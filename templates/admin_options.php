<?php

// if ( !current_user_can( 'manage_options' ) ) {
//     exit;
// }

// 

// <h1>hello</h1>

// <?php

// settings_fields('list_service_areas_field');
// do_settings_sections('list_service_areas_section');

echo '<div class="wrap">
<h1>BMAW Settings</h1>
<form method="post" action="options.php">';
        
    settings_fields( 'bmaw_settings_group' ); // settings group name
    do_settings_sections( 'bmaw_settings' ); // just a page slug
    submit_button();

echo '</form></div>';

?>