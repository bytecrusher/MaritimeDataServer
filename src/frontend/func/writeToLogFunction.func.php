<?php
/**
 * Class for handling "write to log".
 *
 * @author: Guntmar Hoeche
 * @license: TBD
 */

require_once(dirname(__FILE__) . "/../../configuration.php");

class writeToLogFunction {
  public static function write_to_log($text, $source)
  {
    $format = "log"; // Possibilities: csv and txt
    date_default_timezone_set('Europe/Berlin');
    $datum_zeit = date("d.m.Y H:i:s");
    $months = array(1 => "Januar", 2 => "Februar", 3 => "Maerz", 4 => "April", 5 => "Mai", 6 => "Juni", 7 => "Juli", 8 => "August", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Dezember");
    $month = date("n");
    $year = date("Y");
    $filename = dirname(__FILE__) . "/../../logs/log_" . $months[$month] . "_$year.$format";
    $header = "Date       Time     File           Log Info";
    $write_header = !file_exists($filename);
    if ($write_header) {
      error_log( print_r($header . "\r\n", true), 3, $filename );
    }
    error_log( print_r($datum_zeit . " " . basename($source) . ": ", true), 3, $filename );
    error_log( print_r($text, true), 3, $filename );
    error_log( print_r("\r\n", true), 3, $filename );
    return;
  }
}
