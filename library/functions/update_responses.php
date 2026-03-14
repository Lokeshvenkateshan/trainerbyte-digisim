<?php
session_start();
require_once __DIR__ . '/../../include/dataconnect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['di_id']) || empty($data['responses'])) {
    echo json_encode(["success" => false, "error" => "Invalid data"]);
    exit;
}

if (!isset($_SESSION['team_id'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

$digisimId = intval($data['di_id']);
$responses = $data['responses'];

try {

    foreach ($responses as $resp) {

        $drId = intval($resp['id']);
        $statement = trim($resp['statement']);
        $score = intval($resp['score']);

        $stmt = $conn->prepare("
            UPDATE mg5_digisim_response
            SET dr_tasks = ?, 
                dr_score_pkid = ?
            WHERE dr_id = ?
              AND dr_digisim_pkid = ?
        ");

        $stmt->bind_param("siii", $statement, $score, $drId, $digisimId);

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