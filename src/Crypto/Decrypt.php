<?php

namespace Timurikvx\Filebase\Crypto;

class Decrypt
{
    public static function loadAndDecrypt($filePath, $privateKeyPath, $passphrase = null): mixed
    {
        $fileContent = file_get_contents($filePath);

        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($fileContent, 0, $ivLength);
        $encryptedKey = substr($fileContent, $ivLength, 512); // 4096 бит = 512 байт
        $encryptedData = substr($fileContent, $ivLength + 512);

        // Дешифруем ключ
        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath), $passphrase);
        openssl_private_decrypt($encryptedKey, $key, $privateKey);

        // Дешифруем данные
        return openssl_decrypt($encryptedData, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    }

    public static function decryptString($data, $key): bool
    {
        $data = base64_decode($data);
        $method = 'aes-256-cbc';
        $ivLength = openssl_cipher_iv_length($method);

        $iv = substr($data, 0, $ivLength);
        $hmac = substr($data, $ivLength, 32);
        $encrypted = substr($data, $ivLength + 32);

        $decrypted = openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
        $calcHmac = hash_hmac('sha256', $encrypted, $key, true);

        if (hash_equals($hmac, $calcHmac)) {
            return $decrypted;
        }

        return false;
    }

}