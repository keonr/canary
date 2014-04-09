<?php
require_once('db.php');
header("content-type: application/json");

$db = db_open_connection();
$sort 	= explode(",", $db->real_escape_string($_GET["sort"]));
if(isset($_GET['limit'])) {
	$limit	= $db->real_escape_string($_GET['limit']);
} else {
	$limit = false;
}
if(isset($_GET["range"])){
	$range = $_GET["range"];
} else {
	$range = 1;
}
$epoch 	= false;
if($_GET["epoch"] == "true" || $_GET["epoch"] == true) {
	$epoch = true;
}

/*
$map    = Array(
			"id"					=> in_array("id", $reqs),
			"account"				=> in_array("ac", $reqs),
			"date"					=> in_array("dt", $reqs),
			"tx_type"				=> in_array("tx", $reqs),
			"status"				=> in_array("st", $reqs),
			"payment_address"		=> in_array("pa", $reqs),
			"tx_number"				=> in_array("tn", $reqs),
			"block_num"				=> in_array("bn", $reqs),
			"amount"				=> in_array("am", $reqs)

		);
*/
$query = "SELECT date, amount";
$query .= " FROM mining_audit WHERE tx_type='Credit'";
if(sizeof($sort > 0)) {
	$query .= " ORDER BY " . $sort[0] . " " . strtoupper($sort[1]);
}
if($limit) {
	$query .= " LIMIT $limit";
}
$r = $db->query($query);

$out = Array();
date_default_timezone_set('America/Los_Angeles');

while($row = $r->fetch_row()) {
	if($epoch) {
		$row[0] = strtotime($row[0])*1000;
	}
	$row[1] = floatval($row[1]);
	
	array_push($out, $row);
}

$base = $out[0][0];
$total = 0.0;
$count = 0;
$out2 = Array();
for($i = 0; $i < sizeof($out); $i++) {
	if($out[$i][0] - $base >= 24*60*60*1000*$range) {
		array_push($out2, Array($base + 12*60*60*1000*$range, ($total/$count)));
		$total = 0;
		$count = 0;
		$base = $out[$i][0];
	} else {
		$total += $out[$i][1];
		$count++;
	}
}
echo json_encode($out2);
?>
