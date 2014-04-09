<?php
require_once('db.php');
header("content-type: application/json");

$db = db_open_connection();
$reqs	= explode(",", $db->real_escape_string($_GET["req"]));
$sort 	= explode(",", $db->real_escape_string($_GET["sort"]));
if(isset($_GET['limit'])) {
	$limit	= $db->real_escape_string($_GET['limit']);
} else {
	$limit = false;
}

$epoch 	= false;
if($_GET["epoch"] == "true" || $_GET["epoch"] == true) {
	$epoch = true;
}
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
function get_pos($str) {
	global $map;
	$i = 0;
	foreach($map as $key => $value) {
		if($value) {
			if($key == $str) {
				return $i;
			}
			$i++;
		}
	}
	return -1;
}
$query = "SELECT ";
foreach($map as $key => $value) {
	if($value) {
		$query .= $key .", ";
	}
}
$query = rtrim($query, ", ");
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
	$pos = get_pos("date");
	$ampos = get_pos("amount");
	if($pos != -1 && $epoch) {
		$row[$pos] = strtotime($row[$pos])*1000;
	}
	if($ampos != -1) {
		$row[$ampos] = floatval($row[$ampos]);
	}
	array_push($out, $row);
}
echo json_encode($out);
?>
