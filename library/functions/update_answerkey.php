<?php
session_start();
require_once __DIR__ . '/../../include/dataconnect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['di_id'])) {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit;
}

if (!isset($_SESSION['team_id'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

$digisimId = intval($data['di_id']);
$content = trim($data['content']);

try {

    $stmt = $conn->prepare("
        UPDATE mg5_digisim
        SET di_answerkey = ?
        WHERE di_id = ?
    ");

    $stmt->bind_param("si", $content, $digisimId);

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $stmt->close();

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}