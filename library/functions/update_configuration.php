<?php
session_start();
require_once __DIR__ . '/../../include/dataconnect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['di_id'])) {
    echo json_encode(["success"=>false,"error"=>"Invalid request"]);
    exit;
}

if (!isset($_SESSION['team_id'])) {
    echo json_encode(["success"=>false,"error"=>"Unauthorized"]);
    exit;
}

try {

    $stmt = $conn->prepare("
    UPDATE mg5_digisim
    SET di_priority_point = ?,
        di_scoring_logic = ?,
        di_scoring_basis = ?,
        di_total_basis = ?,
        di_result_type = ?
    WHERE di_id = ?
");

    $stmt->bind_param(
        "iiiiii",
        $data['priority'],
        $data['logic'],
        $data['basis'],
        $data['total'],
        $data['result'],
        $data['di_id']
    );

    $stmt->execute();
    $stmt->close();

    echo json_encode(["success"=>true]);

} catch(Exception $e) {
    echo json_encode([
        "success"=>false,
        "error"=>$e->getMessage()
    ]);
}