<?php
// Copyright (C) 2022 nigel.bmlt@gmail.com
// 
// This file is part of bmlt-workflow.
// 
// bmlt-workflow is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// bmlt-workflow is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with bmlt-workflow.  If not, see <http://www.gnu.org/licenses/>.



namespace bw;

class BW_WP_Options
{

    public function __construct($stub = null)
    {
    // capability for managing submissions
        $this->bw_capability_manage_submissions = 'bw_manage_submissions';
    
        // option list so we can back them up
        $this->bw_options = array(
            'bw_db_version',
            'bw_bmlt_server_address',
            'bw_bmlt_username',
            'bw_bmlt_password',
            'bw_bmlt_test_status',
            'bw_submitter_email_template',
            'bw_optional_location_sub_province',
            'bw_optional_location_nation',
            'bw_delete_closed_meetings',
            'bw_email_from_address',
            'bw_fso_email_template',
            'bw_fso_email_address',
            'bw_submitter_email_template',
            'bw_fso_feature',
            'bw_optional_postcode'
        );
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
