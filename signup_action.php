<?php
session_start();
header("Content-Type: application/json");
include("include/dataconnect.php");

$team_name  = trim($_POST["team_name"] ?? "");
$team_login = trim($_POST["team_login"] ?? "");
$team_org   = trim($_POST["team_org"] ?? "");
$password   = trim($_POST["team_password"] ?? "");
$confirm    = trim($_POST["confirm_password"] ?? "");

if ($password !== $confirm) {
    echo json_encode(["status" => "error", "message" => "Passwords do not match"]);
    exit;
}

$encoded_password = base64_encode($password);
$team_creatby = 1;

// check email
$check = $conn->prepare("SELECT team_id FROM mg5_team WHERE team_login = ?");
$check->bind_param("s", $team_login);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email already exists"]);
    exit;
}



$stmt = $conn->prepare("
    INSERT INTO mg5_team (team_name, team_login, team_org, team_password, team_creatby)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssi",
    $team_name,
    $team_login,
    $team_org,
    $encoded_password,
    $team_creatby
);


if ($stmt->execute()) {

    $team_id = $stmt->insert_id;
    // LOGIN AFTER SIGNUP
    $_SESSION["team_id"] = $stmt->insert_id;
    $_SESSION["team_name"] = $team_name;
    $_SESSION["last_activity"] = time();


    $catStmt = $conn->prepare("
        INSERT INTO mg5_digisim_category
        (lg_team_pkid, lg_name, lg_description, lg_status, createddate)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $lg_description = ""; // optional / empty
    $lg_status = 1;

    $catStmt->bind_param(
        "issi",
        $team_id,
        $team_org,
        $lg_description,
        $lg_status
    );

    $catStmt->execute();

    echo json_encode([
        "status" => "success",
        "message" => "Signup successful. Redirecting..."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Signup failed"
    ]);
}
