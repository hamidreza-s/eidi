<?php

	// Show errors
	error_reporting(E_ALL);
	ini_set('display_errors', '1');

	// Include necessary files
	include_once '../sys/core/init.inc.php';
	
	// Load the calendar for January
	$cal = new Calendar($db, "2013-01-05 00:00:00");
	
	// Set up the page title and CSS files
	$page_title = "Events Calendar";
	$css_files = array('style.css');
	
	// Include the header
	include_once "assets/common/header.inc.php";

?>

<div id="content">

<?php
	
	// Display the calendar HTML
	echo $cal->buildCalendar();
	
?>

</div>
<?php

	// Include the footer
	include_once "assets/common/footer.inc.php";

?>	
