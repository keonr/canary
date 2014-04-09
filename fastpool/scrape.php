<?php
require_once('html_dom.php');
require_once('db.php');
if(!isset($_GET["anti-robots"])) {
	exit("Sorry Robots");
}
$cookie = "cookie.txt";
$login = Array(
	"username"	=> urlencode("malibuminingco"),
	"password"	=> urlencode("R66rest0n^^"),
	"page"		=> urldecode("login")
	);
//url-ify the data for the POST
foreach($login as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
rtrim($fields_string, '&');

$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, "https://fast-pool.com/index.php"); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
curl_setopt($ch, CURLOPT_POST, count($login));
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6');
$r = curl_exec($ch);
for($i = 0; $i < 2; $i++) {
	curl_setopt($ch, CURLOPT_URL, "https://fast-pool.com/index.php?page=account&action=transactions&start=".($i*30)); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6');
	$r = curl_exec($ch);
	cache_in_db($r);
}
curl_close($ch);      


function cache_in_db($r) {
	$html = str_get_html($r);
	$ret = $html->find("table", 2)->children(1);
	$db = db_open_connection();
	/*
	$query = "SELECT id FROM mining_audit ORDER BY id DESC";
	$res = $db->query($query);
	$row = $res->fetch_row();
	$newest_id = $row[0];
	*/
		foreach($ret->find("tr") as $tr) {
		$query = "INSERT INTO mining_audit (id, account, date, tx_type, status, payment_address, tx_number, block_num, amount) VALUES (";
	
			$query.= "'".trim($tr->children(0)->plaintext) ."', ";
			$query.= "'".trim($tr->children(1)->plaintext) ."', ";
			$query.= "'".trim($tr->children(2)->plaintext) ."', ";
			$query.= "'".trim($tr->children(3)->plaintext) ."', ";
			$query.= "'".trim($tr->children(4)->plaintext) ."', ";
			$query.= "'".trim($tr->children(5)->plaintext) ."', ";
			$query.= "'".trim($tr->children(6)->plaintext) ."', ";
			$query.= "'".trim($tr->children(7)->plaintext) ."', ";
			$query.= "'".trim(str_replace(",", "", $tr->children(8)->plaintext)) ."')";
			
			$db->query($query);
			//echo $db->error."<br>";
			//echo $query."<br>";
		}
		
		db_close_connection($db);
}

?> 	
