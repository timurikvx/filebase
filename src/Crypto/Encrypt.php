<?php

namespace Timurikvx\Filebase\Crypto;

class Encrypt
{
    public static function encryptAndSave($data, $filePath, $publicKeyPath): void
    {
        // Генерируем случайный симметричный ключ
        $key = openssl_random_pseudo_bytes(32);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        // Шифруем данные
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        // Шифруем ключ RSA
        $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
        openssl_public_encrypt($key, $encryptedKey, $publicKey);

        // Сохраняем (IV + зашифрованный ключ + зашифрованные данные)
        file_put_contents($filePath, $iv . $encryptedKey . $encryptedData);
    }

    public static function encryptString($data, $key): string
    {
        $method = 'aes-256-cbc';
        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $encrypted, $key, true);

        return base64_encode($iv . $hmac . $encrypted);
    }

}