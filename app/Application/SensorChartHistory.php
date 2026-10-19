<?php

final class SensorChartHistory
{
    // Existing device wall-clock timestamps follow the TTN ingestion timezone.
    public static function timestamp(array $row): ?int
    {
        $zone = new DateTimeZone('Europe/Berlin');
        $value = ($row['val_date'] ?? '') . ' ' . ($row['val_time'] ?? '');
        $date = DateTimeImmutable::createFromFormat('!d.m.Y H:i:s', $value, $zone);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date && (!$errors || (!$errors['warning_count'] && !$errors['error_count']))
            && $date->format('d.m.Y H:i:s') === $value) {
            return $date->getTimestamp();
        }
        // MySQL TIMESTAMP is an instant; never reinterpret its rendered wall time.
        return isset($row['receivedTimestamp']) && is_numeric($row['receivedTimestamp'])
            ? (int)$row['receivedTimestamp'] : null;
    }

    public static function summarize(iterable $rows, int $from, int $to, int $limit = 1000): array
    {
        // At most 14 representatives per bucket: endpoints, four min/max pairs,
        // and a missing-value representative for each channel. Never average away peaks.
        $width = max(1, (int)ceil(($to - $from) / max(1, intdiv($limit, 14))));
        $buckets = array();
        $raw = array();
        $count = 0;
        foreach ($rows as $row) {
            $timestamp = self::timestamp($row);
            if ($timestamp === null || $timestamp < $from || $timestamp > $to) {
                continue;
            }
            $row['timestamp'] = $timestamp * 1000;
            $row['measuredAt'] = gmdate('Y-m-d\TH:i:s\Z', $timestamp);
            $count++;
            if ($count <= $limit) {
                $raw[] = $row;
            } elseif ($count === $limit + 1) {
                $raw = array();
            }
            $key = min((int)(($timestamp - $from) / $width), max(0, (int)ceil(($to - $from) / $width) - 1));
            $bucket = $buckets[$key] ?? array();
            foreach (array('first', 'last') as $edge) {
                if (!isset($bucket[$edge]) || ($edge === 'first'
                    ? $timestamp < $bucket[$edge]['timestamp'] / 1000
                    : $timestamp >= $bucket[$edge]['timestamp'] / 1000)) {
                    $bucket[$edge] = $row;
                }
            }
            for ($channel = 1; $channel <= 4; $channel++) {
                $field = 'value' . $channel;
                $value = $row[$field] ?? null;
                if (!is_numeric($value)) {
                    $bucket['gap' . $channel] = $row;
                    continue;
                }
                foreach (array('min', 'max') as $extreme) {
                    $slot = $extreme . $channel;
                    if (!isset($bucket[$slot]) || ($extreme === 'min'
                        ? (float)$value < (float)$bucket[$slot][$field]
                        : (float)$value > (float)$bucket[$slot][$field])) {
                        $bucket[$slot] = $row;
                    }
                }
            }
            $buckets[$key] = $bucket;
        }
        $result = array();
        foreach ($buckets as $bucket) {
            foreach ($bucket as $row) {
                $result[$row['id']] = $row;
            }
        }
        if ($count <= $limit) {
            $result = $raw;
        }
        usort($result, static fn($a, $b) => ($a['timestamp'] <=> $b['timestamp']) ?: ($a['id'] <=> $b['id']));
        return $result;
    }
}
