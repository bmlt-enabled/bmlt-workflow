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

namespace bmltwf;

if ((!defined('ABSPATH')&&(!defined('BMLTWF_RUNNING_UNDER_PHPUNIT')))) exit; // die if being called directly

class BMLTWF_WP_Options
{

    public function __construct($stub = null)
    {
    // capability for managing submissions
        $this->bmltwf_capability_manage_submissions = 'bmltwf_manage_submissions';
    
        // option list so we can back them up
        $this->bmltwf_options = array(
            'bmltwf_db_version',
            'bmltwf_bmlt_server_address',
            'bmltwf_bmlt_username',
            'bmltwf_bmlt_password',
            'bmltwf_bmlt_test_status',
            'bmltwf_submitter_email_template',
            'bmltwf_required_meeting_formats',
            'bmltwf_optional_location_sub_province',
            'bmltwf_optional_location_sub_province_displayname',
            'bmltwf_optional_location_province',
            'bmltwf_optional_location_province_displayname',
            'bmltwf_optional_location_nation',
            'bmltwf_optional_location_nation_displayname',
            'bmltwf_optional_postcode',
            'bmltwf_optional_postcode_displayname',
            'bmltwf_delete_closed_meetings',
            'bmltwf_email_from_address',
            'bmltwf_fso_email_template',
            'bmltwf_fso_email_address',
            'bmltwf_submitter_email_template',
            'bmltwf_fso_feature',
            'bmltwf_trusted_servants_can_delete_submissions'
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
