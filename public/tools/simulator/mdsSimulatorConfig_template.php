<?php
/**
 *
 * @author: Guntmar Höche
 * @license: TBD
 */

class mdsSimulatorConfig {
    public static $mdsDestination;
    public static $mdsDestinationdescription;

    public function __construct() {
        self::$mdsDestination[] = "https://localhost/webhooks/ttn.php";
        self::$mdsDestinationdescription[] = "localhost";

        self::$mdsDestination[] = "https://localhost2/webhooks/ttn.php";
        self::$mdsDestinationdescription[] = "localhost2";

    }
}
