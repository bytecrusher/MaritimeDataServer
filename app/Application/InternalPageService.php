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
        $eventTimelineWindowHours = self::normalizeEventTimelineWindowHours($currentUser->getEventTimelineWindowHours() ?? 24);

        return array(
            'myBoardsIdList' => $myBoardsIdList,
            'boardObjsArray' => $boardObjsArray,
            'mapPayload' => self::buildMapPayload($currentUser),
            'eventPayload' => self::buildEventPayload($boardObjsArray, $preferredChartWindowDays, $eventTimelineWindowHours),
            'dashboardUpdateIntervalMs' => max(1000, (int) $currentUser->getDashboardUpdateInterval() * 10000),
            'dashboardOnlineOnlyDefault' => (int) ($currentUser->getDashboardOnlineOnly() ?? $config::$defaultDashboardOnlineOnly),
            'preferredChartWindowDays' => $preferredChartWindowDays,
            'eventTimelineWindowHours' => $eventTimelineWindowHours,
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

    private static function buildEventPayload(array $boardObjsArray, $windowDays = 7, $timelineWindowHours = 24)
    {
        $eventChartSensors = array();
        $eventTimelineBoards = array();
        $eventTimelineSummaryLabels = array();
        $eventTimelineSummary = array();
        $eventTimelineSummaryBuckets = array();
        $eventTimelineLast24h = array();
        $windowDays = max(1, (int) $windowDays);
        $timelineWindowHours = self::normalizeEventTimelineWindowHours($timelineWindowHours);
        $eventWindowStart = new DateTimeImmutable('today -' . ($windowDays - 1) . ' days');
        $eventWindowEnd = new DateTimeImmutable('now');
        $eventTimelineStart = $eventWindowEnd->modify('-' . $timelineWindowHours . ' hours');

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

                $eventRows = myFunctions::getLatestSensorData((int)$eventSensor['id'], self::eventRowLimitForWindow($windowDays, $timelineWindowHours));
                if (!is_array($eventRows)) {
                    continue;
                }

                $boardId = (int)$eventBoardObj->getId();
                $eventTimelineBoards[$boardId]['boardId'] = $boardId;
                $eventTimelineBoards[$boardId]['boardName'] = $eventBoardObj->getName();
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

            $eventTimelineBoard['events'] = self::normalizeEventSequence($eventTimelineBoard['events']);
            if (empty($eventTimelineBoard['events'])) {
                continue;
            }

            $eventDurationSummary = self::buildEventDurationSummary(
                $eventTimelineBoard['events'],
                $eventTimelineSummaryBuckets,
                $eventWindowStart,
                $eventWindowEnd
            );

            $eventTimelineLast24h[] = self::buildEventTimelineChartData($eventTimelineBoard, $eventTimelineStart, $eventWindowEnd);
            $eventTimelineBoard['events'] = self::sortEventsDescending($eventTimelineBoard['events']);
            $eventTimelineBoard['events'] = self::addEventDurationDetails($eventTimelineBoard['events'], $eventWindowEnd);
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

    private static function normalizeEventSequence(array $events)
    {
        $deduplicatedEvents = array();
        foreach ($events as $eventEntry) {
            $eventDateTime = self::parseEventDatetime($eventEntry);
            if (!$eventDateTime instanceof DateTimeImmutable) {
                continue;
            }

            $stateClass = $eventEntry['stateClass'] ?? '';
            if (!in_array($stateClass, array('is-wakeup', 'is-standby'), true)) {
                continue;
            }

            $eventEntry['sortTimestamp'] = $eventDateTime->getTimestamp();
            $eventKey = $eventEntry['sortTimestamp'] . '|' . $stateClass;
            if (!isset($deduplicatedEvents[$eventKey])) {
                $deduplicatedEvents[$eventKey] = $eventEntry;
            }
        }

        $sortedEvents = array_values($deduplicatedEvents);
        usort($sortedEvents, function ($leftEvent, $rightEvent) {
            if ($leftEvent['sortTimestamp'] === $rightEvent['sortTimestamp']) {
                return strcmp($leftEvent['stateClass'], $rightEvent['stateClass']);
            }

            return $leftEvent['sortTimestamp'] <=> $rightEvent['sortTimestamp'];
        });

        $transitionEvents = array();
        $lastStateClass = null;
        foreach ($sortedEvents as $eventEntry) {
            if (($eventEntry['stateClass'] ?? null) === $lastStateClass) {
                continue;
            }

            unset($eventEntry['sortTimestamp']);
            $transitionEvents[] = $eventEntry;
            $lastStateClass = $eventEntry['stateClass'] ?? null;
        }

        return $transitionEvents;
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

    private static function addEventDurationDetails(array $events, DateTimeImmutable $windowEnd)
    {
        $eventCount = count($events);
        for ($eventIndex = 0; $eventIndex < $eventCount; $eventIndex++) {
            $events[$eventIndex]['durationSeconds'] = null;
            $events[$eventIndex]['durationOpen'] = false;

            $eventStart = self::parseEventDatetime($events[$eventIndex]);
            if (!$eventStart instanceof DateTimeImmutable) {
                continue;
            }

            if ($eventIndex === 0) {
                if (($events[$eventIndex]['stateClass'] ?? '') === 'is-wakeup') {
                    $events[$eventIndex]['durationOpen'] = true;
                    continue;
                }
                $eventEnd = $windowEnd;
            } else {
                $eventEnd = self::parseEventDatetime($events[$eventIndex - 1]);
            }

            if (!$eventEnd instanceof DateTimeImmutable || $eventEnd <= $eventStart) {
                continue;
            }

            $events[$eventIndex]['durationSeconds'] = $eventEnd->getTimestamp() - $eventStart->getTimestamp();
        }

        return $events;
    }

    private static function buildEventTimelineChartData(array $eventTimelineBoard, DateTimeImmutable $windowStart, DateTimeImmutable $windowEnd)
    {
        $points = array();
        $timelineEvents = self::eventsAscendingForDuration($eventTimelineBoard['events'] ?? array());
        $timelineSummary = self::buildEventWindowSummary($eventTimelineBoard['events'] ?? array(), $windowStart, $windowEnd);
        $activeStateAtWindowStart = null;
        $activeLabelAtWindowStart = '';

        foreach ($timelineEvents as $timelineEvent) {
            if ($timelineEvent['dateTime'] <= $windowStart) {
                $activeStateAtWindowStart = $timelineEvent['stateClass'];
                $activeLabelAtWindowStart = $activeStateAtWindowStart === 'is-wakeup' ? 'Wakeup' : 'Standby';
                continue;
            }
            break;
        }

        if ($activeStateAtWindowStart !== null) {
            $points[] = self::buildEventTimelinePoint($windowStart, $activeStateAtWindowStart, $activeLabelAtWindowStart);
        }

        foreach (($eventTimelineBoard['events'] ?? array()) as $eventEntry) {
            $eventDateTime = self::parseEventDatetime($eventEntry);
            if (!$eventDateTime instanceof DateTimeImmutable) {
                continue;
            }
            if ($eventDateTime < $windowStart || $eventDateTime > $windowEnd) {
                continue;
            }

            $stateClass = $eventEntry['stateClass'] ?? '';
            if (!in_array($stateClass, array('is-wakeup', 'is-standby'), true)) {
                continue;
            }

            $points[] = self::buildEventTimelinePoint($eventDateTime, $stateClass, $eventEntry['label'] ?? '');
        }

        if (!empty($points)) {
            $lastPoint = $points[count($points) - 1];
            $points[] = self::buildEventTimelinePoint(
                $windowEnd,
                ((int)$lastPoint['y'] === 1) ? 'is-wakeup' : 'is-standby',
                ((int)$lastPoint['y'] === 1) ? 'Wakeup' : 'Standby'
            );
        }

        $points = self::deduplicateEventTimelinePoints($points);

        return array(
            'boardId' => (int)($eventTimelineBoard['boardId'] ?? 0),
            'boardName' => (string)($eventTimelineBoard['boardName'] ?? ''),
            'points' => $points,
            'onlineHours' => $timelineSummary['onlineHours'],
            'standbyHours' => $timelineSummary['standbyHours'],
            'windowHours' => $timelineSummary['windowHours'],
            'onlinePercent' => $timelineSummary['onlinePercent'],
            'standbyPercent' => $timelineSummary['standbyPercent'],
        );
    }

    private static function deduplicateEventTimelinePoints(array $points)
    {
        $deduplicatedPoints = array();
        foreach ($points as $point) {
            $pointKey = (string)($point['x'] ?? '') . '|' . (string)($point['y'] ?? '');
            $deduplicatedPoints[$pointKey] = $point;
        }

        $deduplicatedPoints = array_values($deduplicatedPoints);
        usort($deduplicatedPoints, function ($leftPoint, $rightPoint) {
            return strcmp((string)$leftPoint['x'], (string)$rightPoint['x']);
        });

        return $deduplicatedPoints;
    }

    private static function buildEventTimelinePoint(DateTimeImmutable $eventDateTime, $stateClass, $label)
    {
        return array(
            'x' => $eventDateTime->format(DateTimeInterface::ATOM),
            'y' => $stateClass === 'is-wakeup' ? 1 : 0,
            'label' => $label,
            'timestamp' => $eventDateTime->format('d.m.Y H:i:s'),
        );
    }

    private static function buildEventWindowSummary(array $eventEntries, DateTimeImmutable $windowStart, DateTimeImmutable $windowEnd)
    {
        $onlineSeconds = 0;
        $standbySeconds = 0;
        $normalizedEvents = self::eventsAscendingForDuration($eventEntries);
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

            $overlapSeconds = $segmentEnd->getTimestamp() - $segmentStart->getTimestamp();
            if ($overlapSeconds <= 0) {
                continue;
            }

            if ($segmentState === 'is-wakeup') {
                $onlineSeconds += $overlapSeconds;
            } elseif ($segmentState === 'is-standby') {
                $standbySeconds += $overlapSeconds;
            }
        }

        $windowSeconds = max(1, $windowEnd->getTimestamp() - $windowStart->getTimestamp());

        return array(
            'onlineHours' => round($onlineSeconds / 3600, 2),
            'standbyHours' => round($standbySeconds / 3600, 2),
            'windowHours' => round($windowSeconds / 3600, 2),
            'onlinePercent' => round(($onlineSeconds / $windowSeconds) * 100, 1),
            'standbyPercent' => round(($standbySeconds / $windowSeconds) * 100, 1),
        );
    }

    private static function eventsAscendingForDuration(array $eventEntries)
    {
        $normalizedEventMap = array();

        foreach ($eventEntries as $eventEntry) {
            $eventDateTime = self::parseEventDatetime($eventEntry);
            if (!$eventDateTime instanceof DateTimeImmutable) {
                continue;
            }
            $stateClass = $eventEntry['stateClass'] ?? '';
            if (!in_array($stateClass, array('is-wakeup', 'is-standby'), true)) {
                continue;
            }
            $eventKey = $eventDateTime->getTimestamp() . '|' . $stateClass;
            $normalizedEventMap[$eventKey] = array(
                'stateClass' => $stateClass,
                'dateTime' => $eventDateTime,
            );
        }

        $normalizedEvents = array_values($normalizedEventMap);
        usort($normalizedEvents, function ($leftEvent, $rightEvent) {
            $leftTime = $leftEvent['dateTime']->getTimestamp();
            $rightTime = $rightEvent['dateTime']->getTimestamp();

            if ($leftTime === $rightTime) {
                return strcmp($leftEvent['stateClass'], $rightEvent['stateClass']);
            }

            return $leftTime <=> $rightTime;
        });

        return $normalizedEvents;
    }

    private static function buildEventDurationSummary($eventEntries, $bucketDates, DateTimeImmutable $windowStart, DateTimeImmutable $windowEnd)
    {
        $onlineDailyHours = array_fill(0, count($bucketDates), 0.0);
        $standbyDailyHours = array_fill(0, count($bucketDates), 0.0);
        $normalizedEvents = self::eventsAscendingForDuration($eventEntries);

        $eventCount = count($normalizedEvents);
        for ($eventIndex = 0; $eventIndex < $eventCount; $eventIndex++) {
            $segmentState = $normalizedEvents[$eventIndex]['stateClass'];
            $segmentStart = $normalizedEvents[$eventIndex]['dateTime'];
            $hasNextEvent = ($eventIndex + 1 < $eventCount);

            $segmentEnd = $hasNextEvent ? $normalizedEvents[$eventIndex + 1]['dateTime'] : $windowEnd;

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
