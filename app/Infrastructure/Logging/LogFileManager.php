<?php

class LogFileManager
{
    public const DEFAULT_MAX_FILE_SIZE_MB = 10;
    public const DEFAULT_RETENTION_DAYS = 90;
    public const MAINTENANCE_INTERVAL_SECONDS = 86400;

    public static function append(
        $logDirectory,
        $filename,
        $header,
        $line,
        $maxFileSizeBytes,
        $retentionDays,
        $now = null
    ) {
        $now = $now === null ? time() : (int)$now;
        $maxFileSizeBytes = max(1024, (int)$maxFileSizeBytes);
        $retentionDays = max(1, (int)$retentionDays);
        if (!self::prepareDirectory($logDirectory)) {
            return array('success' => false, 'rotated' => false, 'deletedFiles' => 0);
        }

        $lockHandle = @fopen(rtrim($logDirectory, DIRECTORY_SEPARATOR) . '/.log-manager.lock', 'c');
        if ($lockHandle === false || !flock($lockHandle, LOCK_EX)) {
            if (is_resource($lockHandle)) {
                fclose($lockHandle);
            }
            return array('success' => false, 'rotated' => false, 'deletedFiles' => 0);
        }

        try {
            $deletedFiles = self::runScheduledCleanup($logDirectory, $retentionDays, $now);
            $targetFile = rtrim($logDirectory, DIRECTORY_SEPARATOR) . '/' . basename((string)$filename);
            $line = self::truncateLine((string)$line, (string)$header, $maxFileSizeBytes);
            $payloadBytes = strlen((string)$line) + 1;
            $existingBytes = is_file($targetFile) ? (int)filesize($targetFile) : 0;
            $rotated = false;

            if ($existingBytes > 0 && ($existingBytes + $payloadBytes) > $maxFileSizeBytes) {
                $archiveFile = self::nextArchiveFilename($targetFile, $now);
                $rotated = @rename($targetFile, $archiveFile);
                if (!$rotated) {
                    return array('success' => false, 'rotated' => false, 'deletedFiles' => $deletedFiles);
                }
            }

            $writeHeader = !is_file($targetFile) || (int)filesize($targetFile) === 0;
            $payload = ($writeHeader ? rtrim((string)$header) . PHP_EOL : '')
                . rtrim((string)$line)
                . PHP_EOL;
            $written = @file_put_contents($targetFile, $payload, FILE_APPEND | LOCK_EX);

            return array(
                'success' => $written !== false,
                'rotated' => $rotated,
                'deletedFiles' => $deletedFiles,
            );
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    public static function cleanup($logDirectory, $retentionDays, $now = null)
    {
        $now = $now === null ? time() : (int)$now;
        $retentionDays = max(1, (int)$retentionDays);
        if (!self::prepareDirectory($logDirectory)) {
            return array('deletedFiles' => 0, 'deletedBytes' => 0, 'failedFiles' => 0);
        }

        $lockHandle = @fopen(rtrim($logDirectory, DIRECTORY_SEPARATOR) . '/.log-manager.lock', 'c');
        if ($lockHandle === false || !flock($lockHandle, LOCK_EX)) {
            if (is_resource($lockHandle)) {
                fclose($lockHandle);
            }
            return array('deletedFiles' => 0, 'deletedBytes' => 0, 'failedFiles' => 1);
        }

        try {
            $result = self::cleanupUnlocked($logDirectory, $retentionDays, $now);
            self::markMaintenanceRun($logDirectory, $now);
            return $result;
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    private static function runScheduledCleanup($logDirectory, $retentionDays, $now)
    {
        $marker = rtrim($logDirectory, DIRECTORY_SEPARATOR) . '/.log-maintenance';
        $lastRun = is_file($marker) ? @filemtime($marker) : false;
        if ($lastRun !== false && $lastRun > ($now - self::MAINTENANCE_INTERVAL_SECONDS)) {
            return 0;
        }

        $result = self::cleanupUnlocked($logDirectory, $retentionDays, $now);
        self::markMaintenanceRun($logDirectory, $now);
        return (int)$result['deletedFiles'];
    }

    private static function cleanupUnlocked($logDirectory, $retentionDays, $now)
    {
        $cutoffTimestamp = $now - (max(1, (int)$retentionDays) * 86400);
        $result = array('deletedFiles' => 0, 'deletedBytes' => 0, 'failedFiles' => 0);
        foreach (glob(rtrim($logDirectory, DIRECTORY_SEPARATOR) . '/*.log') ?: array() as $logFile) {
            if (!is_file($logFile)) {
                continue;
            }
            $modifiedAt = @filemtime($logFile);
            if ($modifiedAt === false || $modifiedAt >= $cutoffTimestamp) {
                continue;
            }

            $bytes = (int)(@filesize($logFile) ?: 0);
            if (@unlink($logFile)) {
                $result['deletedFiles']++;
                $result['deletedBytes'] += $bytes;
            } else {
                $result['failedFiles']++;
            }
        }
        return $result;
    }

    private static function markMaintenanceRun($logDirectory, $now)
    {
        $marker = rtrim($logDirectory, DIRECTORY_SEPARATOR) . '/.log-maintenance';
        @file_put_contents($marker, (string)$now, LOCK_EX);
        @touch($marker, $now);
    }

    private static function nextArchiveFilename($targetFile, $now)
    {
        $directory = dirname($targetFile);
        $baseName = pathinfo($targetFile, PATHINFO_FILENAME);
        $timestamp = date('Ymd_His', $now);
        $candidate = $directory . '/' . $baseName . '_' . $timestamp . '.log';
        $suffix = 1;
        while (file_exists($candidate)) {
            $candidate = $directory . '/' . $baseName . '_' . $timestamp . '_' . $suffix++ . '.log';
        }
        return $candidate;
    }

    private static function truncateLine($line, $header, $maxFileSizeBytes)
    {
        $marker = ' [log entry truncated]';
        $availableBytes = max(1, (int)$maxFileSizeBytes - strlen(rtrim($header)) - strlen($marker) - 2);
        if (strlen($line) <= $availableBytes + strlen($marker)) {
            return $line;
        }

        $truncated = function_exists('mb_strcut')
            ? mb_strcut($line, 0, $availableBytes, 'UTF-8')
            : substr($line, 0, $availableBytes);
        return rtrim((string)$truncated) . $marker;
    }

    private static function prepareDirectory($logDirectory)
    {
        if (!is_dir($logDirectory) && !@mkdir($logDirectory, 0775, true) && !is_dir($logDirectory)) {
            return false;
        }
        return is_writable($logDirectory);
    }
}
