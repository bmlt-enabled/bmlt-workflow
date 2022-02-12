<?php

if ( !current_user_can( 'manage_options' ) ) {
    exit;
}

?>

<h1>hello</h1>

<?php

settings_fields('list_service_areas_field');
do_settings_sections('list_service_areas_section');

?>