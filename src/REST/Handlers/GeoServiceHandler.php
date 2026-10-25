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



namespace bmltwf\REST\Handlers;

use bmltwf\BMLT\Integration;

class GeoServiceHandler
{

    use \bmltwf\BMLTWF_Debug;
    use \bmltwf\BMLTWF_Secrets;
    use \bmltwf\REST\HandlerCore;

    protected $bmlt_integration;

    public function __construct($intstub = null)
    {
        $this->initTableNames();

        if (empty($intstub)) {
            $this->bmlt_integration = new Integration();
        } else {
            $this->bmlt_integration = $intstub;
        }
    }

    // Tests the geo service API key by performing a sample geocode against the configured
    // (hardcoded) geo service URL. Does not persist.
    public function post_geoservice_handler($request)
    {
        $key = $request['bmltwf_geo_service_key'] ?? false;

        $code = 422;

        if (empty($key)) {
            return $this->bmltwf_rest_error(__('Empty geo service API key parameter', 'bmlt-workflow'), $code);
        }

        $status = $this->bmlt_integration->testGeoService($this->bmlt_integration->geoServiceBaseUrl(), $key);

        if (is_wp_error($status)) {
            return $this->bmltwf_rest_error($status->get_error_message(), $code);
        }

        switch ($status) {
            case 200:
                return $this->bmltwf_rest_success(array(
                    'message' => __('Geo service connection test succeeded.', 'bmlt-workflow'),
                ));
            case 401:
                return $this->bmltwf_rest_error(__('Geo service rejected the API key (401). Check the key is correct.', 'bmlt-workflow'), $code);
            case 403:
                return $this->bmltwf_rest_error(__('Geo service account not approved yet (403).', 'bmlt-workflow'), $code);
            case 429:
                return $this->bmltwf_rest_error(__('Geo service rate limit exceeded (429). Please try again shortly.', 'bmlt-workflow'), $code);
            default:
                return $this->bmltwf_rest_error(__('Geo service test failed with status', 'bmlt-workflow') . ' ' . $status, $code);
        }
    }

    // Encrypts and persists the geo service API key (the URL is hardcoded).
    public function patch_geoservice_handler($request)
    {
        $key = $request['bmltwf_geo_service_key'];

        if (empty($key)) {
            return $this->bmltwf_rest_error(__('Empty geo service API key parameter', 'bmlt-workflow'), 422);
        }

        $this->debug_log("encrypting geo service API key");

        $nonce_salt = $this->secrets_nonce_salt();

        $encrypted = $this->secrets_encrypt($nonce_salt, $key);

        if (!is_array($encrypted)) {
            return $this->bmltwf_rest_failure('Error encrypting geo service API key.');
        }

        update_option('bmltwf_geo_service_key', $encrypted);
        update_option('bmltwf_geo_service_test_status', 'success');

        $data = array();
        $data["bmltwf_geo_service_test_status"] = "success";
        $data["message"] = __('Geo service configuration updated.', 'bmlt-workflow');

        return $this->bmltwf_rest_success($data);
    }
}
