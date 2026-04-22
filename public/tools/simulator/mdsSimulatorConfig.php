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
        #self::$mdsDestination[] = "https://localhost/webhooks/ttn.php";
        #self::$mdsDestinationdescription[] = "localhost";

        #self::$mdsDestination[] = "https://localhost2/webhooks/ttn.php";
        #self::$mdsDestinationdescription[] = "localhost2";

        self::$mdsDestination[] = "http://localhost/maritimedataserver/webhooks/ttn.php";
        self::$mdsDestinationdescription[] = "localhost";

        self::$mdsDestination[] = "http://172.21.0.3/maritimedataserver/webhooks/ttn.php";
        self::$mdsDestinationdescription[] = "Docker";

        self::$mdsDestination[] = "https://mds-git.derguntmar.de/webhooks/ttn.php";
        self::$mdsDestinationdescription[] = "Netcup Git";

        self::$mdsDestination[] = "https://mds-demo.derguntmar.de/webhooks/ttn.php";
        self::$mdsDestinationdescription[] = "Netcup Demo";

        self::$mdsDestination[] = "https://mds-beta.derguntmar.de/webhooks/ttn.php";
        self::$mdsDestinationdescription[] = "Netcup beta";
    }
}
