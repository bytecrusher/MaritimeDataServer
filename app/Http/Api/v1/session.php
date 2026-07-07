<?php
declare(strict_types=1);

require_once __DIR__ . "/common.php";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "GET") {
    mds_api_error("method_not_allowed", "Use GET for session state.", 405);
}

$userObj = mds_api_current_user();

if ($userObj === false) {
    mds_api_json([
        "ok" => true,
        "authenticated" => false
    ]);
}

mds_api_json([
    "ok" => true,
    "authenticated" => true,
    "user" => [
        "id" => (int)$userObj->getId(),
        "email" => $userObj->getEmail(),
        "firstName" => $userObj->getFirstName(),
        "lastName" => $userObj->getLastName(),
        "timezone" => $userObj->getTimezone(),
        "isAdmin" => myFunctions::isUserAdmin((int)$userObj->getId())
    ]
]);
