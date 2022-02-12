<?php
$screen = get_current_screen(); 
print_r($screen);
echo '<div class="wrap">
<h1>BMLT Meeting Admin Workflow Settings</h1>
<form method="post" action="options.php">';
        
    settings_fields( 'bmaw-settings-group' ); // settings group name
    do_settings_sections( 'bmaw-settings' ); // just a page slug
    submit_button();

echo '</form></div>';

?>

