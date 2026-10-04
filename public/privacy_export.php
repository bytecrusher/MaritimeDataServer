<?php

require_once dirname(__DIR__) . "/bootstrap/app.php";
mds_start_session();
require_once dirname(__DIR__) . "/app/Application/SettingsPageService.php";

$userObj = SettingsPageService::resolveCurrentUserFromSession();
if (!$userObj) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'Authentication required.'));
    exit();
}

$payload = SettingsPageService::buildPrivacyExportData($userObj);

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="mds-personal-data-export-' . date('Y-m-d') . '.json"');
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
