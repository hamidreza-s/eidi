<?php
// Include necessary files
include_once '../sys/core/init.inc.php';
// Load the admin object
$obj = new Admin($dbo);
// Generate a salted hash of "admin"
$pass = $obj->testSaltedHash("admin");
echo 'Hash of "admin":<br />', $pass, "<br /><br />";
?>