<?php

class DeviceEventTimelineCalculator
{
    public static function normalizeSequence(array $events)
    {
        $deduplicatedEvents = array();
        foreach ($events as $eventEntry) {
            $eventDateTime = self::parseEventDatetime($eventEntry);
            if (!$eventDateTime instanceof DateTimeImmutable) {
                continue;
            }

            $stateClass = $eventEntry['stateClass'] ?? '';
            if (!self::isSupportedState($stateClass)) {
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
                unset($eventEntry['sortTimestamp']);
                $transitionEvents[count($transitionEvents) - 1] = $eventEntry;
                continue;
            }

            unset($eventEntry['sortTimestamp']);
            $transitionEvents[] = $eventEntry;
            $lastStateClass = $eventEntry['stateClass'] ?? null;
        }

        return $transitionEvents;
    }

    public static function buildBoardStatus(array $eventEntries)
    {
        $normalizedEvents = self::eventsAscending($eventEntries);
        if (empty($normalizedEvents)) {
            return null;
        }

        $latestEvent = $normalizedEvents[count($normalizedEvents) - 1];
        $latestDateTime = $latestEvent['dateTime'];
        $persistentOnline = !empty($latestEvent['persistentOnline']);
        $stateClass = (string)$latestEvent['stateClass'];

        $alwaysOnlineLabel = function_exists('mds_t') ? mds_t('internal.always_online') : 'Always online';
        $currentLabel = $stateClass === 'is-standby'
            ? 'Standby'
            : ($persistentOnline ? $alwaysOnlineLabel : 'Wakeup');

        return array(
            'modeLabel' => $persistentOnline
                ? $alwaysOnlineLabel
                : (function_exists('mds_t') ? mds_t('internal.wakeup_standby_cycle') : 'Wakeup / Standby'),
            'modeClass' => $persistentOnline ? 'is-persistent-online' : 'is-wakeup-cycle',
            'currentLabel' => $currentLabel,
            'currentClass' => $stateClass,
            'persistentOnline' => $persistentOnline,
            'timestamp' => $latestDateTime->format('d.m.Y H:i:s'),
        );
    }

    public static function addDurationDetails(array $events, DateTimeImmutable $windowEnd)
    {
        $durationByEventKey = array();
        $normalizedEvents = self::eventsAscending($events);
        $normalizedEventCount = count($normalizedEvents);

        for ($eventIndex = 0; $eventIndex < $normalizedEventCount; $eventIndex++) {
            $eventDateTime = $normalizedEvents[$eventIndex]['dateTime'];
            $stateClass = $normalizedEvents[$eventIndex]['stateClass'];
            $persistentOnline = !empty($normalizedEvents[$eventIndex]['persistentOnline']);
            $eventKey = $eventDateTime->getTimestamp() . '|' . $stateClass;
            $nextEventDateTime = $normalizedEvents[$eventIndex + 1]['dateTime'] ?? null;

            $durationByEventKey[$eventKey] = array(
                'durationSeconds' => null,
                'durationOpen' => false,
                'persistentOnline' => $persistentOnline,
            );

            if ($nextEventDateTime instanceof DateTimeImmutable && $nextEventDateTime > $eventDateTime) {
                $durationByEventKey[$eventKey]['durationSeconds'] = $nextEventDateTime->getTimestamp() - $eventDateTime->getTimestamp();
            } elseif ($stateClass === 'is-wakeup' && !$persistentOnline) {
                $durationByEventKey[$eventKey]['durationOpen'] = true;
            } elseif ($windowEnd > $eventDateTime) {
                $durationByEventKey[$eventKey]['durationSeconds'] = $windowEnd->getTimestamp() - $eventDateTime->getTimestamp();
            }
        }

        foreach ($events as $eventIndex => $eventEntry) {
            $events[$eventIndex]['durationSeconds'] = null;
            $events[$eventIndex]['durationOpen'] = false;
            $events[$eventIndex]['persistentOnline'] = !empty($eventEntry['persistentOnline']);

            $eventDateTime = self::parseEventDatetime($eventEntry);
            if (!$eventDateTime instanceof DateTimeImmutable) {
                continue;
            }

            $eventKey = $eventDateTime->getTimestamp() . '|' . ($eventEntry['stateClass'] ?? '');
            if (isset($durationByEventKey[$eventKey])) {
                $events[$eventIndex] = array_merge($events[$eventIndex], $durationByEventKey[$eventKey]);
            }
        }

        return $events;
    }

    public static function buildTimelineChartData(array $eventTimelineBoard, DateTimeImmutable $windowStart, DateTimeImmutable $windowEnd)
    {
        $eventEntries = $eventTimelineBoard['events'] ?? array();
        $points = array();
        $timelineEvents = self::eventsAscending($eventEntries);
        $timelineSummary = self::buildWindowSummary($eventEntries, $windowStart, $windowEnd);
        $activeStateAtWindowStart = null;

        foreach ($timelineEvents as $timelineEvent) {
            if ($timelineEvent['dateTime'] <= $windowStart) {
                $activeStateAtWindowStart = $timelineEvent['stateClass'];
                continue;
            }
            break;
        }

        if ($activeStateAtWindowStart !== null) {
            $points[] = self::buildTimelinePoint(
                $windowStart,
                $activeStateAtWindowStart,
                $activeStateAtWindowStart === 'is-wakeup' ? 'Wakeup' : 'Standby'
            );
        }

        foreach ($eventEntries as $eventEntry) {
            $eventDateTime = self::parseEventDatetime($eventEntry);
            $stateClass = $eventEntry['stateClass'] ?? '';
            if (!$eventDateTime instanceof DateTimeImmutable || !self::isSupportedState($stateClass)) {
                continue;
            }
            if ($eventDateTime < $windowStart || $eventDateTime > $windowEnd) {
                continue;
            }

            $points[] = self::buildTimelinePoint(
                $eventDateTime,
                $stateClass,
                $eventEntry['label'] ?? '',
                !empty($eventEntry['persistentOnline'])
            );
        }

        if (!empty($points)) {
            $lastPointIndex = count($points) - 1;
            $lastPoint = $points[$lastPointIndex];
            if ((int)$lastPoint['y'] === 1 && empty($lastPoint['persistentOnline'])) {
                $points[$lastPointIndex]['openEnded'] = true;
            } else {
                $points[] = self::buildTimelinePoint(
                    $windowEnd,
                    (int)$lastPoint['y'] === 1 ? 'is-wakeup' : 'is-standby',
                    $lastPoint['label'] ?? ((int)$lastPoint['y'] === 1 ? 'Always online' : 'Standby'),
                    !empty($lastPoint['persistentOnline'])
                );
            }
        }

        $points = self::deduplicateTimelinePoints($points);

        return array_merge(array(
            'boardId' => (int)($eventTimelineBoard['boardId'] ?? 0),
            'boardName' => (string)($eventTimelineBoard['boardName'] ?? ''),
            'points' => $points,
        ), $timelineSummary);
    }

    public static function buildWindowSummary(array $eventEntries, DateTimeImmutable $windowStart, DateTimeImmutable $windowEnd)
    {
        $totals = self::accumulateSegments($eventEntries, $windowStart, $windowEnd);
        $windowSeconds = max(1, $windowEnd->getTimestamp() - $windowStart->getTimestamp());

        return array(
            'onlineHours' => round($totals['onlineSeconds'] / 3600, 2),
            'standbyHours' => round($totals['standbySeconds'] / 3600, 2),
            'windowHours' => round($windowSeconds / 3600, 2),
            'onlinePercent' => round(($totals['onlineSeconds'] / $windowSeconds) * 100, 1),
            'standbyPercent' => round(($totals['standbySeconds'] / $windowSeconds) * 100, 1),
        );
    }

    public static function buildDailySummary(array $eventEntries, array $bucketDates, DateTimeImmutable $windowStart, DateTimeImmutable $windowEnd)
    {
        $onlineDailySeconds = array_fill(0, count($bucketDates), 0);
        $standbyDailySeconds = array_fill(0, count($bucketDates), 0);

        foreach (self::segments($eventEntries, $windowStart, $windowEnd) as $segment) {
            foreach ($bucketDates as $bucketIndex => $bucketDate) {
                $bucketStart = new DateTimeImmutable($bucketDate . ' 00:00:00');
                $bucketEnd = $bucketStart->modify('+1 day');
                if ($bucketEnd > $windowEnd) {
                    $bucketEnd = $windowEnd;
                }

                $overlapStart = $segment['start'] > $bucketStart ? $segment['start'] : $bucketStart;
                $overlapEnd = $segment['end'] < $bucketEnd ? $segment['end'] : $bucketEnd;
                $overlapSeconds = $overlapEnd->getTimestamp() - $overlapStart->getTimestamp();
                if ($overlapSeconds <= 0) {
                    continue;
                }

                if ($segment['stateClass'] === 'is-wakeup') {
                    $onlineDailySeconds[$bucketIndex] += $overlapSeconds;
                } else {
                    $standbyDailySeconds[$bucketIndex] += $overlapSeconds;
                }
            }
        }

        $onlineDailyHours = array_map(function ($seconds) {
            return round($seconds / 3600, 2);
        }, $onlineDailySeconds);
        $standbyDailyHours = array_map(function ($seconds) {
            return round($seconds / 3600, 2);
        }, $standbyDailySeconds);

        return array(
            'onlineHoursTotal' => round(array_sum($onlineDailySeconds) / 3600, 2),
            'standbyHoursTotal' => round(array_sum($standbyDailySeconds) / 3600, 2),
            'onlineDailyHours' => $onlineDailyHours,
            'standbyDailyHours' => $standbyDailyHours,
        );
    }

    private static function accumulateSegments(array $eventEntries, DateTimeImmutable $windowStart, DateTimeImmutable $windowEnd)
    {
        $totals = array('onlineSeconds' => 0, 'standbySeconds' => 0);
        foreach (self::segments($eventEntries, $windowStart, $windowEnd) as $segment) {
            $seconds = $segment['end']->getTimestamp() - $segment['start']->getTimestamp();
            if ($segment['stateClass'] === 'is-wakeup') {
                $totals['onlineSeconds'] += $seconds;
            } else {
                $totals['standbySeconds'] += $seconds;
            }
        }

        return $totals;
    }

    private static function segments(array $eventEntries, DateTimeImmutable $windowStart, DateTimeImmutable $windowEnd)
    {
        $segments = array();
        $normalizedEvents = self::eventsAscending($eventEntries);
        $eventCount = count($normalizedEvents);

        for ($eventIndex = 0; $eventIndex < $eventCount; $eventIndex++) {
            $event = $normalizedEvents[$eventIndex];
            $hasNextEvent = isset($normalizedEvents[$eventIndex + 1]);
            if (!$hasNextEvent && $event['stateClass'] === 'is-wakeup' && empty($event['persistentOnline'])) {
                continue;
            }

            $segmentStart = $event['dateTime'];
            $segmentEnd = $hasNextEvent ? $normalizedEvents[$eventIndex + 1]['dateTime'] : $windowEnd;
            if ($segmentEnd <= $windowStart || $segmentStart >= $windowEnd || $segmentEnd <= $segmentStart) {
                continue;
            }

            $segments[] = array(
                'stateClass' => $event['stateClass'],
                'start' => $segmentStart < $windowStart ? $windowStart : $segmentStart,
                'end' => $segmentEnd > $windowEnd ? $windowEnd : $segmentEnd,
            );
        }

        return $segments;
    }

    private static function eventsAscending(array $eventEntries)
    {
        $normalizedEventMap = array();
        foreach ($eventEntries as $eventEntry) {
            $eventDateTime = self::parseEventDatetime($eventEntry);
            $stateClass = $eventEntry['stateClass'] ?? '';
            if (!$eventDateTime instanceof DateTimeImmutable || !self::isSupportedState($stateClass)) {
                continue;
            }

            $eventKey = $eventDateTime->getTimestamp() . '|' . $stateClass;
            $normalizedEventMap[$eventKey] = array(
                'stateClass' => $stateClass,
                'dateTime' => $eventDateTime,
                'persistentOnline' => !empty($eventEntry['persistentOnline']),
            );
        }

        $normalizedEvents = array_values($normalizedEventMap);
        usort($normalizedEvents, function ($leftEvent, $rightEvent) {
            $leftTime = $leftEvent['dateTime']->getTimestamp();
            $rightTime = $rightEvent['dateTime']->getTimestamp();
            return $leftTime === $rightTime
                ? strcmp($leftEvent['stateClass'], $rightEvent['stateClass'])
                : $leftTime <=> $rightTime;
        });

        return $normalizedEvents;
    }

    private static function parseEventDatetime(array $eventEntry)
    {
        if (!empty($eventEntry['timestamp'])) {
            $timestampDate = DateTimeImmutable::createFromFormat('d.m.Y H:i:s', (string)$eventEntry['timestamp']);
            if ($timestampDate instanceof DateTimeImmutable) {
                return $timestampDate;
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

    private static function isSupportedState($stateClass)
    {
        return in_array($stateClass, array('is-wakeup', 'is-standby'), true);
    }

    private static function buildTimelinePoint(DateTimeImmutable $eventDateTime, $stateClass, $label, $persistentOnline = false)
    {
        return array(
            'x' => $eventDateTime->format(DateTimeInterface::ATOM),
            'y' => $stateClass === 'is-wakeup' ? 1 : 0,
            'label' => $label,
            'timestamp' => $eventDateTime->format('d.m.Y H:i:s'),
            'persistentOnline' => (bool)$persistentOnline,
        );
    }

    private static function deduplicateTimelinePoints(array $points)
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
}
