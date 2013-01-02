<?php
	// Include necessary files
	require_once '../sys/core/init.inc.php';
	
	// Output the header
	$page_title = "Add/Edit Event";
	$css_files = array('style.css', 'admin.css');
	require_once 'assets/common/header.inc.php';
	
	// Load the calendar
	$cal = new Calendar($db);
?>

<div id="content">
	<?php echo $cal->displayForm(); ?>
</div>

<?php
	// Output the footer
	require_once 'assets/common/header.inc.php';
?>