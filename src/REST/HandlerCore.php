<?php

namespace wbw\REST;

use wbw\Debug;
use wbw\BMLT\Integration;

class HandlerCore
{

    public function __construct($stub = null)
    {
        if (empty($stub))
        {
            $this->bmlt_integration = new Integration;
        }
        else
        {
            $this->bmlt_integration = $stub;
        }
    }

    // accepts raw string or array
    public function wbw_rest_success($message)
    {
        if (is_array($message)) {
            $data = $message;
        } else {
            $data = array('message' => $message);
        }
        $response = new \WP_REST_Response();
        $response->set_data($data);
        $response->set_status(200);
        return $response;
    }

    public function wbw_rest_error($message, $code)
    {
        return new \WP_Error('wbw_error', $message, array('status' => $code));
    }

    public function wbw_rest_error_with_data($message, $code, array $data)
    {
        $data['status'] = $code;
        return new \WP_Error('wbw_error', $message, $data);
    }

    function secrets_encrypt($password, $secret) {

        $config = [
            'size'      => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES,
            'salt'      => random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES),
            'limit_ops' => SODIUM_CRYPTO_PWHASH_OPSLIMIT_SENSITIVE,
            'limit_mem' => SODIUM_CRYPTO_PWHASH_MEMLIMIT_SENSITIVE,
            'alg'       => SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13,
            'nonce'     => random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES),
          ];
      
        $key = hash_hkdf('sha256', $password, $config['size'], 'context');

        $encrypted = sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
            $secret,
            $config['nonce'], // Associated Data
            $config['nonce'],
            $key
          );
      
        return [
            'config' => array_map('base64_encode', $config),
            'encrypted' => base64_encode($encrypted),
          ];
      
      }
            
      function secrets_decrypt($password, $data) {
      
        $config = array_map('base64_decode', $data['config']);
        $encrypted = base64_decode($data['encrypted']);
      
        $key = hash_hkdf('sha256', $password, $config['size'], 'context');

        return sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
            $encrypted,
            $config['nonce'], // Associated Data
            $config['nonce'],
            $key
          );
      
      }
            
}
