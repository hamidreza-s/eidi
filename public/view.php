<?php
	
	// Make sure the event ID was passed
	if (isset($_GET['event_id']))
	{
		// Make sure the ID is an integer
		$id = preg_replace('/[^0-9]/', '', $_GET['event_id']);
		
		// If the ID isn't valid, send the user to the main page
		if(empty($id))
		{
			header("Location: ./");
			exit;
		}
	}
	else
	{
		// Send the user to the main page
		header("Location: ./");
		exit;
	}
	
	// Include necessary files
	include_once '../sys/core/init.inc.php';
	
	// Output the header
	$page_title = "View Event";
	$css_files = array("style.css");
	include_once 'assets/common/header.inc.php';
	
	// Load the calendar
	$cal = new Calendar($db);
	
?>

<div id="content">
	<?php echo $cal->displayEvents($id); ?>
	<a href="./">&laquo; Back to the calendar</a>
</div>

<?php

	// Output the footer
	include_once 'assets/common/footer.inc.php';
	
?>















