<?php

namespace Timurikvx\Filebase\Crypto;

class Crypto
{

    public function __construct()
    {

    }

    public function encrypt(string $plaintext, string $key): string
    {
        $method = "AES-256-CBC";
        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext, $key, true);
        return base64_encode($iv . $hmac . $ciphertext);
    }

    public function decrypt(string $encrypted, string $key): string
    {
        $data = base64_decode($encrypted);
        $method = "AES-256-CBC";
        $ivLength = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $ivLength);
        $hmac = substr($data, $ivLength, 32);
        $ciphertext = substr($data, $ivLength + 32);
        $decrypted = openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
        $calcHmac = hash_hmac('sha256', $ciphertext, $key, true);
        if (hash_equals($hmac, $calcHmac)) {
            return $decrypted;
        }
        return '';
    }

}