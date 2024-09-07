<?php
// Get the 'nome_usuario' parameter from the URL
if (isset($_GET['nome_usuario'])) {
    $nome_usuario = $_GET['nome_usuario'];

    // Replace underscores with spaces
    $nome_usuario_clean = str_replace('_', ' ', $nome_usuario);

    // Redirect to index.php with the cleaned-up name as a parameter
    header('Location: index.php?nome_usuario_clean=' . urlencode($nome_usuario_clean));
    exit(); // Stop further execution after the redirect
}
