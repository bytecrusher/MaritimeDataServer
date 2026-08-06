<?php

require_once(__DIR__ . '/../Domain/User/user.class.php');
require_once(__DIR__ . '/../Domain/Board/board.class.php');
require_once(__DIR__ . '/myFunctions.func.php');
require_once(__DIR__ . '/DeviceEventTimelineCalculator.php');

class InternalPageService
{
    public static function resolveCurrentUserFromSession()
    {
        if (isset($_SESSION['userId']) && (int) $_SESSION['userId'] > 0) {
            $sessionUserData = dbGetData::getUserById((int) $_SESSION['userId']);
            if (is_array($sessionUserData) && !empty($sessionUserData['email'])) {
                $currentUser = new user($sessionUserData['email']);
                $_SESSION['userObj'] = serialize($currentUser);
                mds_set_current_language($currentUser->getLanguage());
                return $currentUser;
            }
        }

        if (!isset($_SESSION['userObj'])) {
            return false;
        }

        $sessionUserObj = @unserialize($_SESSION['userObj'], ['allowed_classes' => ['user']]);
        if ($sessionUserObj instanceof user) {
            $_SESSION['userId'] = $sessionUserObj->getId();
            mds_set_current_language($sessionUserObj->getLanguage());
            return $sessionUserObj;
        }

        return false;
    }

    public static function buildPageData($currentUser, $config)
    {
        $myBoardsIdList = $currentUser ? $currentUser->getMyBoardsAll() : array();
        if (!is_array($myBoardsIdList)) {
            $myBoardsIdList = array();
        }
        $boardObjsArray = self::buildBoardObjects($myBoardsIdList);
        $preferredChartWindowDays = (int) ($currentUser->getPreferredChartWindowDays() ?: $config::$defaultChartWindowDays);
        if ($preferredChartWindowDays < 1) {
            $preferredChartWindowDays = 7;
        }
        $eventTimelineWindowHours = self::normalizeEventTimelineWindowHours($currentUser->getEventTimelineWindowHours() ?? 24);

        return array(
            'myBoardsIdList' => $myBoardsIdList,
            'boardObjsArray' => $boardObjsArray,
            'mapPayload' => self::buildMapPayload($currentUser),
            'eventPayload' => self::buildEventPayload($boardObjsArray, $preferredChartWindowDays, $eventTimelineWindowHours, (int)$currentUser->getId()),
            'dashboardUpdateIntervalMs' => max(1000, (int) $currentUser->getDashboardUpdateInterval() * 10000),
            'dashboardOnlineOnlyDefault' => (int) ($currentUser->getDashboardOnlineOnly() ?? $config::$defaultDashboardOnlineOnly),
            'preferredChartWindowDays' => $preferredChartWindowDays,
            'eventTimelineWindowHours' => $eventTimelineWindowHours,
            'demoMode' => (bool) $config::$demoMode,
            'showInstallAlert' => myFunctions::isUserAdmin((int)$currentUser->getId()) && is_dir(__DIR__ . '/../../public/install'),
            'hasBoards' => !empty($myBoardsIdList),
        );
    }

    private static function buildBoardObjects($myBoardsIdList)
    {
        $boardObjsArray = array();
        foreach ($myBoardsIdList as $boardData) {
            if (!isset($boardData['id'])) {
                continue;
            }
            $boardObjsArray[] = new board($boardData['id']);
        }

        return $boardObjsArray;
    }

    private static function buildMapPayload($currentUser)
    {
        $mapBoardNames = array();
        $mapGpsData = array();

        if (!$currentUser) {
            return array(
                'boardNames' => $mapBoardNames,
                'gpsData' => $mapGpsData,
            );
        }

        $mapBoards = myFunctions::getMyBoards($currentUser->getId());
        foreach ($mapBoards as $mapBoard) {
            $hasAccessibleGpsSensor = false;
            $boardSensors = myFunctions::getAllSensorsOfBoard((int)$mapBoard['id']);
            if (is_array($boardSensors)) {
                foreach ($boardSensors as $boardSensor) {
                    if (($boardSensor['sensorTypesName'] ?? null) === 'GPS' && myFunctions::canUserAccessSensor((int)$currentUser->getId(), (int)$boardSensor['id'])) {
                        $hasAccessibleGpsSensor = true;
                        break;
                    }
                }
            }
            if (!$hasAccessibleGpsSensor) {
                continue;
            }

            $gpsData = myFunctions::getAllGpsData($mapBoard['id']);
            if (!empty($gpsData) && $gpsData !== 0) {
                $boardId = (string) $mapBoard['id'];
                $mapBoardNames[$boardId] = $mapBoard['name'];
                $mapGpsData[$boardId] = $gpsData;
            }
        }

        return array(
            'boardNames' => $mapBoardNames,
            'gpsData' => $mapGpsData,
        );
    }

    private static function buildEventPayload(array $boardObjsArray, $windowDays = 7, $timelineWindowHours = 24, $userId = 0)
    {
        $eventChartSensors = array();
        $eventTimelineBoards = array();
        $eventTimelineSummaryLabels = array();
        $eventTimelineSummary = array();
        $eventTimelineSummaryBuckets = array();
        $eventTimelineLast24h = array();
        $eventStatusByBoard = array();
        $windowDays = max(1, (int) $windowDays);
        $timelineWindowHours = self::normalizeEventTimelineWindowHours($timelineWindowHours);
        $eventWindowStart = new DateTimeImmutable('today -' . ($windowDays - 1) . ' days');
        $eventWindowEnd = new DateTimeImmutable('now');

        for ($eventOffset = 0; $eventOffset < $windowDays; $eventOffset++) {
            $eventDay = $eventWindowStart->modify('+' . $eventOffset . ' days');
            $eventTimelineSummaryLabels[] = $eventDay->format('d.m.');
            $eventTimelineSummaryBuckets[] = $eventDay->format('Y-m-d');
        }

        foreach ($boardObjsArray as $eventBoardObj) {
            $eventBoardSensors = myFunctions::getAllSensorsOfBoard($eventBoardObj->getId());
            if (!is_array($eventBoardSensors)) {
                continue;
            }

            foreach ($eventBoardSensors as $eventSensor) {
                if (!myFunctions::canUserAccessSensor((int)$userId, (int)$eventSensor['id'])) {
                    continue;
                }
                if (($eventSensor['sensorTypesName'] ?? null) !== 'WakeupStan') {
                    continue;
                }

                $eventChartSensors[] = array(
                    'sensorId' => (int)$eventSensor['id'],
                    'boardId' => (int)$eventBoardObj->getId(),
                    'boardName' => $eventBoardObj->getName(),
                    'sensorName' => $eventSensor['name'] ?? 'WakeupStan',
                    'sensorTypeName' => $eventSensor['sensorTypesName'] ?? 'WakeupStan',
                );

                $eventRows = myFunctions::getLatestSensorData((int)$eventSensor['id'], self::eventRowLimitForWindow($windowDays, $timelineWindowHours));
                if (!is_array($eventRows)) {
                    continue;
                }

                $boardId = (int)$eventBoardObj->getId();
                $offlineDataTimerMinutes = max(1, (int)$eventBoardObj->getOfflineDataTimer());
                $eventTimelineBoards[$boardId]['boardId'] = $boardId;
                $eventTimelineBoards[$boardId]['boardName'] = $eventBoardObj->getName();
                $eventTimelineBoards[$boardId]['maxTransitionGapSeconds'] = max(300, $offlineDataTimerMinutes * 2 * 60);
                $eventTimelineBoards[$boardId]['events'] = $eventTimelineBoards[$boardId]['events'] ?? array();

                foreach (self::buildEventEntriesFromRows($eventRows, $eventSensor['name'] ?? 'WakeupStan') as $eventEntry) {
                    $eventTimelineBoards[$boardId]['events'][] = $eventEntry;
                }
            }
        }

        foreach ($eventTimelineBoards as &$eventTimelineBoard) {
            if (empty($eventTimelineBoard['events'])) {
                continue;
            }

            $eventTimelineBoard['events'] = DeviceEventTimelineCalculator::normalizeSequence($eventTimelineBoard['events']);
            if (empty($eventTimelineBoard['events'])) {
                continue;
            }

            $eventStatusByBoard[(int)$eventTimelineBoard['boardId']] = DeviceEventTimelineCalculator::buildBoardStatus($eventTimelineBoard['events']);

            $eventDurationSummary = DeviceEventTimelineCalculator::buildDailySummary(
                $eventTimelineBoard['events'],
                $eventTimelineSummaryBuckets,
                $eventWindowStart,
                $eventWindowEnd,
                $eventTimelineBoard['maxTransitionGapSeconds']
            );

            $eventTimelineLast24h[] = DeviceEventTimelineCalculator::buildTimelineChartData(
                $eventTimelineBoard,
                $eventWindowEnd->modify('-72 hours'),
                $eventWindowEnd
            );
            $eventTimelineBoard['events'] = self::sortEventsDescending($eventTimelineBoard['events']);
            $eventTimelineBoard['events'] = DeviceEventTimelineCalculator::addDurationDetails(
                $eventTimelineBoard['events'],
                $eventWindowEnd,
                $eventTimelineBoard['maxTransitionGapSeconds']
            );
            $eventTimelineBoard['events'] = array_slice($eventTimelineBoard['events'], 0, 80);
            $eventTimelineSummary[] = array(
                'boardId' => (int)$eventTimelineBoard['boardId'],
                'boardName' => $eventTimelineBoard['boardName'],
                'onlineHoursTotal' => $eventDurationSummary['onlineHoursTotal'],
                'standbyHoursTotal' => $eventDurationSummary['standbyHoursTotal'],
                'onlineDailyHours' => $eventDurationSummary['onlineDailyHours'],
                'standbyDailyHours' => $eventDurationSummary['standbyDailyHours'],
            );
        }
        unset($eventTimelineBoard);

        return array(
            'chartSensors' => $eventChartSensors,
            'timelineBoards' => $eventTimelineBoards,
            'summaryLabels' => $eventTimelineSummaryLabels,
            'summary' => $eventTimelineSummary,
            'last24hTimeline' => $eventTimelineLast24h,
            'timelineWindowHours' => $timelineWindowHours,
            'statusByBoard' => $eventStatusByBoard,
        );
    }

    private static function normalizeEventTimelineWindowHours($hours)
    {
        $hours = (int)$hours;
        return in_array($hours, array(3, 6, 12, 24, 48, 72), true) ? $hours : 24;
    }

    private static function eventRowLimitForWindow($windowDays, $timelineWindowHours = 24)
    {
        $effectiveDays = max((int)$windowDays, (int)ceil(self::normalizeEventTimelineWindowHours($timelineWindowHours) / 24) + 1);
        return min(3000, max(500, ($effectiveDays + 1) * 150));
    }

    private static function buildEventEntriesFromRows(array $eventRows, $sensorName)
    {
        $eventEntries = array();
        foreach ($eventRows as $eventRow) {
            $legacyEventEntry = self::buildLegacyWakeupStandbyEntry($eventRow, $sensorName);
            if ($legacyEventEntry !== null) {
                $eventEntries[] = $legacyEventEntry;
            }

            foreach (array(
                array('label' => $eventRow['value1'] ?? null, 'time' => $eventRow['value2'] ?? null, 'fallback' => ($eventRow['val_date'] ?? '') . ' ' . ($eventRow['val_time'] ?? '')),
                array('label' => $eventRow['value3'] ?? null, 'time' => $eventRow['value4'] ?? null, 'fallback' => ($eventRow['val_date'] ?? '') . ' ' . ($eventRow['val_time'] ?? ''))
            ) as $eventPair) {
                if (!self::isEventLabel($eventPair['label'])) {
                    continue;
                }

                $normalizedEvent = self::normalizeEventLabel($eventPair['label']);
                $eventEntries[] = array(
                    'label' => $normalizedEvent['label'],
                    'stateClass' => $normalizedEvent['stateClass'],
                    'persistentOnline' => !empty($normalizedEvent['persistentOnline']),
                    'rawLabel' => trim((string)$eventPair['label']),
                    'timestamp' => self::isEventTimestamp($eventPair['time']) ? trim((string)$eventPair['time']) : trim((string)$eventPair['fallback']),
                    'fallbackTimestamp' => trim((string)$eventPair['fallback']),
                    'sensorName' => $sensorName,
                    'readingTime' => $eventRow['reading_time'] ?? null,
                    'sourceRowId' => isset($eventRow['id']) ? (int)$eventRow['id'] : null,
                );
            }
        }

        return $eventEntries;
    }

    private static function buildLegacyWakeupStandbyEntry(array $eventRow, $sensorName)
    {
        $value1 = trim((string)($eventRow['value1'] ?? ''));
        $value2 = trim((string)($eventRow['value2'] ?? ''));
        $value3 = trim((string)($eventRow['value3'] ?? ''));
        $value4 = trim((string)($eventRow['value4'] ?? ''));

        if ($value1 !== '0' || $value2 !== '1' || $value3 !== '0' || $value4 !== '0') {
            return null;
        }

        $fallbackTimestamp = trim((string)(($eventRow['val_date'] ?? '') . ' ' . ($eventRow['val_time'] ?? '')));
        if ($fallbackTimestamp === '') {
            $fallbackTimestamp = trim((string)($eventRow['reading_time'] ?? ''));
        }

        return array(
            'label' => 'Standby',
            'stateClass' => 'is-standby',
            'persistentOnline' => false,
            'rawLabel' => '0|1|0|0',
            'timestamp' => $fallbackTimestamp,
            'fallbackTimestamp' => $fallbackTimestamp,
            'sensorName' => $sensorName,
            'readingTime' => $eventRow['reading_time'] ?? null,
            'sourceRowId' => isset($eventRow['id']) ? (int)$eventRow['id'] : null,
        );
    }

    private static function sortEventsDescending(array $events)
    {
        usort($events, function ($leftEvent, $rightEvent) {
            $leftDateTime = self::parseEventDatetime($leftEvent);
            $rightDateTime = self::parseEventDatetime($rightEvent);
            $leftTime = $leftDateTime instanceof DateTimeImmutable ? $leftDateTime->getTimestamp() : 0;
            $rightTime = $rightDateTime instanceof DateTimeImmutable ? $rightDateTime->getTimestamp() : 0;

            if ($rightTime === $leftTime) {
                return strcmp((string)($rightEvent['readingTime'] ?? ''), (string)($leftEvent['readingTime'] ?? ''));
            }

            return $rightTime <=> $leftTime;
        });

        return $events;
    }

    private static function isEventLabel($value)
    {
        if ($value === null) {
            return false;
        }

        $trimmedValue = trim((string)$value);
        if ($trimmedValue === '') {
            return false;
        }

        return !is_numeric($trimmedValue);
    }

    private static function isEventTimestamp($value)
    {
        if ($value === null) {
            return false;
        }

        return preg_match('/^\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}:\d{2}$/', trim((string)$value)) === 1;
    }

    private static function normalizeEventLabel($value)
    {
        $rawValue = trim((string)$value);
        $normalizedValue = mb_strtolower($rawValue);
        if (str_contains($normalizedValue, 'always online') || str_contains($normalizedValue, 'always-on')) {
            return array(
                'label' => function_exists('mds_t') ? mds_t('internal.always_online') : 'Always online',
                'stateClass' => 'is-wakeup',
                'persistentOnline' => true
            );
        }
        if (str_contains($normalizedValue, 'sleep') || str_contains($normalizedValue, 'standby')) {
            return array('label' => 'Standby', 'stateClass' => 'is-standby', 'persistentOnline' => false);
        }
        if (str_contains($normalizedValue, 'wake')) {
            return array('label' => 'Wakeup', 'stateClass' => 'is-wakeup', 'persistentOnline' => false);
        }
        return array('label' => $rawValue, 'stateClass' => 'is-other', 'persistentOnline' => false);
    }

    private static function parseEventDatetime($eventEntry)
    {
        if (!empty($eventEntry['timestamp'])) {
            $timestampDate = DateTimeImmutable::createFromFormat('d.m.Y H:i:s', (string)$eventEntry['timestamp']);
            if ($timestampDate instanceof DateTimeImmutable) {
                return $timestampDate;
            }
            $timestampDate = DateTime::createFromFormat('d.m.Y H:i:s', (string)$eventEntry['timestamp']);
            if ($timestampDate instanceof DateTime) {
                return DateTimeImmutable::createFromMutable($timestampDate);
            }
        }

        if (!empty($eventEntry['readingTime'])) {
            try {
                return new DateTimeImmutable((string)$eventEntry['readingTime']);
            } catch (Exception $exception) {
                return null;
            }
        }

        return null;
    }
}
