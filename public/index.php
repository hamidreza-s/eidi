<?php

	// Show errors
	error_reporting(E_ALL);
	ini_set('display_errors', '1');

	// Include necessary files
	include_once '../sys/core/init.inc.php';
	
	// Load the calendar for January
	$cal = new Calendar($db, "2013-01-05 00:00:00");
	
	// Display the calendar HTML
	echo $cal->buildCalendar();

	
?>
