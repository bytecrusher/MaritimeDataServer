<?php
/**
 * Shared checks for installer endpoints.
 */
function installer_config_value_is_true($value) {
  if (is_bool($value)) {
    return $value;
  }

  if (is_string($value)) {
    $normalizedValue = strtolower(trim($value));
    return in_array($normalizedValue, array('1', 'true', 'yes', 'on'), true);
  }

  return $value === 1;
}

function installer_config_is_finished($path) {
  if (!file_exists($path)) {
    return false;
  }

  $jsonString = file_get_contents($path);
  $jsonData = json_decode($jsonString, true);
  if (!is_array($jsonData) || !array_key_exists('installFinished', $jsonData)) {
    return false;
  }

  return installer_config_value_is_true($jsonData['installFinished']);
}

function installer_is_finished() {
  $configPaths = array(
    __DIR__ . '/../config/config.json',
    __DIR__ . '/../config.json'
  );

  foreach ($configPaths as $path) {
    if (installer_config_is_finished($path)) {
      return true;
    }
  }

  return false;
}

function installer_block_if_finished($jsonResponse = false) {
  if (!installer_is_finished()) {
    return;
  }

  http_response_code(403);
  if ($jsonResponse) {
    header('Content-Type: application/json');
    print json_encode(array('error'=>'true', 'error_text'=>'Installation has already been completed.'));
  } else {
    print 'Installation has already been completed.';
  }
  exit();
}
