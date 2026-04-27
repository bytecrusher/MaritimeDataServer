<?php
/**
 * Central logging helper for the project.
 *
 * @author: Guntmar Hoeche
 * @license: TBD
 */

class writeToLogFunction
{
    private static $handlersRegistered = false;

    public static function registerGlobalHandlers()
    {
        if (self::$handlersRegistered) {
            return;
        }

        self::$handlersRegistered = true;

        set_error_handler(function ($severity, $message, $file, $line) {
            $errorMessage = sprintf('%s in %s:%d', $message, $file, $line);
            self::logBySeverity($severity, $errorMessage, $file);
            return false;
        });

        set_exception_handler(function ($throwable) {
            self::exception($throwable, $throwable->getFile());
        });

        register_shutdown_function(function () {
            $lastError = error_get_last();
            if ($lastError === null) {
                return;
            }

            $fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
            if (in_array($lastError['type'], $fatalTypes, true)) {
                $message = sprintf(
                    'Fatal shutdown error: %s in %s:%d',
                    $lastError['message'],
                    $lastError['file'],
                    $lastError['line']
                );
                self::error($message, $lastError['file']);
            }
        });
    }

    public static function write_to_log($text, $source, $level = 'INFO', array $context = array())
    {
        self::registerGlobalHandlers();

        date_default_timezone_set('Europe/Berlin');
        $datumZeit = date('d.m.Y H:i:s');
        $months = array(
            1 => 'Januar',
            2 => 'Februar',
            3 => 'Maerz',
            4 => 'April',
            5 => 'Mai',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'August',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Dezember'
        );
        $month = date('n');
        $year = date('Y');
        $logDirectory = dirname(__FILE__, 4) . '/var/log';
        if (!is_dir($logDirectory)) {
            @mkdir($logDirectory, 0775, true);
        }

        if (!is_dir($logDirectory) || !is_writable($logDirectory)) {
            error_log(
                sprintf(
                    '[MDS][%s] Logging directory is not writable: %s',
                    strtoupper((string)$level),
                    $logDirectory
                )
            );
            return;
        }
        $filename = $logDirectory . '/log_' . $months[$month] . '_' . $year . '.log';
        $header = 'Date Time Level Source Message';

        if (!file_exists($filename)) {
            error_log($header . PHP_EOL, 3, $filename);
        }

        $normalizedSource = self::normalizeSource($source);
        $context = self::buildContext($context);
        $message = self::stringify($text);
        $line = sprintf(
            '[%s] [%s] [%s] %s%s',
            $datumZeit,
            strtoupper((string)$level),
            $normalizedSource,
            $message,
            self::formatContext($context)
        );

        error_log($line . PHP_EOL, 3, $filename);
    }

    public static function info($text, $source, array $context = array())
    {
        self::write_to_log($text, $source, 'INFO', $context);
    }

    public static function debug($text, $source, array $context = array())
    {
        self::write_to_log($text, $source, 'DEBUG', $context);
    }

    public static function warning($text, $source, array $context = array())
    {
        self::write_to_log($text, $source, 'WARNING', $context);
    }

    public static function error($text, $source, array $context = array())
    {
        self::write_to_log($text, $source, 'ERROR', $context);
    }

    public static function exception($throwable, $source, array $context = array())
    {
        $payload = array_merge(
            $context,
            array(
                'exceptionClass' => get_class($throwable),
                'exceptionMessage' => $throwable->getMessage(),
                'exceptionFile' => $throwable->getFile(),
                'exceptionLine' => $throwable->getLine(),
            )
        );

        self::write_to_log($throwable->getTraceAsString(), $source, 'EXCEPTION', $payload);
    }

    private static function logBySeverity($severity, $message, $source)
    {
        $level = 'ERROR';
        if (in_array($severity, array(E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED), true)) {
            $level = 'NOTICE';
        } elseif (in_array($severity, array(E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING), true)) {
            $level = 'WARNING';
        }

        self::write_to_log($message, $source, $level);
    }

    private static function normalizeSource($source)
    {
        if (is_string($source) && $source !== '') {
            return basename($source);
        }

        if (isset($_SERVER['SCRIPT_FILENAME']) && $_SERVER['SCRIPT_FILENAME'] !== '') {
            return basename($_SERVER['SCRIPT_FILENAME']);
        }

        return 'unknown-source';
    }

    private static function buildContext(array $context)
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $context['requestMethod'] = $_SERVER['REQUEST_METHOD'];
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $context['requestUri'] = $_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $context['remoteAddr'] = self::maskRemoteAddress((string)$_SERVER['REMOTE_ADDR']);
        }

        return $context;
    }

    private static function formatContext(array $context)
    {
        if (empty($context)) {
            return '';
        }

        return ' | context=' . self::stringify($context);
    }

    private static function stringify($value)
    {
        if ($value instanceof Throwable) {
            return $value->getMessage();
        }

        if (is_scalar($value) || $value === null) {
            return (string)$value;
        }

        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json !== false) {
            return $json;
        }

        return print_r($value, true);
    }

    private static function maskRemoteAddress($ipAddress)
    {
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ipAddress);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ipAddress);
            $maskedParts = array_slice($parts, 0, 4);
            while (count($maskedParts) < 8) {
                $maskedParts[] = '0000';
            }
            return implode(':', $maskedParts);
        }

        return $ipAddress;
    }
}

writeToLogFunction::registerGlobalHandlers();
