<?php
function mdsInstallConfigPath() {
  return __DIR__ . '/../config/config.json';
}

function mdsInstallTemplatePath() {
  return __DIR__ . '/../config/config_template.json';
}

function mdsReadInstallConfig() {
  $path = mdsInstallConfigPath();
  if (!file_exists($path)) {
    $path = mdsInstallTemplatePath();
  }

  $jsonString = file_get_contents($path);
  $jsonData = json_decode($jsonString, true);

  if (!is_array($jsonData)) {
    return array();
  }

  return $jsonData;
}

function mdsWriteInstallConfig($jsonData) {
  $path = mdsInstallConfigPath();
  if (!file_exists($path)) {
    copy(mdsInstallTemplatePath(), $path);
  }

  $jsonString = json_encode($jsonData, JSON_PRETTY_PRINT);
  $fp = fopen($path, 'w');
  fwrite($fp, $jsonString);
  fclose($fp);
}

function mdsInstallFinished($jsonData = null) {
  if ($jsonData === null) {
    $jsonData = mdsReadInstallConfig();
  }

  if (!array_key_exists('installFinished', $jsonData)) {
    return false;
  }

  return filter_var($jsonData['installFinished'], FILTER_VALIDATE_BOOLEAN);
}

function mdsAbortIfInstallFinished($jsonResponse = false) {
  if (!mdsInstallFinished()) {
    return;
  }

  http_response_code(403);
  header('Cache-Control: no-store');

  if ($jsonResponse) {
    header('Content-Type: application/json');
    print json_encode(array('error' => 'true', 'error_text' => 'Installer is disabled because installation is already complete.'));
  } else {
    print 'Installer is disabled because installation is already complete.';
  }

  exit;
}
