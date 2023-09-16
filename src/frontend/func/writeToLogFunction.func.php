<?php
/**
 * Class for handling "write to log".
 *
 * @author: Guntmar Hoeche
 * @license: TBD
 */

require_once(__DIR__ . '/../../configuration.php');

class writeToLogFunction {
  public static function write_to_log($text, $source)
  {
    $format = "log"; // Possibilities: csv and txt
    date_default_timezone_set('Europe/Berlin');
    $datum_zeit = date("d.m.Y H:i:s");
    $monate = array(1 => "Januar", 2 => "Februar", 3 => "Maerz", 4 => "April", 5 => "Mai", 6 => "Juni", 7 => "Juli", 8 => "August", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Dezember");
    $monat = date("n");
    $jahr = date("Y");
    $dateiname = dirname(__FILE__) . "/../../logs/log_" . $monate[$monat] . "_$jahr.$format";
    $header = "Date                File     Log Info";
    $write_header = !file_exists($dateiname);
    if ($write_header) {
      error_log( print_r($header . "\r\n", true), 3, $dateiname );
    }
    error_log( print_r($datum_zeit . " " . basename($source) . ": ", true), 3, $dateiname );
    error_log( print_r($text, true), 3, $dateiname );
    error_log( print_r("\r\n", true), 3, $dateiname );
    return;
  }
}
