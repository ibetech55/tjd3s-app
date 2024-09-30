<?php
include "./fetch-suggestions.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/fundacentro-16x16.png" sizes="16x16" type="image/png">
    <link rel="icon" href="/assets/fundacentro-32x32.png" sizes="32x32" type="image/png">
    <link rel="icon" href="/assets/fundacentro-64x64.png" sizes="64x64" type="image/png">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="style.css">
    <title>TJD3S</title>
</head>

<body>
    <div id="loading"></div>
    <div id="notificationBox" class="notification-box">
        <span class="close" onclick="hideNotification()">&times;</span>
        <p>Deseja abrir a pasta de imagens ou usar a câmera?</p>
        <button onclick="handleChoice('folder')">Abrir pasta de imagens</button>
        <button onclick="handleChoice('camera')">Usar câmera</button>
    </div>
    <div class="header">
        <div class="header-icon">
            <img src="/assets/fundacentro.png" alt="fundacentro">
        </div>
        <div class="header-text">
            <span>Ministério do Trabalho e Emprego</span>
            <span>FUNDACENTRO</span>
            <span>Registro de Ação</span>
        </div>
        <div class="header-icon">
            <img src="/assets/calendar-check.svg" alt="">
        </div>
    </div>
    <section class="banner-section">
        <div class="banner-image">
            <div class="banner-image-img">
                <img src="/assets/banner-image.svg" alt="banner-image">
            </div>
        </div>
        <div class="banner-text">
            <p>
                <span id="nome-usuario"></span>
                boas-vindas ao registro de atividades do <span>Programa Nacional de Economia
                    Popular,
                    Solidária e Sustentável!</span>
            </p>
        </div>
    </section>
    <section class="form-section">
        <form id="criar-evidencia-form">
            <div class="form-atividade">
                <div class="form-atividade-inputs">
                    <h2><img src="/assets/check-icon.svg" alt="check-icon">Registre sua Contribuição!</h2>
                    <div class="form-input">
                        <label for="nome-atividade">Nome da atividade</label>
                        <input type="text" id="nome-atividade" name="nome-atividade">
                    </div>
                    <div class="row-tipo-atividade-data">
                        <div class="form-input">
                            <label for="tipo-atividade">Frase que melhor descreve esse ação</label>
                            <div id="tipo-atividade-container" class="chip-container">
                                <div id="chips-container" class="chips-container"></div>
                                <input type="text" id="tipo-atividade" name="tipo-atividade" oninput="fetchSuggestions()">
                            </div>
                            <div id="suggestions-container" class="suggestions-container"></div>
                        </div>

                        <div class="form-input">
                            <label for="data">Data</label>
                            <input id="data" name="data" type="datetime-local">
                        </div>
                    </div>
                    <div class="form-input">
                        <label for="atividade-realizada">Detalhe a ação com suas palavras</label>
                        <textarea type="text" id="atividade-realizada" name="atividade-realizada" rows="6"></textarea>
                    </div>
                </div>
            </div>
            <div class="form-evidencias">
                <span>Adicione evidências da sua atividade</span>
                <div class="foto">
                    <div class="foto-container" onclick="promptUserForAction()">
                        <div class="foto-container-img">
                            <img src="/assets/camera.svg" alt="camera">
                        </div>
                        <span>Clique para tirar uma foto</span>
                    </div>
                </div>
                <div class="video-canvas">
                    <video id="cameraStream" autoplay style="display:none;"></video>
                    <canvas id="cameraCanvas" style="display:none;"></canvas>
                </div>
                <div class="image-previews" id="imagePreviews"></div>
                <div class="local">
                    <span>Localização Aproximada Detectada:</span>
                    <div class="map" id="map">
                    </div>
                </div>
                <div class=" submit">
                    <button>Enviar</button>
                </div>
            </div>

            <input type="file" id="fileInput" name="files" accept="image/*" style="display:none" onchange="addImageToArray(event)">
            <input type="file" id="cameraInput2" accept="image/*" name="files" style="display:none;" capture="environment"
                onchange="addImageToArray(event)">


            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">
        </form>
    </section>

    <div class="footer">
        <div class="footer-icon">
            <img src="/assets/fundacentro.png" alt="fundacentro">
        </div>
        <h2>TJD3S</h2>
        <div class="contact">
            <span>Contato: selecao0124@fundacentro.gov.br</span>
        </div>

        <script src="./main.js">
        </script>

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="./map-script.js"></script>
</body>

</html>