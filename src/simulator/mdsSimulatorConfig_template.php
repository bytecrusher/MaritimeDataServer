<?php
/**
 *
 * @author: Guntmar Höche
 * @license: TBD
 */

class mdsSimulatorConfig {
    static $mdsDestination;

    function __construct() {
        self::$mdsDestination[] = "https://localhost/receiver/ttndata/ttn.php";
        self::$mdsDestination[] = "https://localhost2/receiver/ttndata/ttn.php";
    }
}
