<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');

include "database.php";
include "handleDecrypt.php";

$response = ['success' => false, 'message' => ''];
function log_request_data()
{
    $logData = [
        'POST' => $_POST,
        'FILES' => $_FILES
    ];

    $logDataJson = json_encode($logData, JSON_PRETTY_PRINT);
    error_log($logDataJson);
}

// log_request_data();



try {
    $lon = $_POST['longitude'];
    $lat = $_POST['latitude'];

    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lon";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: MyCustomUserAgent/1.0',
        'Accept-Language: pt'
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        error_log("cURL Error: " . $error_msg);
        die('Error occurred: ' . $error_msg);
    }

    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_status !== 200) {
        error_log("HTTP Error: " . $http_status);
        die('Error occurred: HTTP Status ' . $http_status);
    }

    curl_close($ch);

    $mapData = json_decode($response, true);
    $nome_usuario = $_POST['nome-usuario'];
    $nomeAtividadeEvento = $_POST['nome-atividade'];
    $nomeTipoAcao = $_POST['tipo-atividade'];
    $data = $_POST['data'];
    $descricao = $_POST['atividade-realizada'];
    $dataAcao = $_POST['data-acao'];
    $horaAcao = $_POST['hora-acao'];
    $bounding_box_lat_min = $mapData['boundingbox'][0];
    $bounding_box_lat_max = $mapData['boundingbox'][1];
    $bounding_box_long_min = $mapData['boundingbox'][2];
    $bounding_box_long_max = $mapData['boundingbox'][3];
    $neighbourhood = $mapData['address']['municipality'];
    $country = $mapData['address']['country'];
    $road = $mapData['address']['road'];
    $city = $mapData['address']['city'];
    $state = $mapData['address']['state'];
    $postCode = $mapData['address']['postcode'];
    $countryCode = strtoupper($mapData['address']['country_code']);
    $suburb = $mapData['address']['suburb'];
    $dislayName = $mapData['display_name'];
    $address = $mapData['address'];
    $id_pais = 0;
    $id_estado = 0;
    $nome_estado = '';
    $id_cidade = 0;
    $codigo_ibge = 0;
    $id_localizacao = 0;
    $id_endereco = 0;
    $id_atividade_evento = 0;
    $id_arquivo = 0;
    $id_tipo_arquivo = 0;
    $id_tipo_acao = 0;
    $id_pessoa = 0;


    $stmt = $conn->prepare("SELECT id_chave_pessoa FROM pessoas WHERE nome_pessoa = ?");

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('s', $nome_usuario);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);

        if (!empty($data)) {
            $id_pessoa = $data[0]['id_chave_pessoa'];
        }
    } else {
        $response['message'] = $e->getMessage();
    }

    $stmt = $conn->prepare("SELECT id_chave_pais FROM paises where nome_pais = ?");
    $stmt->bind_param('s', $country);

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }


    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);

        if (!empty($data)) {
            $id_pais = $data[0]['id_chave_pais'];
        }
    } else {
        $response['message'] = $e->getMessage();
    }

    $stmt = $conn->prepare("SELECT id_chave_estado, nome_estado FROM estados WHERE nome_estado =  ?");
    $stmt->bind_param('s', $state);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);

        if (!empty($data)) {
            $id_estado = $data[0]['id_chave_estado'];
            $nome_estado = $data[0]['nome_estado'];
        }
    } else {
        $error_message = 'Query failed: ' . $stmt->error;
        error_log($error_message);
        echo json_encode(['error' => $error_message]);
    }

    $stmt = $conn->prepare("SELECT * FROM cidades WHERE id_estado =  ? AND nome_cidade = ?");

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('is', $id_estado, $city);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        $data = $result->fetch_all(MYSQLI_ASSOC);
        if (!empty($data)) {
            $id_cidade = $data[0]['id_chave_cidade'];
        }
    } else {
        error_log('Query failed: ' . $e->getMessage());
    }


    $stmt = $conn->prepare("INSERT INTO localizacoes (latitude, longitude, bounding_box_lat_min, bounding_box_lat_max, bounding_box_long_min, bounding_box_long_max, display_name, road, neighbourhood, suburb, city, state, postcode, country, country_code, id_cidade, id_estado, id_pais) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('ddddddsssssssssiii', $lon, $lat, $bounding_box_lat_min, $bounding_box_lat_max, $bounding_box_long_min, $bounding_box_long_max, $dislayName, $road, $neighbourhood, $suburb, $city, $state, $postCode, $country, $countryCode, $id_cidade, $id_estado, $id_pais);

    if ($stmt->execute()) {
        $id_localizacao = $conn->insert_id;
    } else {
        error_log('Query failed: ' . $e->getMessage());
    }


    $stmt = $conn->prepare("INSERT INTO enderecos (id_localizacao) VALUES (?)");

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $id_localizacao);

    if ($stmt->execute()) {
        $id_endereco = $conn->insert_id;
    } else {
        error_log('Query failed: ' . $e->getMessage());
    }
    $stmt = $conn->prepare("INSERT INTO atividades_eventos (nome_atividade_evento, data_atividade_evento, hora_atividade_evento) VALUES (?,?,?)");

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('sss', $nomeAtividadeEvento, $dataAcao, $horaAcao);

    if ($stmt->execute()) {
        $id_atividade_evento = $conn->insert_id;
    } else {
        error_log('Query failed: ' . $e->getMessage());
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $uploadDir = '/var/www/html/uploads/';

        // Create the upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }


        try {
            // Check if the uploads directory exists
            function handleFileUploads($fileInputName)
            {
                global $fileExtension;
                global $quantidade_pessoas;
                global $caminho_arquivo_anonimizado;
                global $caminho_arquivo_original;
                global $nome_arquivo;
                if (isset($_FILES[$fileInputName]) && is_array($_FILES[$fileInputName]['name'])) {
                    foreach ($_FILES[$fileInputName]['name'] as $key => $name) {
                        if ($_FILES[$fileInputName]['error'][$key] == UPLOAD_ERR_OK) {
                            file_put_contents('output.json', '');

                            // Get the temporary file path of the uploaded file
                            $fileTmpPath = $_FILES[$fileInputName]['tmp_name'][$key];
                            $fileExtension = pathinfo($name, PATHINFO_EXTENSION);
                            $fileName = uniqid(rand(), true) . '.' . $fileExtension; // Generate a random filename
                            // Define the destination path in the input directory
                            $destinationPath = "./input/" . $fileName;

                            // Move the uploaded file to the input directory
                            if (!move_uploaded_file($fileTmpPath, $destinationPath)) {
                                throw new Exception('Error moving uploaded file.');
                            }

                            // Construct the command to execute the Python script with the image path
                            $command = escapeshellcmd("/var/www/html/venv/bin/python /var/www/html/php/pasteur_demo.py") . ' ' . escapeshellarg($destinationPath);

                            // Execute the command and capture the output
                            $output = [];
                            $returnVar = 0;
                            exec($command, $output, $returnVar);

                            $output = file_get_contents('output.json');

                            if ($output === false) {
                                error_log("Failed to read output.json");
                            } else {
                                // Decode the JSON into an associative array
                                $jsonOutput = json_decode($output, true);
                                error_log(json_encode($jsonOutput));
                                $quantidade_pessoas = intval($jsonOutput['quantidade_pessoas']);
                                $caminho_arquivo_anonimizado = $jsonOutput['caminho_arquivo_anonimizado'];
                                $encryptKey = "tjd3s_secret_key";
                                $iv = "tjd3s_initialization_vector";
                                $caminho_arquivo_original = handleDecrypt(
                                    $jsonOutput['caminho_arquivo_original'],
                                    $encryptKey,
                                    $iv
                                );
                                $nome_arquivo = $jsonOutput['nome_arquivo'];
                                // Check if decoding was successful
                                if (json_last_error() !== JSON_ERROR_NONE) {
                                    error_log("JSON Decode Error: " . json_last_error_msg());
                                } else {
                                    // Convert the associative array back into a JSON string
                                    $jsonString = json_encode($jsonOutput);

                                    // Log the JSON string
                                    error_log("Processed JSON Output: " . $jsonString);
                                }
                                // file_put_contents('output.json', '');
                            }



                            error_log("Command executed: $command");
                            // error_log("Output: " . implode("\n", $output));
                            error_log("Return code: $returnVar");

                            if ($returnVar !== 0) {
                                error_log("Error: Python script execution failed.");
                                // error_log("Output: " . implode("\n", $output));
                            } else {
                                error_log("Python script executed successfully.");
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
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    $extensao  = '.' . $fileExtension;
    $stmt = $conn->prepare("SELECT id_chave_tipo_arquivo FROM tipos_arquivos WHERE extensao = ?");

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('s', $extensao);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        $data = $result->fetch_all(MYSQLI_ASSOC);

        if (!empty($data)) {
            $id_tipo_arquivo = $data[0]['id_chave_tipo_arquivo'];
        }
    } else {
        error_log('Query failed: ' . $e->getMessage());
    }

    error_log("caminho_arquivo_anonimizado: " . $caminho_arquivo_anonimizado);
    error_log("caminho_arquivo_original: " . $caminho_arquivo_original);
    error_log("quantidade_pessoas: " . $quantidade_pessoas);
    error_log("nome_arquivo: " . $nome_arquivo);
    $stmt = $conn->prepare("INSERT INTO arquivos (nome_arquivo, id_tipo_arquivo, caminho_arquivo_original, quantidade_pessoas, caminho_arquivo_anonimizado) VALUES (?, ?, ?, ?, ?)");

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('sisis', $nome_arquivo, $id_tipo_arquivo, $caminho_arquivo_original, $quantidade_pessoas, $caminho_arquivo_anonimizado);

    if ($stmt->execute()) {
        $id_arquivo = $conn->insert_id;
        error_log("id_arquivo: " . $id_arquivo);
    } else {
        error_log('Query failed: ' . $e->getMessage());
    }

    $stmt = $conn->prepare("SELECT id_chave_tipo_acao FROM tipos_acoes WHERE nome_tipo_acao = ?");

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('s', $nomeTipoAcao);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);

        if (!empty($data)) {
            $id_tipo_acao = $data[0]['id_chave_tipo_acao'];
        }
    } else {
        error_log('Query failed: ' . $e->getMessage());
    }


    $stmt = $conn->prepare("INSERT INTO acoes (id_atividade_evento, id_localizacao, id_tipo_acao, id_arquivo, latitude, longitude, data_acao, hora_acao, descricao, id_pessoa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('iiiiddsssi', $id_atividade_evento, $id_localizacao, $id_tipo_acao, $id_arquivo, $lat, $lon, $dataAcao, $horaAcao, $descricao, $id_pessoa);

    if ($stmt->execute()) {
        $id_arquivo = $conn->insert_id;
    } else {
        error_log('Query failed: ' . $e->getMessage());
    }


    $response = [
        'success' => true,
        'message' => 'Data submitted successfully'
    ];

    echo json_encode($response);
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$stmt->close();
$conn->close();

ob_end_flush();
