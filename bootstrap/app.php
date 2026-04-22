<?php

$projectRoot = dirname(__DIR__);
$vendorAutoload = $projectRoot . '/vendor/autoload.php';

if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

require_once $projectRoot . '/app/Infrastructure/Logging/writeToLogFunction.func.php';
require_once $projectRoot . '/app/Infrastructure/Config/configuration.php';
require_once $projectRoot . '/app/Support/url_helpers.php';
require_once $projectRoot . '/app/Domain/Board/get_data.php';
