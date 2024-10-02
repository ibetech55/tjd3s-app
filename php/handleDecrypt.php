<?php
function handleDecrypt($plainText, $key, $iv)
{
    $cipherMethod = 'AES-256-CBC';

    // Encrypt the string
    $encryptedString = openssl_encrypt($plainText, $cipherMethod, $key, 0, $iv);

    return $encryptedString;
}
