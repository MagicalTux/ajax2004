<?php
require_once('js_func.php');

$cmd = $_POST['cmd'];

if (get_magic_quotes_gpc()) $cmd = stripslashes($cmd);
$cmd = parseJS($cmd);
$param = $cmd[1];

echo '// JSCOMMIT'."\n";
flush();
//
//sleep(5);
echo 'alert('.JSaddslashes('Hello '.$param[0]).');';
