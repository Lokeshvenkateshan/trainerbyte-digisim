<?php
session_start();
require_once __DIR__ . '/../../include/dataconnect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['di_id']) || empty($data['injects'])) {
    echo json_encode(["success" => false, "error" => "Invalid data"]);
    exit;
}

if (!isset($_SESSION['team_id'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

$digisimId = intval($data['di_id']);
$injects = $data['injects'];

try {

    foreach ($injects as $inject) {

        $dmId = intval($inject['id']);
        $subject = trim($inject['subject']);
        $message = trim($inject['message']);
        $trigger = intval($inject['trigger']);   // ✅ FIXED — moved here

        $stmt = $conn->prepare("
            UPDATE mg5_digisim_message
            SET dm_subject = ?, 
                dm_message = ?,
                dm_trigger = ?
            WHERE dm_id = ? 
              AND dm_digisim_pkid = ?
        ");

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("ssiii", $subject, $message, $trigger, $dmId, $digisimId);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $stmt->close();
    }

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}