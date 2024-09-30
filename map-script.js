// Initialize map with a default location
var map = L.map('map').setView([51.505, -0.09], 13); // Default view (London)

// Add OpenStreetMap tiles
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
}).addTo(map);

// Get user's current location when the page loads
window.onload = function () {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(showPosition, showError);
    } else {
        alert("Geolocation is not supported by this browser.");
    }
};

// Function to handle successful geolocation
function showPosition(position) {
    var lat = position.coords.latitude;
    var lon = position.coords.longitude;

    // Center map on user's location
    map.setView([lat, lon], 13);

    // Add a marker at user's current location
    L.marker([lat, lon]).addTo(map)
        .bindPopup("Você está aqui")
        .openPopup();
}

// Handle geolocation errors
function showError(error) {
    switch (error.code) {
        case error.PERMISSION_DENIED:
            alert("User denied the request for Geolocation.");
            break;
        case error.POSITION_UNAVAILABLE:
            alert("Location information is unavailable.");
            break;
        case error.TIMEOUT:
            alert("The request to get user location timed out.");
            break;
        case error.UNKNOWN_ERROR:
            alert("An unknown error occurred.");
            break;
    }
}