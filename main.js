let imagesArray = [];
let suggestionSelected = "";

function getNomeUsusario(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param) ? urlParams.get(param).replaceAll('_', ' ') : "";
}

window.addEventListener('load', function () {
    let nomeUsuario = "";
    if (!localStorage.getItem('nome_usuario')) {
        nomeUsuario = getNomeUsusario('nome_usuario');
    } else {
        nomeUsuario = localStorage.getItem('nome_usuario');
    }
    document.getElementById('nome-usuario').innerText = `${nomeUsuario},`;
    localStorage.setItem('nome_usuario', nomeUsuario);
})
window.addEventListener('load', function () {
    navigator.geolocation.getCurrentPosition(showPosition2, showError, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000
    });

    function showPosition2(position) {
        var lat = position.coords.latitude;
        var lon = position.coords.longitude;
        localStorage.setItem('latitude', lat);
        localStorage.setItem('longitude', lon);
    }

    function showError(error) {
        console.log('Error in geolocation');
    }
});
if ('serviceWorker' in navigator) {
    const protocol = window.location.protocol;
    const host = window.location.host;
    const swUrl = `${protocol}//${host}/sw.js`;

    navigator.serviceWorker.register(swUrl, {
        scope: '/'
    })
        .then(function (registration) {
            console.log('Service Worker registered with scope:', registration.scope);
        })
        .catch(function (error) {
            console.log('Service Worker registration failed:', error);
        });
}

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

function addImageToArray(event) {
    const files = event.target.files;
    if (files.length > 0) {
        const file = files[0];
        if (!file.type.includes("image/")) {
            alert("O arquivo não é uma imagem");
            return;
        } else {
            imagesArray = [file]; // Replace previous image with the new one
            displayImagePreview(file);

            // Clear the input value to allow re-upload of the same file
            event.target.value = '';
        }

    }
}

function displayImagePreview(file) {
    const previewContainer = document.getElementById('imagePreviews');
    previewContainer.innerHTML = '';
    const reader = new FileReader();
    reader.onload = function (e) {
        const img = document.createElement('img');
        img.src = e.target.result;
        img.style.display = 'block'
        img.style.width = '300px';
        img.style.height = '300px';
        img.style.margin = '0px auto 2rem auto';
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
            const dataToStore = {
                ...data,
            };

            objectStore.add(dataToStore);

            transaction.oncomplete = () => {
                console.log('Old data removed, new data stored offline');
                document.getElementById('criar-evidencia-form').reset();
                document.getElementById('imagePreviews').innerHTML = '';
                document.getElementById("remove-chip").click();
                suggestionSelected = "";
                imagePreviews = [];
                alert('Dados salvos. Serão enviados quando houver conexão.');
            };

            transaction.onerror = () => {
                console.error('Error storing new data offline');
                alert('Erro');
            };
        };

        request.onerror = () => {
            console.error('Error checking existing data in IndexedDB');
        };
    });
}

function syncDataWithServer() {
    console.log('Back online! Syncing data with server in 10 seconds...');

    setTimeout(() => {
        openDatabase('OfflineDataDB', 1, upgradeOfflineDataDB).then(db => {
            const transaction = db.transaction(['offlineData'], 'readonly');
            const objectStore = transaction.objectStore('offlineData');
            const request = objectStore.getAll();
            const fetchStatusArr = []

            request.onsuccess = (event) => {
                const offlineDataArray = event.target.result;

                if (offlineDataArray.length === 0) {
                    console.log('No offline data to sync');
                    return;
                }

                offlineDataArray.forEach(offlineData => {
                    const formData = new FormData();

                    for (const key in offlineData) {
                        if (key === 'files') {
                            for (const file of offlineData.files) {
                                formData.append('files[]', file);
                            }
                        } else {
                            formData.append(key, offlineData[key]);
                        }
                    }

                    fetch('./php/CriarEvidencia.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok: ' + response.statusText);
                            }
                            fetchStatusArr.push(true)
                            clearOfflineData(db, offlineData.id);
                            return response.json();
                        })
                        .catch(error => {
                            fetchStatusArr.push(false)
                            console.error('Error syncing data:', error);
                        });
                });
            };

            if (!fetchStatusArr.includes(false)) {
                alert('Dados enviados com sucesso!')
            } else {

            }
            request.onerror = () => {
                console.error('Error fetching data from IndexedDB');
            };
        });
    }, 10000);
}


function clearOfflineData(db, id) {
    const transaction = db.transaction(['offlineData'], 'readwrite');
    const objectStore = transaction.objectStore('offlineData');
    const deleteRequest = objectStore.delete(id);

    deleteRequest.onsuccess = () => {
        console.log(`Offline data with id ${id} cleared after sync`);
    };

    deleteRequest.onerror = () => {
        console.error(`Error clearing offline data with id ${id}`);
    };
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
    let suggestions = [];
    const currentWord = getCurrentWord(input);

    if (isStopword(currentWord)) {
        clearSuggestions();
        return;
    }

    if (currentWord.length >= 2) {
        if (navigator.onLine) {
            fetch(`/fetch-suggestions.php?q=${encodeURIComponent(currentWord)}`)
                .then(response => response.json())
                .then(data => {
                    suggestions = []
                    suggestions = data;
                    if (suggestions.length)
                        displaySuggestions(data);
                })
                .catch(error => console.error('Error fetching suggestions:', error));
        } else {

            getPhrasesFromDB(currentWord)
                .then(data => {
                    suggestions = []
                    suggestions = data;

                    if (suggestions.length) {
                        displaySuggestions(data);
                    }
                })
                .catch(error => console.error('Error retrieving suggestions from IndexedDB:', error));
        }
    } else {
        if (suggestions.length !== 0 || currentWord.length === 0) {
            clearSuggestions();
        }
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
    suggestionSelected = text;
    const chipsContainer = document.getElementById('chips-container');
    const input = document.getElementById('tipo-atividade');
    input.innerText = text;
    input.value = text;
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


document.addEventListener('DOMContentLoaded', function () {
    loadAllPhrasesIntoIndexedDB();
});

document.getElementById('fileInput').addEventListener('change', function (event) {
    console.log('File input changed:', event.target.files);
});
document.getElementById('criar-evidencia-form').addEventListener('submit', function (event) {
    event.preventDefault();

    const formElement = document.getElementById('criar-evidencia-form');
    const formData = new FormData();

    const nomeAtividade = document.getElementById('nome-atividade');
    const tipoAtividade = document.getElementById('tipo-atividade');
    const data = document.getElementById('data');
    const atividadeRealizada = document.getElementById('atividade-realizada');

    const fields = {
        nomeAtividade,
        tipoAtividade,
        data,
        atividadeRealizada
    }

    const errorFields = []

    if (!localStorage.getItem('longitude') || !localStorage.getItem('longitude')) {
        alert('Erro ao enviar dados, não consegue encontrar sua localização');
        return;
    }

    if (!localStorage.getItem('nome_usuario')) {
        alert('Erro ao enviar dados, precisa ter uma usuario vinculado com esta pesquisa');
        return;
    }

    Object.keys(fields).map((key) => {
        const field = fields[key];
        if (!field.value.trim()) {
            field.classList.add('error-input')
            errorFields.push(field)
        } else {
            field.classList.remove('error-input')
        }
    })

    if (errorFields.length > 0) {
        const focusField = errorFields[0];
        focusField.focus();
        alert("Por favor, preencha todos os campos")
        return;
    }

    if (suggestionSelected === "") {
        alert("Por favor, escolha uma das opçoẽs do Frase que melhor descreve esse ação.");
        document.getElementById('tipo-atividade').classList.add('error-input')
        return;
    } else {
        document.getElementById('tipo-atividade').classList.remove('error-input')
    }


    if (errorFields.length > 0) {
        const focusField = errorFields[0];
        focusField.focus();
        alert("Por favor, preencha todos os campos")
        return;
    }

    if (imagesArray.length === 0) {
        alert("Por favor, carregue ou tire uma foto")
        return;
    }
    formData.append('nome-atividade', nomeAtividade.value);
    formData.append('tipo-atividade', tipoAtividade.value);
    formData.append('data', data.value);
    formData.append('atividade-realizada', atividadeRealizada.value);
    formData.append('data-acao', data.value.split('T')[0]);
    formData.append('hora-acao', data.value.split('T')[1]);
    formData.append('longitude', localStorage.getItem('longitude'));
    formData.append('latitude', localStorage.getItem('latitude'));
    formData.append('nome-usuario', localStorage.getItem('nome_usuario'));

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
                suggestionSelected = "";
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
