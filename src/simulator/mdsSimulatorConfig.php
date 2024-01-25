<?php
/**
 *
 * @author: Guntmar Höche
 * @license: TBD
 */

class mdsSimulatorConfig {
    static $mdsDestination;

    function __construct() {
        self::$mdsDestination[] = "https://mds-messe.derguntmar.de/receiver/ttndata/ttn.php";
        self::$mdsDestination[] = "https://mds-demo.derguntmar.de/receiver/ttndata/ttn.php";
        self::$mdsDestination[] = "https://mds-git.derguntmar.de/receiver/ttndata/ttn.php";
        self::$mdsDestination[] = "https://esp-data.derguntmar.de/receiver/ttndata/ttn.php";
    }
}
