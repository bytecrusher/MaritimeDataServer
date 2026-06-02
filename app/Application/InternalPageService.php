<?php

require_once(__DIR__ . '/../Domain/User/user.class.php');
require_once(__DIR__ . '/../Domain/Board/board.class.php');
require_once(__DIR__ . '/myFunctions.func.php');

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

        return array(
            'myBoardsIdList' => $myBoardsIdList,
            'boardObjsArray' => $boardObjsArray,
            'mapPayload' => self::buildMapPayload($currentUser),
            'eventPayload' => self::buildEventPayload($boardObjsArray, $preferredChartWindowDays),
            'dashboardUpdateIntervalMs' => max(1000, (int) $currentUser->getDashboardUpdateInterval() * 10000),
            'dashboardOnlineOnlyDefault' => (int) ($currentUser->getDashboardOnlineOnly() ?? $config::$defaultDashboardOnlineOnly),
            'preferredChartWindowDays' => $preferredChartWindowDays,
            'demoMode' => (bool) $config::$demoMode,
            'showInstallAlert' => ((int) $currentUser->getUserGroupAdmin() === 1) && is_dir(__DIR__ . '/../../public/install'),
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

    private static function buildEventPayload(array $boardObjsArray, $windowDays = 7)
    {
        $eventChartSensors = array();
        $eventTimelineBoards = array();
        $eventTimelineSummaryLabels = array();
        $eventTimelineSummary = array();
        $eventTimelineSummaryBuckets = array();
        $windowDays = max(1, (int) $windowDays);
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

                $eventRows = myFunctions::getLatestSensorData((int)$eventSensor['id'], 200);
                if (!is_array($eventRows)) {
                    continue;
                }

                foreach ($eventRows as $eventRow) {
                    $boardId = (int)$eventBoardObj->getId();
                    $eventTimelineBoards[$boardId]['boardId'] = $boardId;
                    $eventTimelineBoards[$boardId]['boardName'] = $eventBoardObj->getName();
                    $eventTimelineBoards[$boardId]['events'] = $eventTimelineBoards[$boardId]['events'] ?? array();

                    foreach (array(
                        array('label' => $eventRow['value1'] ?? null, 'time' => $eventRow['value2'] ?? null, 'fallback' => ($eventRow['val_date'] ?? '') . ' ' . ($eventRow['val_time'] ?? '')),
                        array('label' => $eventRow['value3'] ?? null, 'time' => $eventRow['value4'] ?? null, 'fallback' => ($eventRow['val_date'] ?? '') . ' ' . ($eventRow['val_time'] ?? ''))
                    ) as $eventPair) {
                        if (!self::isEventLabel($eventPair['label'])) {
                            continue;
                        }

                        $normalizedEvent = self::normalizeEventLabel($eventPair['label']);
                        $eventTimelineBoards[$boardId]['events'][] = array(
                            'label' => $normalizedEvent['label'],
                            'stateClass' => $normalizedEvent['stateClass'],
                            'rawLabel' => trim((string)$eventPair['label']),
                            'timestamp' => self::isEventTimestamp($eventPair['time']) ? trim((string)$eventPair['time']) : trim((string)$eventPair['fallback']),
                            'fallbackTimestamp' => trim((string)$eventPair['fallback']),
                            'sensorName' => $eventSensor['name'] ?? 'WakeupStan',
                            'readingTime' => $eventRow['reading_time'] ?? null,
                        );
                    }
                }
            }
        }

        foreach ($eventTimelineBoards as &$eventTimelineBoard) {
            if (empty($eventTimelineBoard['events'])) {
                continue;
            }

            $eventDurationSummary = self::buildEventDurationSummary(
                $eventTimelineBoard['events'],
                $eventTimelineSummaryBuckets,
                $eventWindowStart,
                $eventWindowEnd
            );

            usort($eventTimelineBoard['events'], function ($leftEvent, $rightEvent) {
                $leftDateTime = self::parseEventDatetime($leftEvent);
                $rightDateTime = self::parseEventDatetime($rightEvent);
                $leftTime = $leftDateTime instanceof DateTimeImmutable ? $leftDateTime->getTimestamp() : 0;
                $rightTime = $rightDateTime instanceof DateTimeImmutable ? $rightDateTime->getTimestamp() : 0;

                if ($rightTime === $leftTime) {
                    return strcmp((string)($rightEvent['readingTime'] ?? ''), (string)($leftEvent['readingTime'] ?? ''));
                }

                return $rightTime <=> $leftTime;
            });

            $eventTimelineBoard['events'] = array_slice($eventTimelineBoard['events'], 0, 40);
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
        );
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
        if (str_contains($normalizedValue, 'sleep') || str_contains($normalizedValue, 'standby')) {
            return array('label' => 'Standby', 'stateClass' => 'is-standby');
        }
        if (str_contains($normalizedValue, 'wake')) {
            return array('label' => 'Wakeup', 'stateClass' => 'is-wakeup');
        }
        return array('label' => $rawValue, 'stateClass' => 'is-other');
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

    private static function buildEventDurationSummary($eventEntries, $bucketDates, DateTimeImmutable $windowStart, DateTimeImmutable $windowEnd)
    {
        $onlineDailyHours = array_fill(0, count($bucketDates), 0.0);
        $standbyDailyHours = array_fill(0, count($bucketDates), 0.0);
        $normalizedEvents = array();

        foreach ($eventEntries as $eventEntry) {
            $eventDateTime = self::parseEventDatetime($eventEntry);
            if (!$eventDateTime instanceof DateTimeImmutable) {
                continue;
            }
            $stateClass = $eventEntry['stateClass'] ?? '';
            if (!in_array($stateClass, array('is-wakeup', 'is-standby'), true)) {
                continue;
            }
            $normalizedEvents[] = array(
                'stateClass' => $stateClass,
                'dateTime' => $eventDateTime,
            );
        }

        usort($normalizedEvents, function ($leftEvent, $rightEvent) {
            return $leftEvent['dateTime']->getTimestamp() <=> $rightEvent['dateTime']->getTimestamp();
        });

        $eventCount = count($normalizedEvents);
        for ($eventIndex = 0; $eventIndex < $eventCount; $eventIndex++) {
            $segmentState = $normalizedEvents[$eventIndex]['stateClass'];
            $segmentStart = $normalizedEvents[$eventIndex]['dateTime'];
            $segmentEnd = ($eventIndex + 1 < $eventCount) ? $normalizedEvents[$eventIndex + 1]['dateTime'] : $windowEnd;

            if ($segmentEnd <= $windowStart || $segmentStart >= $windowEnd || $segmentEnd <= $segmentStart) {
                continue;
            }

            if ($segmentStart < $windowStart) {
                $segmentStart = $windowStart;
            }
            if ($segmentEnd > $windowEnd) {
                $segmentEnd = $windowEnd;
            }

            foreach ($bucketDates as $bucketIndex => $bucketDate) {
                $bucketStart = new DateTimeImmutable($bucketDate . ' 00:00:00');
                $bucketEnd = $bucketStart->modify('+1 day');
                if ($bucketEnd > $windowEnd) {
                    $bucketEnd = $windowEnd;
                }

                $overlapStart = ($segmentStart > $bucketStart) ? $segmentStart : $bucketStart;
                $overlapEnd = ($segmentEnd < $bucketEnd) ? $segmentEnd : $bucketEnd;
                $overlapSeconds = $overlapEnd->getTimestamp() - $overlapStart->getTimestamp();

                if ($overlapSeconds <= 0) {
                    continue;
                }

                $overlapHours = round($overlapSeconds / 3600, 2);
                if ($segmentState === 'is-wakeup') {
                    $onlineDailyHours[$bucketIndex] += $overlapHours;
                } elseif ($segmentState === 'is-standby') {
                    $standbyDailyHours[$bucketIndex] += $overlapHours;
                }
            }
        }

        return array(
            'onlineHoursTotal' => round(array_sum($onlineDailyHours), 2),
            'standbyHoursTotal' => round(array_sum($standbyDailyHours), 2),
            'onlineDailyHours' => array_map(function ($hours) {
                return round($hours, 2);
            }, $onlineDailyHours),
            'standbyDailyHours' => array_map(function ($hours) {
                return round($hours, 2);
            }, $standbyDailyHours),
        );
    }
}
