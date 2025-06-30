<?php
$output = shell_exec('/usr/local/bin/import-sql.sh 2>&1');
echo "<pre>$output</pre>";
?>
