<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "./php/database.php";

$stmt = $conn->prepare("SELECT nome_tipo_acao FROM tipos_acoes WHERE id_tipo_elemento_sintatico = 14");

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $phrases = [];

    while ($row = $result->fetch_assoc()) {
        $phrases[] = $row['nome_tipo_acao'];
    }

    echo json_encode($phrases);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
