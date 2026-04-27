<?php
// Check if Session exist and user is logged in.
require_once dirname(__DIR__, 3) . "/bootstrap/app.php";
mds_start_session();
header('Content-Type: application/json');

if (isset($_SESSION['userId']) && ((int)$_SESSION['userId'] > 0)) {
    echo json_encode(true);
} else {
    echo json_encode(false);
}
