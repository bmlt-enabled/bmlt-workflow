<?php


class bmaw_submissions_rest_handlers
{

    public function get_submissions_handler()
    {

        global $wpdb;
        global $bmaw_submissions_table_name;

        $result = $wpdb->get_results('SELECT * FROM ' . $bmaw_submissions_table_name, ARRAY_A);
        return $result;
    }

    public function get_service_areas()
    {
        // call bmlt for service area list
        // add list of wp uids with access
        // return as array of all service areas

        global $wpdb;
        global $bmaw_service_areas_table_name;
        global $bmaw_service_areas_access_table_name;

        $req = array();
        $req['admin_action'] = 'get_service_body_info';
        $req['flat'] = '';
        $bmlt_integration = new BMLTIntegration;

        // get an xml for a workaround
        $response = $bmlt_integration->postConfiguredRootServerRequestSemantic('local_server/server_admin/xml.php', $req);
        if (is_wp_error($response)) {
            wp_die("BMLT Configuration Error - Unable to retrieve meeting formats");
        }

        $xml = simplexml_load_string($response['body']);
        $arr = json_decode(json_encode($xml), 1);

        error_log(vdump($arr));

        $sblist = array();
        $idlist = array();

        // make our list of service bodies
        foreach ($arr['service_body'] as $key => $value) {
            error_log("looping key = " . $key);
            if (array_key_exists('@attributes', $value)) {
                $sbid = $value['@attributes']['id'];
                $idlist[] = $sbid;
                $sblist[$sbid] = array('name' => $value['@attributes']['name']);
            }
        }

        // update our service area list in the database in case there have been some new ones added
        error_log("get ids");
        $sqlresult = $wpdb->get_col('SELECT service_area_id FROM ' . $bmaw_service_areas_table_name . ';', 0);
        // error_log($sql);
        // $sqlresult = $wpdb->get_col($sql, 0);
        error_log(vdump($sqlresult));
        $missing = array_diff($idlist, $sqlresult);
        error_log("missing ids");
        error_log(vdump($missing));

        foreach ($missing as $value) {
            $sql = $wpdb->prepare('INSERT into ' . $bmaw_service_areas_table_name . ' set service_area_id="%d", show_on_form=NULL', $value);
            $wpdb->query($sql);
        }

        error_log("our sblist");
        error_log(vdump($sblist));

        // make our group membership lists
        foreach ($sblist as $key => $value) {
            error_log("getting memberships for " . $key);
            $sql = $wpdb->prepare('SELECT DISTINCT wp_uid from ' . $bmaw_service_areas_access_table_name . ' where service_area_id = "%d"', $key);
            $result = $wpdb->get_col($sql, 0);
            error_log(vdump($result));
            $sblist[$key]['membership'] = implode(',', $result);
        }
    }
}
