<?php

if (!defined('ABSPATH')) exit; // die if being called directly

if (!class_exists('BMLTIntegration')) {
	require_once(BMAW_PLUGIN_DIR . 'admin/bmlt_integration.php');
}

wp_nonce_field('wp_rest', '_wprestnonce');

$change['admin_action']='get_service_body_info';
$bmlt_integration = new BMLTIntegration;

// get an xml for a workaround
$response = $bmlt_integration->postConfiguredRootServerRequestSemantic('local_server/server_admin/xml.php', $change);
if( is_wp_error( $response ) ) {
    wp_die("BMLT Configuration Error - Unable to retrieve meeting formats");
}
$response['body']=<<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<service_bodies xmlns="http://na.org.au"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://na.org.au:443/main_server/client_interface/xsd/HierServiceBodies.php">
    <service_body id="1" name="Australian Region" type="AS">
        <service_body_type>Area Service Committee</service_body_type>
        <description>Australian Region</description>
        <uri>na.org.au</uri>
        <helpline>+61488811247</helpline>
        <editors>
            <service_body_editors>
                <editor id="2" admin_type="direct" admin_name="Meetings List Administrator"/>
            </service_body_editors>
            <meeting_list_editors>
                <editor id="4" admin_type="direct" admin_name="Meetings List Administrator 03"/>
                <editor id="5" admin_type="direct" admin_name="Meetings List Administrator 04"/>
                <editor id="6" admin_type="direct" admin_name="Meetings List Administrator 05"/>
                <editor id="7" admin_type="direct" admin_name="Meetings List Administrator 07"/>
                <editor id="8" admin_type="direct" admin_name="Meetings List Administrator 08"/>
                <editor id="9" admin_type="direct" admin_name="Tony S"/>
                <editor id="13" admin_type="direct" admin_name="Sinclair"/>
                <editor id="16" admin_type="direct" admin_name="Bruce"/>
                <editor id="17" admin_type="direct" admin_name="MarkD"/>
                <editor id="20" admin_type="direct" admin_name="michaeld"/>
                <editor id="29" admin_type="direct" admin_name="nigelb"/>
                <editor id="37" admin_type="direct" admin_name="Anitak"/>
            </meeting_list_editors>
            <observers>
                <editor id="32" admin_type="direct" admin_name="regionalpr"/>
            </observers>
        </editors>
        <service_bodies>
            <service_body id="2" name="Sydney Metro" type="MA">
                <service_body_type>Metro Area</service_body_type>
                <description>Sydney Metropolitan Service Committee. Provides H&amp;amp;amp;amp;I, PI and Phoneline services for the combined Sydney Areas, North, South, East and West.</description>
                <helpline>+61295196200</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
                <editors>
                    <service_body_editors>
                        <editor id="2" admin_type="direct" admin_name="Meetings List Administrator"/>
                        <editor id="21" admin_type="direct" admin_name="SydneyMetro"/>
                    </service_body_editors>
                    <meeting_list_editors>
                        <editor id="4" admin_type="direct" admin_name="Meetings List Administrator 03"/>
                        <editor id="5" admin_type="direct" admin_name="Meetings List Administrator 04"/>
                        <editor id="6" admin_type="direct" admin_name="Meetings List Administrator 05"/>
                        <editor id="7" admin_type="direct" admin_name="Meetings List Administrator 07"/>
                        <editor id="8" admin_type="direct" admin_name="Meetings List Administrator 08"/>
                        <editor id="9" admin_type="direct" admin_name="Tony S"/>
                        <editor id="12" admin_type="direct" admin_name="Jamie W"/>
                        <editor id="13" admin_type="direct" admin_name="Sinclair"/>
                        <editor id="16" admin_type="direct" admin_name="Bruce"/>
                        <editor id="17" admin_type="direct" admin_name="MarkD"/>
                        <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
                <service_bodies>
                    <service_body id="3" name="Sydney North" type="AS">
                        <service_body_type>Area Service Committee</service_body_type>
                        <description>Northern Metropolitan Area of Sydney</description>
                        <helpline>+61295196200</helpline>
                        <parent_service_body id="2" type="MA">Sydney Metro</parent_service_body>
                        <editors>
                            <service_body_editors>
                                <editor id="2" admin_type="direct" admin_name="Meetings List Administrator"/>
                                <editor id="21" admin_type="direct" admin_name="SydneyMetro"/>
                            </service_body_editors>
                            <meeting_list_editors>
                                <editor id="4" admin_type="direct" admin_name="Meetings List Administrator 03"/>
                                <editor id="5" admin_type="direct" admin_name="Meetings List Administrator 04"/>
                                <editor id="6" admin_type="direct" admin_name="Meetings List Administrator 05"/>
                                <editor id="7" admin_type="direct" admin_name="Meetings List Administrator 07"/>
                                <editor id="8" admin_type="direct" admin_name="Meetings List Administrator 08"/>
                                <editor id="9" admin_type="direct" admin_name="Tony S"/>
                                <editor id="12" admin_type="direct" admin_name="Jamie W"/>
                                <editor id="13" admin_type="direct" admin_name="Sinclair"/>
                                <editor id="16" admin_type="direct" admin_name="Bruce"/>
                                <editor id="17" admin_type="direct" admin_name="MarkD"/>
                                <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                                <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                                <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                            </meeting_list_editors>
                            <observers>
                                <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                            </observers>
                        </editors>
                    </service_body>
                    <service_body id="4" name="Sydney South" type="AS">
                        <service_body_type>Area Service Committee</service_body_type>
                        <description>Southern Sydney Metropolitan Area</description>
                        <helpline>+61295196200</helpline>
                        <parent_service_body id="2" type="MA">Sydney Metro</parent_service_body>
                        <editors>
                            <service_body_editors>
                                <editor id="2" admin_type="direct" admin_name="Meetings List Administrator"/>
                                <editor id="21" admin_type="direct" admin_name="SydneyMetro"/>
                            </service_body_editors>
                            <meeting_list_editors>
                                <editor id="4" admin_type="direct" admin_name="Meetings List Administrator 03"/>
                                <editor id="5" admin_type="direct" admin_name="Meetings List Administrator 04"/>
                                <editor id="6" admin_type="direct" admin_name="Meetings List Administrator 05"/>
                                <editor id="7" admin_type="direct" admin_name="Meetings List Administrator 07"/>
                                <editor id="8" admin_type="direct" admin_name="Meetings List Administrator 08"/>
                                <editor id="9" admin_type="direct" admin_name="Tony S"/>
                                <editor id="12" admin_type="inherit" admin_name="Jamie W"/>
                                <editor id="13" admin_type="direct" admin_name="Sinclair"/>
                                <editor id="16" admin_type="direct" admin_name="Bruce"/>
                                <editor id="17" admin_type="direct" admin_name="MarkD"/>
                                <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                                <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                                <editor id="35" admin_type="direct" admin_name="sydneysouth"/>
                                <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                            </meeting_list_editors>
                            <observers>
                                <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                            </observers>
                        </editors>
                    </service_body>
                    <service_body id="5" name="Sydney West" type="AS">
                        <service_body_type>Area Service Committee</service_body_type>
                        <description>Greater Western Sydney Area</description>
                        <helpline>+61295196200</helpline>
                        <parent_service_body id="2" type="MA">Sydney Metro</parent_service_body>
                        <editors>
                            <service_body_editors>
                                <editor id="2" admin_type="direct" admin_name="Meetings List Administrator"/>
                                <editor id="21" admin_type="direct" admin_name="SydneyMetro"/>
                            </service_body_editors>
                            <meeting_list_editors>
                                <editor id="4" admin_type="direct" admin_name="Meetings List Administrator 03"/>
                                <editor id="5" admin_type="direct" admin_name="Meetings List Administrator 04"/>
                                <editor id="6" admin_type="direct" admin_name="Meetings List Administrator 05"/>
                                <editor id="7" admin_type="direct" admin_name="Meetings List Administrator 07"/>
                                <editor id="8" admin_type="direct" admin_name="Meetings List Administrator 08"/>
                                <editor id="9" admin_type="direct" admin_name="Tony S"/>
                                <editor id="12" admin_type="inherit" admin_name="Jamie W"/>
                                <editor id="13" admin_type="direct" admin_name="Sinclair"/>
                                <editor id="16" admin_type="inherit" admin_name="Bruce"/>
                                <editor id="17" admin_type="inherit" admin_name="MarkD"/>
                                <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                                <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                                <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                            </meeting_list_editors>
                            <observers>
                                <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                            </observers>
                        </editors>
                    </service_body>
        </service_bodies>
    </service_body>
</service_bodies>
EOD;

$xml = simplexml_load_string($response['body']);
$arr = json_decode(json_encode($xml),1);
// when xml gets fixed
// $arr = json_decode($response['body'],true);

$sblist = array();
$sblist = recurse_service_bodies($arr['service_body'],$sblist);

// foreach ($sblist as $item)
// {
// //     echo '<br>'.$item.'<br>';
// var_dump($item);
// }

function recurse_service_bodies($arr, $sblist)
{
    if(array_key_exists('service_bodies', $arr))
    {
        foreach ($arr['service_bodies']['service_body'] as $idx)
        {
            $sblist = recurse_service_bodies($idx, $sblist);
        }
    }
    if(array_key_exists('@attributes', $arr))
    {
            $sblist[] = array('name'=>$arr['@attributes']['name'],'id'=>$arr['@attributes']['id']);
    }
    return $sblist;
}
?>

<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2>Service Area Configuration</h2>
    <table class="bmaw-userlist-table"">
<thead>
  <tr>
    <th class="bmaw-userlist-header">Service Area</th>
    <th class="bmaw-userlist-header">Wordpress Users with Access</th>
  </tr>
</thead>
<tbody>
    <?php
    foreach ($sblist as $item) {
        echo '<tr class="bmaw-userlist-row">';
        echo '<td>'.$item['name'].'</td>';
        echo '<td><select class="bmaw-userlist" id="bmaw_userlist_id_'.$item['id'].'" style="width: auto"></select></td></tr>';
    }
    echo '</tr>';
    ?>
</tbody>
</table>

</div>
