<?php
/*
	Simple Tool for Displaying Multipool API data in a 
	human-readable format. Uses Cryptsy and Coinbase API's
	as well to convert multipool data to BTC and then to USD.
	
	Written by: Keon Ramezani - ramezanik@gmail.com
	For: Canary Cryptocurrency Mining, Co.
	Last Revision: 9 April, 2014

*/

//Multipool API Key
$api_key	= "c8bc46b6e28f718162b96e6e1fa55d02937adbbccd1c4dcc38c9cc47fd93e940";
//Cache of Coinbase BTC price
$_CB_PRICE = -1;

/* function get_cryptsy_prices
 * Fetches most recent trade prices
 * of all to-BTC markets on Cryptsy
 * @returns Array - Each array key is
 * the symbol for the currency in question (all caps), each element
 * contains 'lasttrade' (last trade to BTC price), 'marketid' (cryptsy market ID),
 * 'name' (coin name), and 'volume' (trade volume in BTC).
 */
function get_cryptsy_prices() {
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, "http://pubapi.cryptsy.com/api.php?method=marketdatav2"); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
	$r = curl_exec($ch); 
	curl_close($ch);      
	$r = json_decode($r, true);
	if($r["success"]) {
		$r = $r["return"]["markets"];
	} else {
		exit("{\"error\": \"Could not get market data.\"}");
	}
	$out = Array();
	foreach($r as $value) {
		if($value["secondarycode"] == "BTC") {
			$out[$value["primarycode"]] = Array(
										  "lasttrade"	=> floatval($value["lasttradeprice"]),
										  "marketid"	=> intval($value["marketid"]),
										  "name"		=> $value["primaryname"],
										  "volume"		=> floatval($value["volume"])
				
			);
		}
	}
	return $out;				
}

/* function get_multipool_data
 * Fetches Multipool API data, returns array of info
 * @param $api_key - your multipool API key
 * @return Array - returns multipool API data in array format
 * returned data is as multipool provides.
 */
function get_multipool_data($api_key) {
	//cURL request for the data
	$ch = curl_init(); 
	$url = "http://api.multipool.us/api.php?api_key=$api_key";
	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$r = curl_exec($ch); 
	curl_close($ch);      
	
	//Decode JSON
	return(json_decode($r, true));
}


/* function coinbase_btc_price
 * Fetches the current BTC to USD exchange rate from coinbase
 * and caches that prices in local memory so CB doesn't get
 * queried multiple times in a short window of time
 * @return BTC-USD price, parsed as a float
 */
function coinbase_btc_price() {
	global $_CB_PRICE;
	if($_CB_PRICE == -1) {
		$ch = curl_init();  
		curl_setopt($ch, CURLOPT_URL, "https://coinbase.com/api/v1/currencies/exchange_rates"); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$r = curl_exec($ch); 
		curl_close($ch);      
		$r = json_decode($r);
		$_CB_PRICE = floatval($r->btc_to_usd);
		return $_CB_PRICE;
	} else {
		return $_CB_PRICE;
	}
	
}


/****************************************************
 ****** UNRULY CODE AHEAD - WARNING *****************
 ***************************************************/


//Call APIs and store returned data for use.
$cr = get_cryptsy_prices();
$mp = get_multipool_data($api_key);
$cb = coinbase_btc_price();

//Initiate some variables for later use
$sum  = 0.0;
$hsum = 0.0;
$mined_in_btc = Array();
$workers 	  = Array();

//Waste some RAM for less typing/slightly cleaner code
$curr = $mp["currency"];
$work = $mp["workers"];

//Iterate through multipool data and grab any currency that has been mined.
//Push amount mined (multiplied by cryptsy's current conversion rate to BTC) and store in
//Array $mined_in_btc where each array key is the currency symbol (ex. DOGE). Stored value is 
//how much of that currency has been mined so far, value in BTC
foreach($curr as $k => $r) {
	if(floatval($r["confirmed_rewards"]) > 0) {
		$mined_in_btc[$k] = floatval($r["confirmed_rewards"]) * $cr[strtoupper($k)]["lasttrade"];
	}
}

//Print some stuff to the screen, make a nice clean table
echo "<h3>Mined So Far</h3>";
echo "<table><thead><tr><td><strong>Currency</strong></td><td><strong>Value (BTC)</strong></td><td><strong>Value (USD)</strong></td></tr></thead>";
echo "<tbody>";

//Iterates through $mined_in_btc, reporting currency and nicely formatted output for BTC and USD values of each coin mined
foreach($mined_in_btc as $k => $v) {
	echo "<tr><td>". strtoupper($k) . "</td><td> " . sprintf('%.9F',$v) . " BTC</td><td> $" . sprintf('%.2F',$v*$cb) . " USD</td></tr>";
	//Running total/sum of all currencies mined for later display
	$sum += $v;
}
echo "</tbody></table><br>";
//Print out sum of mined currencies in BTC
echo "<strong>Total: </strong>" . sprintf('%.9F',$sum) . " BTC<br>";

//Show same value in USD. Probably needs to use number_format() function for thousands separator, etc...
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$" . sprintf('%.2F',$sum*$cb) . " USD @ \$". sprintf('%.2F',$cb)."/BTC<br><br>";

//Iterate through multipool data of current workers and 
//Create array of all workers currently producing a hashrate > 0
foreach($work as $c => $r) {
	foreach($r as $w => $h) {
		if(floatval($h["hashrate"]) > 0) {
			$workers[$w] += floatval($h["hashrate"]);
		}
	}
}

//Print current hash rates to screen, neatly in a table
echo "<h3>Current Hashrates</h3>";
echo "<table><thead><tr><td><strong>Worker</strong></td><td><strong>Hashrate</strong></td></tr></thead>";
echo "<tbody>";
//Iterates through $workers array (created above) and prints hash rate of each worker, neatly with thousands separator
foreach($workers as $w => $h) {
	echo "<tr><td>". $w . "</td><td> " . number_format($h, 0, '', ',') . " kH/s</td></tr>";
	//Creates running total of all workers' hashrates
	$hsum += floatval($h);
}
echo "</tbody></table><br>";
//Print total hashrate
echo "<strong>Total: </strong>" . number_format($hsum, 0, '', ',') . " kH/s<br>";


?>
