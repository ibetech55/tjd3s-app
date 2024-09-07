<?php
include "./php/GetUserName.php";
include "./fetch-suggestions.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="style.css">
    <title>Document</title>
</head>

<body>
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
                <span><?php echo isset($_GET['nome_usuario_clean']) ? $_GET['nome_usuario_clean'] : ''; ?>,</span>
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
                            <input id="data" name="data" type="date">
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

            <!-- <input type="file" id="cameraInput" name="files" accept="image/*" style="display:none" onchange="addImageToArray(event)"> -->



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
            <span>[Link: Politica de Privacidade]</span>
        </div>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('./sw.js').then(function(registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                }, function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>

    <script src="./upload-foto-script.js">
    </script>

    <script>
        let imagesArray = [];

        function isMobileDevice() {
            return /Mobi|Android/i.test(navigator.userAgent);
        }

        function openCamera() {
            if (isMobileDevice()) {
                document.getElementById("cameraInput2").click()
            } else {
                const video = document.getElementById('cameraStream');
                const constraints = {
                    video: {
                        facingMode: 'environment'
                    }
                };

                navigator.mediaDevices.getUserMedia(constraints)
                    .then((stream) => {
                        video.srcObject = stream;
                        video.style.display = 'block';
                        video.addEventListener('click', captureImage);
                    })
                    .catch((err) => {
                        console.error('Error accessing camera: ', err);
                    });
            }


        }

        function captureImage() {
            const video = document.getElementById('cameraStream');
            const canvas = document.getElementById('cameraCanvas');
            const context = canvas.getContext('2d');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            video.srcObject.getTracks().forEach(track => track.stop());
            video.style.display = 'none';

            const dataURL = canvas.toDataURL('image/png');

            // Convert dataURL to a File object
            fetch(dataURL)
                .then(res => res.blob())
                .then(blob => {
                    const file = new File([blob], 'captured-image.png', {
                        type: 'image/png'
                    });
                    imagesArray = [file]; // Replace previous image with the new one
                    displayImagePreview(file);
                });
        }

        // // Function to trigger file upload dialog
        // function triggerFileUpload() {
        //     document.getElementById('fileInput').click();
        // }

        // // Function to trigger camera capture dialog
        // function triggerCameraCapture() {
        //     document.getElementById('cameraInput').click();

        // }

        function addImageToArray(event) {
            const files = event.target.files;
            if (files.length > 0) {
                const file = files[0];
                imagesArray = [file]; // Replace previous image with the new one
                displayImagePreview(file);

                // Clear the input value to allow re-upload of the same file
                event.target.value = '';
            }
        }

        function displayImagePreview(file) {
            const previewContainer = document.getElementById('imagePreviews');
            previewContainer.innerHTML = ''; // Clear previous previews
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '100px'; // Ensure images are displayed at 100px by 100px
                img.style.height = '100px';
                img.style.margin = '5px';
                document.getElementById('imagePreviews').appendChild(img);
            };
            reader.readAsDataURL(file);
        }

        function showCustomAlert() {
            document.getElementById('customModal').style.display = 'block';
        }

        function closeCustomAlert() {
            document.getElementById('customModal').style.display = 'none';
        }

        function promptUserForAction() {
            document.getElementById('notificationBox').style.display = 'block';

        }

        // Function to hide the small notification box
        function hideNotification() {
            document.getElementById('notificationBox').style.display = 'none';
        }

        // Function to handle user choices
        function handleChoice(choice) {
            if (choice === 'folder') {
                document.getElementById('fileInput').click();
            } else if (choice === 'camera') {
                openCamera()
            }
            hideNotification();
        }


        function openDatabase(dbName, version, upgradeCallback) {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(dbName, version);

                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    if (upgradeCallback) {
                        upgradeCallback(db);
                    }
                };

                request.onsuccess = (event) => {
                    resolve(event.target.result);
                };

                request.onerror = (event) => {
                    reject('Error opening database: ' + event.target.errorCode);
                };
            });
        }

        function upgradePhrasesDB(db) {
            if (!db.objectStoreNames.contains('phrases')) {
                db.createObjectStore('phrases', {
                    keyPath: 'id',
                    autoIncrement: true
                });
            }
        }

        function upgradeOfflineDataDB(db) {
            if (!db.objectStoreNames.contains('offlineData')) {
                db.createObjectStore('offlineData', {
                    keyPath: 'id',
                    autoIncrement: true
                });
            }
        }

        function storePhrasesInDB(phrases) {
            openDatabase('PhrasesDB', 1, upgradePhrasesDB).then(db => {
                const transaction = db.transaction(['phrases'], 'readwrite');
                const objectStore = transaction.objectStore('phrases');

                objectStore.clear();
                phrases.forEach(phrase => {
                    objectStore.add({
                        nome_frase: phrase
                    });
                });

                transaction.oncomplete = () => {
                    console.log('Phrases stored in IndexedDB');
                };

                transaction.onerror = () => {
                    console.error('Error storing phrases in IndexedDB');
                };
            });
        }

        function getPhrasesFromDB(query) {
            return new Promise((resolve, reject) => {
                openDatabase('PhrasesDB', 1, upgradePhrasesDB).then(db => {
                    const transaction = db.transaction(['phrases'], 'readonly');
                    const objectStore = transaction.objectStore('phrases');
                    const request = objectStore.getAll();

                    request.onsuccess = (event) => {
                        const phrases = event.target.result;
                        const filteredPhrases = phrases
                            .map(phrase => phrase.nome_frase)
                            .filter(phrase => phrase.toLowerCase().includes(query.toLowerCase()))
                            .slice(0, 10);
                        resolve(filteredPhrases);
                    };

                    request.onerror = () => {
                        reject('Error retrieving phrases from IndexedDB');
                    };
                });
            });
        }

        function storeDataOffline(data) {
            openDatabase('OfflineDataDB', 1, upgradeOfflineDataDB).then(db => {
                const transaction = db.transaction(['offlineData'], 'readwrite');
                const objectStore = transaction.objectStore('offlineData');

                const request = objectStore.getAll();
                request.onsuccess = () => {
                    const existingData = request.result;
                    if (existingData.length > 0) {
                        existingData.forEach(item => {
                            objectStore.delete(item.id);
                        });
                    }

                    const dataToStore = {
                        ...data,
                    };

                    objectStore.add(dataToStore);

                    transaction.oncomplete = () => {
                        console.log('Old data removed, new data stored offline');
                        document.getElementById('criar-evidencia-form').reset();
                        document.getElementById('imagePreviews').innerHTML = '';
                        document.getElementById("remove-chip").click();
                        imagePreviews = [];
                    };

                    transaction.onerror = () => {
                        console.error('Error storing new data offline');
                    };
                };

                request.onerror = () => {
                    console.error('Error checking existing data in IndexedDB');
                };
            });
        }

        function syncDataWithServer() {
            console.log('Back online! Syncing data with server in 30 seconds...');

            setTimeout(() => {
                openDatabase('OfflineDataDB', 1, upgradeOfflineDataDB).then(db => {
                    const transaction = db.transaction(['offlineData'], 'readonly');
                    const objectStore = transaction.objectStore('offlineData');
                    const request = objectStore.getAll();

                    request.onsuccess = (event) => {
                        const offlineData = event.target.result;

                        if (offlineData.length === 0) {
                            console.log('No offline data to sync');
                            return;
                        }

                        const sendDataToServer = (data, index) => {
                            if (index >= offlineData.length) {
                                return;
                            }

                            const formData = new FormData();
                            for (const key in data) {
                                if (key === 'files') {
                                    for (const file of data.files) {
                                        formData.append('files[]', file);
                                    }
                                } else {
                                    formData.append(key, data[key]);
                                }
                            }

                            fetch('/php/CriarEvidencia.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok: ' + response.statusText);
                                    }
                                    return response.json();
                                })
                                .then(() => {
                                    sendDataToServer(offlineData[index + 1], index + 1);
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                });
                        };

                        // sendDataToServer(offlineData[0], 0);
                    };

                    request.onerror = () => {
                        console.error('Error fetching data from IndexedDB');
                    };
                });
            }, 30000);
        }

        function removeSyncedDataFromDB(id) {
            openDatabase('OfflineDataDB', 1, upgradeOfflineDataDB).then(db => {
                const transaction = db.transaction(['offlineData'], 'readwrite');
                const objectStore = transaction.objectStore('offlineData');
                objectStore.delete(id);

                transaction.oncomplete = () => {
                    console.log('Synced data removed from IndexedDB');
                };

                transaction.onerror = () => {
                    console.error('Error removing synced data from IndexedDB');
                };
            });
        }

        function loadAllPhrasesIntoIndexedDB() {
            if (navigator.onLine) {
                fetch('/fetch-all-frases.php')
                    .then(response => response.json())
                    .then(data => {
                        storePhrasesInDB(data);
                    })
                    .catch(error => console.error('Error fetching all phrases:', error));
            }
        }

        function removerAcentos(str) {
            return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        const stopwords = ['eu', 'nos', 'fui', 'estava', 'eles', 'elas', 'e', 'o', 'a', 'do', 'da', 'em', 'um', 'uma', 'que', 'para', 'com', 'por', 'se', 'de', 'no', 'na'];

        function filterStopwords(query) {
            const words = query.split(' ');
            const filteredWords = words.filter(word => !stopwords.includes(word));
            return filteredWords.join(' ');
        }

        function getCurrentWord(input) {
            const words = input.split(' ');
            return words[words.length - 1];
        }

        function isStopword(word) {
            return stopwords.includes(word);
        }

        function fetchSuggestions() {
            let input = document.getElementById('tipo-atividade').value;
            input = removerAcentos(input.toLowerCase());

            const currentWord = getCurrentWord(input);

            if (isStopword(currentWord)) {
                clearSuggestions();
                return;
            }

            const filteredInput = filterStopwords(input);

            if (currentWord.length >= 2) {
                if (navigator.onLine) {
                    fetch(`/fetch-suggestions.php?q=${encodeURIComponent(currentWord)}`)
                        .then(response => response.json())
                        .then(data => {
                            displaySuggestions(data);
                        })
                        .catch(error => console.error('Error fetching suggestions:', error));
                } else {

                    getPhrasesFromDB(currentWord)
                        .then(data => {
                            displaySuggestions(data);
                        })
                        .catch(error => console.error('Error retrieving suggestions from IndexedDB:', error));
                }
            } else {
                clearSuggestions();
            }
        }


        function clearSuggestions() {
            const container = document.getElementById('suggestions-container');
            container.innerHTML = '';
        }


        function displaySuggestions(suggestions) {
            const container = document.getElementById('suggestions-container');
            container.innerHTML = '';

            suggestions.forEach(suggestion => {
                const chip = document.createElement('div');
                chip.className = 'suggestion-chip';
                chip.textContent = suggestion;
                chip.onclick = () => addChip(suggestion);
                container.appendChild(chip);
            });
        }

        function addChip(text) {
            const chipsContainer = document.getElementById('chips-container');
            const input = document.getElementById('tipo-atividade');
            input.innerText = ""
            const chip = document.createElement('div');
            chip.className = 'chip';
            chip.innerHTML = `
        <span>${text}</span>
        <div id="remove-chip" class="remove-chip" onclick="removeChip(this)">x</div>
    `;
            chipsContainer.appendChild(chip);

            toggleInputVisibility();

            // Clear the suggestions
            clearSuggestions();
        }

        function removeChip(button) {
            const chip = button.parentElement;
            chip.remove();

            // Show the input field if there are no chips
            toggleInputVisibility();

            // Clear and focus the input field
            const input = document.getElementById('tipo-atividade');
            input.value = ''; // Reset input field
            input.focus(); // Re-focus the input field
        }

        function toggleInputVisibility() {
            const chipsContainer = document.getElementById('chips-container');
            const input = document.getElementById('tipo-atividade');

            if (chipsContainer.children.length > 0) {
                input.classList.add('hidden');
            } else {
                input.classList.remove('hidden');
            }
        }

        // Call toggleInputVisibility on page load to set the correct initial state
        document.addEventListener('DOMContentLoaded', toggleInputVisibility);



        document.getElementById('tipo-atividade').addEventListener('input', fetchSuggestions);


        document.addEventListener('DOMContentLoaded', function() {
            loadAllPhrasesIntoIndexedDB();
        });

        document.getElementById('fileInput').addEventListener('change', function(event) {
            console.log('File input changed:', event.target.files);
        });
        document.getElementById('criar-evidencia-form').addEventListener('submit', function(event) {
            event.preventDefault();

            const formElement = document.getElementById('criar-evidencia-form');
            const formData = new FormData();
            formData.append('nome-atividade', document.getElementById('nome-atividade').value);
            formData.append('tipo-atividade', document.getElementById('tipo-atividade').value);
            formData.append('data', document.getElementById('data').value);
            formData.append('atividade-realizada', document.getElementById('atividade-realizada').value);
            formData.append('latitude', document.getElementById('latitude').value);
            formData.append('longitude', document.getElementById('longitude').value);
            if (imagesArray.length > 0) {
                formData.append('files[]', imagesArray[0]);
            }


            if (navigator.onLine) {
                fetch('/php/CriarEvidencia.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(() => {
                        formElement.reset();
                        document.getElementById('imagePreviews').innerHTML = '';
                        document.getElementById("remove-chip").click();
                        alert('Dados enviados com sucesso!');
                    })
                    .catch(error => {
                        console.error('Error submitting data:', error);
                        alert('Erro ao enviar dados, tente novamente');
                    });
            } else {
                const plainData = Object.fromEntries(formData.entries());
                storeDataOffline(plainData);
            }
        });

        window.addEventListener('online', () => {
            console.log('Back online! Syncing data with server...');
            syncDataWithServer();
        });

        window.addEventListener('offline', () => {
            console.log('You are now offline. Your data will be saved locally.');
        });

        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            navigator.serviceWorker.ready.then(registration => {
                registration.sync.register('sync-data');
            });
        }

        // self.addEventListener('sync', event => {
        //     if (event.tag === 'sync-data') {
        //         event.waitUntil(syncDataWithServer());
        //     }
        // });
    </script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="./map-script.js"></script>
</body>

</html>