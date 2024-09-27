<?php
class EncryptionHelper {
    private static $encryptionMethod = 'AES-256-CBC'; 
    private static $secretKey = 'ABCDEFGHIJK'; 

    public static function encrypt($plainText) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$encryptionMethod));
        $encryptedText = openssl_encrypt($plainText, self::$encryptionMethod, self::$secretKey, 0, $iv);
        return base64_encode($encryptedText . '::' . $iv); 
    }

    public static function decrypt($encryptedText) {
        list($encryptedData, $iv) = explode('::', base64_decode($encryptedText), 2);
        return openssl_decrypt($encryptedData, self::$encryptionMethod, self::$secretKey, 0, $iv);
    }
}
