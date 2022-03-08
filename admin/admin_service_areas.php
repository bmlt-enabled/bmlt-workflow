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
                    <service_body id="6" name="Sydney East" type="AS">
                        <service_body_type>Area Service Committee</service_body_type>
                        <description>Eastern Sydney Metropolitan Area</description>
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
                </service_bodies>
            </service_body>
            <service_body id="8" name="Canberra/A.C.T. Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <helpline>+61484386301</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="12" admin_type="direct" admin_name="Jamie W"/>
                        <editor id="13" admin_type="direct" admin_name="Sinclair"/>
                        <editor id="16" admin_type="direct" admin_name="Bruce"/>
                        <editor id="17" admin_type="direct" admin_name="MarkD"/>
                        <editor id="20" admin_type="direct" admin_name="michaeld"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
            </service_body>
            <service_body id="9" name="Gold Coast Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <helpline>+61755914522</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="16" admin_type="inherit" admin_name="Bruce"/>
                        <editor id="17" admin_type="inherit" admin_name="MarkD"/>
                        <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                        <editor id="23" admin_type="direct" admin_name="gcyap"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
            </service_body>
            <service_body id="10" name="Newcastle and Hunter Valley Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <helpline>+61295196200</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
            <service_body id="11" name="Northern Territory Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="16" admin_type="inherit" admin_name="Bruce"/>
                        <editor id="17" admin_type="inherit" admin_name="MarkD"/>
                        <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                        <editor id="22" admin_type="direct" admin_name="ntyap"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
            </service_body>
            <service_body id="12" name="NSW Central Coast Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <helpline>+61243250524</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="16" admin_type="inherit" admin_name="Bruce"/>
                        <editor id="17" admin_type="inherit" admin_name="MarkD"/>
                        <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="36" admin_type="direct" admin_name="nswccphoneline"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
            </service_body>
            <service_body id="13" name="NSW Far North Coast Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <helpline>+61266807280</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="17" admin_type="inherit" admin_name="MarkD"/>
                        <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                        <editor id="28" admin_type="direct" admin_name="nswfncyap"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
            </service_body>
            <service_body id="14" name="NSW South Coast Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <helpline>+61295196200</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
            <service_body id="15" name="Greater Queensland Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <helpline>+61733915045</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                    <service_body id="32" name="Cairns" type="AS">
                        <service_body_type>Area Service Committee</service_body_type>
                        <helpline>+61740543483</helpline>
                        <parent_service_body id="15" type="AS">Greater Queensland Area</parent_service_body>
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
                    <service_body id="33" name="Townsville" type="AS">
                        <service_body_type>Area Service Committee</service_body_type>
                        <helpline>+61747552489</helpline>
                        <parent_service_body id="15" type="AS">Greater Queensland Area</parent_service_body>
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
                                <editor id="12" admin_type="direct" admin_name="Jamie W"/>
                                <editor id="13" admin_type="direct" admin_name="Sinclair"/>
                                <editor id="16" admin_type="direct" admin_name="Bruce"/>
                                <editor id="17" admin_type="direct" admin_name="MarkD"/>
                                <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                                <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                                <editor id="30" admin_type="direct" admin_name="yaptsv"/>
                                <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                            </meeting_list_editors>
                            <observers>
                                <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                            </observers>
                        </editors>
                    </service_body>
                    <service_body id="34" name="Toowoomba" type="AS">
                        <service_body_type>Area Service Committee</service_body_type>
                        <helpline>+61416811598</helpline>
                        <parent_service_body id="15" type="AS">Greater Queensland Area</parent_service_body>
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
                </service_bodies>
            </service_body>
            <service_body id="16" name="South Australia Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <helpline>+61882314233</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="12" admin_type="direct" admin_name="Jamie W"/>
                        <editor id="13" admin_type="direct" admin_name="Sinclair"/>
                        <editor id="16" admin_type="direct" admin_name="Bruce"/>
                        <editor id="17" admin_type="direct" admin_name="MarkD"/>
                        <editor id="18" admin_type="direct" admin_name="DanI"/>
                        <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                        <editor id="27" admin_type="direct" admin_name="yapsa"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
            </service_body>
            <service_body id="17" name="Tasmania Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="16" admin_type="inherit" admin_name="Bruce"/>
                        <editor id="17" admin_type="inherit" admin_name="MarkD"/>
                        <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="31" admin_type="direct" admin_name="navic"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
            </service_body>
            <service_body id="18" name="Victoria Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <helpline>+61395252833</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="12" admin_type="direct" admin_name="Jamie W"/>
                        <editor id="13" admin_type="direct" admin_name="Sinclair"/>
                        <editor id="16" admin_type="direct" admin_name="Bruce"/>
                        <editor id="17" admin_type="direct" admin_name="MarkD"/>
                        <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                        <editor id="24" admin_type="direct" admin_name="navichelpline"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="31" admin_type="direct" admin_name="navic"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
            </service_body>
            <service_body id="19" name="Western Australia Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <helpline>+61892278361</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="17" admin_type="inherit" admin_name="MarkD"/>
                        <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                        <editor id="26" admin_type="direct" admin_name="yapwa"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
            </service_body>
            <service_body id="20" name="Australian Region Outreach" type="RS">
                <service_body_type>Regional Service Conference</service_body_type>
                <description>RSC Outreach Committee</description>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
            <service_body id="21" name="NSW Mid North Coast Port Macquarie Area" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <description>NSW Mid North Coast Port Macquarie Area</description>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
            <service_body id="23" name="NSW Country" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <description>Canowindra and other country NSW towns.</description>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
            <service_body id="24" name="Sunshine Coast" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <description>Mooloolaba and Northern Queensland coast</description>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
            </service_body>
            <service_body id="26" name="Manning Great Lakes" type="GR">
                <service_body_type>Group</service_body_type>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
            </service_body>
            <service_body id="28" name="NSW Coffs Kempsey" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <helpline>+61459432270</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="20" admin_type="inherit" admin_name="michaeld"/>
                        <editor id="25" admin_type="direct" admin_name="coffscoastphoneline"/>
                        <editor id="29" admin_type="inherit" admin_name="nigelb"/>
                        <editor id="37" admin_type="inherit" admin_name="Anitak"/>
                    </meeting_list_editors>
                    <observers>
                        <editor id="32" admin_type="inherit" admin_name="regionalpr"/>
                    </observers>
                </editors>
            </service_body>
            <service_body id="31" name="Blue Mountains" type="AS">
                <service_body_type>Area Service Committee</service_body_type>
                <helpline>+61295196200</helpline>
                <parent_service_body id="1" type="AS">Australian Region</parent_service_body>
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
                        <editor id="12" admin_type="direct" admin_name="Jamie W"/>
                        <editor id="13" admin_type="direct" admin_name="Sinclair"/>
                        <editor id="16" admin_type="direct" admin_name="Bruce"/>
                        <editor id="17" admin_type="direct" admin_name="MarkD"/>
                        <editor id="20" admin_type="direct" admin_name="michaeld"/>
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
// $arr = json_decode($response['body'],true);

var_dump($arr);
$sblist = array();

$sblist = recurse_service_bodies($arr['service_body'],$sblist);
echo "<h1>hows it going</h1>";
var_dump($sblist);

foreach ($sblist as $item)
{
    echo '<br>'+$item+'<br>';
}

// array(1) { ["service_body"]=> 
    //array(7) { ["@attributes"]=> 
        //array(3) { ["id"]=> string(1) "1" ["name"]=> string(17) "Australian Region" ["type"]=> string(2) "AS" } 
        //["service_body_type"]=> string(22) "Area Service Committee" 
        //["description"]=> string(17) "Australian Region" 
        //["uri"]=> string(9) "na.org.au" ["helpline"]=> string(12) "+61488811247" ["editors"]=> array(3) { ["service_body_editors"]=> array(1) { ["editor"]=> array(1) { ["@attributes"]=> array(3) { ["id"]=> string(1) "2" ["admin_type"]=> string(6) "direct" ["admin_name"]=> string(27) "Meetings List Administrator" } } } ["meeting_list_editors"]=> array(1) { ["editor"]=> array(12) { [0]=> array(1) { ["@attributes"]=> array(3) { ["id"]=> string(1) "4" ["admin_type"]=> string(6) "direct" ["admin_name"]=> string(30) "Meetings List Administrator 03" } } [1]=> array(1) { ["@attributes"]=> array(3) { ["id"]=> string(1) "5" ["admin_type"]=> string(6) "direct" ["admin_name"]=> string(30) "Meetings List Administrator 04" } } [2]=> array(1) { ["@attributes"]=> array(3) { ["id"]=> string(1) "6" ["admin_type"]=> string(6) "direct" ["admin_name"]=> string(30) "Meetings List Administrator 05" } } [3]=> array(1) { ["@attributes"]=> array(3) { ["id"]=> string(1) "7" ["admin_type"]=> string(6) "direct" ["admin_name"]=> string(30) "Meetings List Administrator 07" } } [4]=> array(1) { ["@attributes"]=> array(3) { ["id"]=> string(1) "8" ["admin_type"]=> string(6) "direct" ["admin_name"]=> string(30) "M

function recurse_service_bodies($arr, $sblist)
{
    if(array_key_exists('service_bodies', $arr))
    {
        echo "size is "+count($arr['service_bodies']);
        foreach ($arr['service_bodies'] as $idx)
        {
            echo "<br>** recursing<br><br>";
            var_dump($idx);
            echo "<br>** recursing<br>";
            recurse_service_bodies($idx, $sblist);
        }
    }
    if(array_key_exists('@attributes', $arr))
    {
            $sblist[] = $arr['@attributes']['name'];
    }
    return $sblist;
}
