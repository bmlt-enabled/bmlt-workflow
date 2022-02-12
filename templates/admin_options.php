<?php

// if ( !current_user_can( 'manage_options' ) ) {
//     exit;
// }

// 

// <h1>hello</h1>

// <?php

// settings_fields('list_service_areas_field');
// do_settings_sections('list_service_areas_section');
update_option("homepage_text", array(
    "Committee1" => array("e1"=>"email 1", "e2"=>"email 1.1"),
    "Committee2" => array("e1"=>"email 2", "e2"=>"email 2.1"),
    ));
echo '<div class="wrap">
<h1>BMAW Settings</h1>
<form method="post" action="options.php">';
        
    settings_fields( 'bmaw-settings-group' ); // settings group name
    do_settings_sections( 'bmaw-settings' ); // just a page slug
    submit_button();

echo '</form></div>';

?>

