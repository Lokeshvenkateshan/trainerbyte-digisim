<?php
session_start();
require_once __DIR__ . '/../../include/dataconnect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['team_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$teamId = $_SESSION['team_id'];
$diId   = isset($_POST['di_id']) ? intval($_POST['di_id']) : 0;

if ($diId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid simulation ID"]);
    exit;
}

/* Validate ownership */
$stmt = $conn->prepare("
    SELECT d.di_id
    FROM mg5_digisim d
    INNER JOIN mg5_digisim_category c 
        ON d.di_digisim_category_pkid = c.lg_id
    WHERE d.di_id = ?
    AND c.lg_team_pkid = ?
    LIMIT 1
");
$stmt->bind_param("ii", $diId, $teamId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}
$stmt->close();

/* Collect form data */
$companyName = trim($_POST['company_name'] ?? '');
$title       = trim($_POST['title'] ?? '');
$intro       = trim($_POST['introduction'] ?? '');

if ($companyName === '' || $title === '' || $intro === '') {
    echo json_encode(["success" => false, "message" => "All fields required"]);
    exit;
}

/* Encode JSON */
$updatedJson = json_encode([
    "company_name" => $companyName,
    "title" => $title,
    "introduction" => $intro
], JSON_UNESCAPED_UNICODE);

/* Update DB */
$updateStmt = $conn->prepare("
    UPDATE mg5_digisim
    SET di_casestudy = ?
    WHERE di_id = ?
");
$updateStmt->bind_param("si", $updatedJson, $diId);
$updateStmt->execute();
$updateStmt->close();

echo json_encode(["success" => true]);
exit;