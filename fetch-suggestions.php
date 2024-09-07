<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "./php/database.php";

$q = isset($_GET['q']) ? $_GET['q'] : '';

if ($q) {
    $stmt = $conn->prepare("SELECT DISTINCT nome_frase FROM frases WHERE LOWER(nome_frase) LIKE LOWER(?) AND id_tipo_elemento_sintatico = 14 LIMIT 10");
    $param = "%" . $q . "%";
    $stmt->bind_param('s', $param);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $suggestions = [];

        while ($row = $result->fetch_assoc()) {
            $suggestions[] = $row['nome_frase'];
        }

        echo json_encode($suggestions);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $stmt->error]);
    }

    $stmt->close();
}
$conn->close();
