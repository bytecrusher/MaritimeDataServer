<?php
declare(strict_types=1);

require_once __DIR__ . "/common.php";
require_once dirname(__DIR__, 4) . "/app/Application/dbUpdateData.php";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    mds_api_error("method_not_allowed", "Use POST for login.", 405);
}

$data = mds_api_request_data();
$email = trim((string)($data["email"] ?? ""));
$password = (string)($data["password"] ?? "");
$remember = isset($data["remember"]) ? mds_api_bool($data["remember"]) : true;

if ($email === "" || $password === "") {
    mds_api_error("missing_credentials", "Email and password are required.", 400);
}

$userObj = new user($email);

if ($userObj->getError() === "42S02") {
    mds_api_error("not_installed", "MDS database tables do not exist. Please run install.", 503);
}

if ($userObj->userExist() === false || $userObj->isActive() === false || !password_verify($password, $userObj->getPassword())) {
    mds_api_error("invalid_credentials", "Email or password is invalid.", 401);
}

$_SESSION["userObj"] = serialize($userObj);
$_SESSION["userId"] = $userObj->getId();

if ($remember) {
    dbUpdateData::insertSecurityToken($userObj->getId());
}

mds_api_json([
    "ok" => true,
    "user" => [
        "id" => (int)$userObj->getId(),
        "email" => $userObj->getEmail(),
        "firstName" => $userObj->getFirstName(),
        "lastName" => $userObj->getLastName(),
        "timezone" => $userObj->getTimezone(),
        "isAdmin" => myFunctions::isUserAdmin((int)$userObj->getId())
    ]
]);
