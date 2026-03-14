<?php
session_start();

define("SESSION_TIMEOUT", 7200);

if (!isset($_SESSION["team_id"])) {
    header("Location: login.php");
    exit;
}

if (isset($_SESSION["last_activity"]) &&
    (time() - $_SESSION["last_activity"] > SESSION_TIMEOUT)) {

    // Session expired
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}

// Update last activity time
$_SESSION["last_activity"] = time();
