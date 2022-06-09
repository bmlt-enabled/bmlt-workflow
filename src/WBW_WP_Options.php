<?php

namespace wbw;

class WBW_WP_Options
{

    public function __construct($stub = null)
    {
    // capability for managing submissions
        $this->wbw_capability_manage_submissions = 'wbw_manage_submissions';
    
        // option list so we can back them up
        $this->wbw_options = array(
            'wbw_db_version' => 'wbw_db_version',
            'wbw_bmlt_server_address' => 'wbw_bmlt_server_address',
            'wbw_bmlt_username' => 'wbw_bmlt_username',
            'wbw_bmlt_password' => 'wbw_bmlt_password',
            'wbw_bmlt_test_status' => 'wbw_bmlt_test_status',
            'wbw_submitter_email_template' => 'wbw_submitter_email_template',
            'wbw_optional_location_sub_province' => 'wbw_optional_location_sub_province',
            'wbw_optional_location_nation' => 'wbw_optional_location_nation',
            'wbw_delete_closed_meetings' => 'wbw_delete_closed_meetings',
            'wbw_email_from_address' => 'wbw_email_from_address',
            'wbw_fso_email_template' => 'wbw_fso_email_template',
            'wbw_fso_email_address' => 'wbw_fso_email_address',
            'wbw_submitter_email_template' => 'wbw_submitter_email_template',
        );
    }
        
    public function wbw_get_option($option, $default = false)
    {
        return get_option($this->wbw_options[$option], $default);
    }

    public function secrets_encrypt($password, $secret)
    {

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

    public function secrets_decrypt($password, $data)
    {

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
