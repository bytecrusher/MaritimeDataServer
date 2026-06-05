<?php

function mds_install_config_path()
{
    return dirname(__DIR__, 2) . '/config/config.json';
}

function mds_install_is_finished()
{
    $configPath = mds_install_config_path();
    if (!file_exists($configPath)) {
        return false;
    }

    $jsonString = file_get_contents($configPath);
    $jsonData = json_decode((string)$jsonString, true);
    if (!is_array($jsonData)) {
        return false;
    }

    return filter_var($jsonData['installFinished'] ?? false, FILTER_VALIDATE_BOOLEAN);
}

function mds_deny_finished_install($jsonResponse = false)
{
    if (!mds_install_is_finished()) {
        return;
    }

    http_response_code(403);
    if ($jsonResponse) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => true, 'error_text' => 'Installation is locked.'));
    } else {
        echo 'Installation is locked.';
    }
    exit;
}
