<?php
ob_start();
header('Content-Type: application/json');

include "database.php";

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Get POST data
    $nomeEvidencia = $_POST['nome-atividade'];
    $tipoAtividade = $_POST['tipo-atividade'];
    $data = $_POST['data'];
    $atividadeRealizada = $_POST['atividade-realizada'];
    $longitude = $_POST['longitude'];
    $latitude = $_POST['latitude'];

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO evidencias (nome_evidencia, data, longitude, latitude, atividade_realizada) VALUES (?, ?, ?, ?, ?)");

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    // Bind parameters to the SQL statement
    $stmt->bind_param('ssdds', $nomeEvidencia, $data, $longitude, $latitude, $atividadeRealizada);

    // Execute the statement
    if ($stmt->execute()) {
        // Handle file uploads
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $uploadDir = '/var/www/html/uploads/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            function handleFileUploads($fileInputName)
            {
                global $uploadDir;

                if (isset($_FILES[$fileInputName]) && is_array($_FILES[$fileInputName]['name'])) {
                    foreach ($_FILES[$fileInputName]['name'] as $key => $name) {
                        if ($_FILES[$fileInputName]['error'][$key] == UPLOAD_ERR_OK) {
                            $fileTmpPath = $_FILES[$fileInputName]['tmp_name'][$key];
                            $fileName = basename($name);
                            $filePath = $uploadDir . $fileName;

                            if (!move_uploaded_file($fileTmpPath, $filePath)) {
                                throw new Exception('Error moving file "' . htmlspecialchars($fileName) . '" to destination.');
                            }
                        } else {
                            throw new Exception('Error uploading file "' . htmlspecialchars($name) . '": ' . getUploadErrorMessage($_FILES[$fileInputName]['error'][$key]));
                        }
                    }
                }
            }

            function getUploadErrorMessage($errorCode)
            {
                switch ($errorCode) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        return 'File size exceeds the allowed limit.';
                    case UPLOAD_ERR_PARTIAL:
                        return 'File was only partially uploaded.';
                    case UPLOAD_ERR_NO_FILE:
                        return 'No file was uploaded.';
                    default:
                        return 'Unknown upload error.';
                }
            }

            handleFileUploads('files');
        }

        // Set success response
        $response['success'] = true;
        $response['message'] = 'Data submitted successfully';
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
} catch (Exception $e) {
    // Set error response
    $response['message'] = $e->getMessage();
}

// Send JSON response
echo json_encode($response);

// Close the statement and connection
$stmt->close();
$conn->close();

// End output buffering and flush
ob_end_flush();
