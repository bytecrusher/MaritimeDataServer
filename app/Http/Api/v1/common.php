<?php
declare(strict_types=1);

require_once dirname(__DIR__, 4) . "/bootstrap/app.php";
require_once dirname(__DIR__, 4) . "/app/Infrastructure/Database/dbConfig.func.php";
require_once dirname(__DIR__, 4) . "/app/Application/myFunctions.func.php";
require_once dirname(__DIR__, 4) . "/app/Domain/User/user.class.php";

mds_start_session();

function mds_api_json($payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store");
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function mds_api_error(string $code, string $message, int $statusCode): void
{
    mds_api_json([
        "ok" => false,
        "error" => [
            "code" => $code,
            "message" => $message
        ]
    ], $statusCode);
}

function mds_api_request_data(): array
{
    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
    if (stripos($contentType, "application/json") !== false) {
        $rawBody = file_get_contents("php://input");
        $json = json_decode($rawBody ?: "{}", true);
        return is_array($json) ? $json : [];
    }

    if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "GET") {
        return $_GET;
    }

    return $_POST;
}

function mds_api_current_user()
{
    if (!isset($_SESSION["userObj"])) {
        return false;
    }

    $userObj = unserialize($_SESSION["userObj"]);
    return $userObj ?: false;
}

function mds_api_require_user()
{
    $userObj = mds_api_current_user();
    if ($userObj === false) {
        mds_api_error("not_authenticated", "No active MDS session.", 401);
    }
    return $userObj;
}

function mds_api_bool($value): bool
{
    return (string)$value === "1" || $value === true;
}

function mds_api_float_or_null($value): ?float
{
    if ($value === null || $value === "") {
        return null;
    }
    return is_numeric($value) ? (float)$value : null;
}
