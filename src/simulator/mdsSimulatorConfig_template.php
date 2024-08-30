<?php
/**
 *
 * @author: Guntmar Höche
 * @license: TBD
 */

class mdsSimulatorConfig {
    static $mdsDestination;
    static $mdsDestinationdescription;

    function __construct() {
        self::$mdsDestination[] = "https://localhost/receiver/ttndata/ttn.php";
        self::$mdsDestinationdescription[] = "localhost";

        self::$mdsDestination[] = "https://localhost2/receiver/ttndata/ttn.php";
        self::$mdsDestinationdescription[] = "localhost2";

    }
}
