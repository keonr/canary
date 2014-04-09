<?php
$select = $_GET["sel"];

switch($select) {
	case 'DOGE': 
		
		$_GET['req'] = "dt,am";
		$_GET['sort'] = "date,asc";
		$_GET['epoch'] = true;
		include 'api.php';
	break;
	
	case '1': 
		
		$_GET['range'] = "1";
		$_GET['sort'] = "date,asc";
		$_GET['epoch'] = true;
		include 'average.php';
	break;

	case '7': 
		
		$_GET['range'] = "7";
		$_GET['sort'] = "date,asc";
		$_GET['epoch'] = true;
		include 'average.php';
	break;

	case '14': 
		
		$_GET['range'] = "14";
		$_GET['sort'] = "date,asc";
		$_GET['epoch'] = true;
		include 'average.php';
	break;

}

?>
