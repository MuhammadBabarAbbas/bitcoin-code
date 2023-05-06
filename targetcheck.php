<?php

require_once('easybitcoin.php');
require_once('nonces.php');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bitcoin";

$bitcoin = new Bitcoin('mbabbas','Virtual786','localhost','8332');
//echo "Hash :: 0000000000000000000732a10705dd0d3502e43b78c57287f871800cb19cfd2f<br/>"; 
//$blockTemplate = $bitcoin->getblock('0000000000000000000732a10705dd0d3502e43b78c57287f871800cb19cfd2f');
///print_r($blockTemplate);
$blockTemplate = $bitcoin->getblocktemplate();
print_r($blockTemplate);
exit;
echo '7d77529b86bbe0d4759c0e32d913767cedd3b6927794cb4d4ba11b0a3f570a2e<br/>';
$data = '010000000001010000000000000000000000000000000000000000000000000000000000000000ffffffff2e03ab1e080004b16a4c5b04860b3d110cbb0f0e5b764ce95aeb787cb3112f426974436c7562204e6574776f726b2fffffffff023517034b000000001976a9142cc2b87a28c8a097f48fcc1d468ced6e7d39958d88ac0000000000000000';///266a24aa21a9ede0e1911dbdc9d047958a85899a96eeb499583d289b25cbe6f213f993fa18944e0120000000000000000000000000000000000000000000000000000000000000000000000000';

//convert from hex to binary
$coinbaseTransactionBin = hex2bin($data);
//hash it then convert from hex to binary
$firstHash = hex2bin(hash('sha256', $coinbaseTransactionBin));
//Hash it for the seconded time
//$firstHash = hash('sha256', $data);
$coinbaseTransactionId = hash('sha256', $firstHash);

echo $coinbaseTransactionId;

exit;
$version = $blockTemplate['version'];
$previousblockhash = $blockTemplate['previousblockhash'];
//$target = $blockTemplate['target']; //adjustment with difficult
$curtime = $blockTemplate['time']; //this or current time
$bits = $blockTemplate['bits'];
$height = $blockTemplate['height'];
$transactions = $blockTemplate['tx'];
$txids = array();
$i=0;
foreach($transactions as $transaction){
		array_push($txids, $transactions[$i]);
	$i++;
}
if(sizeof($txids) > 0){
	$txidsBEbinary = [];
	foreach ($txids as $txidBE) {
	    // covert to binary, then flip
	    $txidsBEbinary[] = binFlipByteOrder(hex2bin($txidBE));
	}
	$root = merkleroot($txidsBEbinary);
	$calulatedHash =  bin2hex(binFlipByteOrder($root));
	echo "Before<br/>";
	echo "Version: " . $version ."<br/>Prev block hash: ". $previousblockhash ."<br/> calculated hash: ". $calulatedHash ."<br/> time (curtime): ". $curtime ."<br/> bits: ". $bits;
	$version = littleEndian($version);
	$prevBlockHash = SwapOrder($previousblockhash);
	$rootHash = SwapOrder($calulatedHash);
	$time = littleEndian($curtime);
	$bits = SwapOrder($bits);
	echo "<br/><br/>After<br/>";
	echo "Version: " . $version ."<br/>Prev block hash: ". $prevBlockHash ."<br/> root hash: ". $rootHash ."<br/> time: ". $time ."<br/> bits: ". $bits;
	echo "<br/><br/>Target<br/>";
	$target = '000000000000000000275a1f0000000000000000000000000000000000000000';
	echo $target;
	//echo "<br/><br/>Hashes<br/>";
	echo "<br/><br/>Target<br/>";
	$calculatedTarget =  0x5a2717 * 2**(8*(0x1f - 3));
	echo $calculatedTarget;
	//Temp
	/*$version = littleEndian(536870912);
	$rootHash = SwapOrder('871148c57dad60c0cde483233b099daa3e6492a91c13b337a5413a4c4f842978');
	$prevBlockHash = SwapOrder('00000000000000000061abcd4f51d81ddba5498cff67fed44b287de0990b7266');
	$time = littleEndian(1515252561);
	$bits = SwapOrder('180091c1');
	$nonce = 45291998;
	echo "Version: " . $version ."<br/>Prev block hash: ". $prevBlockHash ."<br/> root hash: ". $rootHash ."<br/> time: ". $time ."<br/> bits: ". $bits . "<br/>";
	*/
	$nonce = 2431658524;
	echo "<br/><br/>Hashes<br/>";
	///foreach($nonces as $nonce) {
		$nonce = littleEndian($nonce);
		
		//concat it all
		$header_hex = $version . $prevBlockHash . $rootHash . $time . $bits . $nonce;
		echo $header_hex . "<br/>";	
		
		echo $version . "<br/>" . $prevBlockHash . "<br/>" . $rootHash . "<br/>" . $time . "<br/>" . $bits . "<br/>" . $nonce . "<br/>";
		
		echo mb_strlen($header_hex, '8bit') . "<br/>";
		//convert from hex to binary
		$header_bin = hex2bin($header_hex);
		//hash it then convert from hex to binary
		$pass1 = hex2bin(hash('sha256', $header_bin));
		//Hash it for the seconded time
		$pass2 = hash('sha256', $pass1);
		//fix the order
		$finalHash = SwapOrder($pass2);
		echo $finalHash . "<br/>";	
		exit;
		if($finalHash < $target){
			$bitcoin->submitblock($finalHash);				
			// Create connection
			$conn = new mysqli($servername, $username, $password, $dbname);
			$sql = "INSERT INTO success (nonce, target, finalhash, inserted_date) VALUES ('".$nonce."','".$target."', '".$finalHash."', now())";
			$conn->query($sql);		
			$conn->close();
			break;
		}
	//}
}

/**********************************************************/

//This reverses and then swaps every other char
function SwapOrder($in)
{
    $Split = str_split(strrev($in));
    $x = '';
    for ($i = 0; $i < count($Split); $i += 2) {
        $x .= $Split[$i + 1] . $Split[$i];
    }
    return $x;
}

//makes the littleEndian
function littleEndian($value)
{
    return implode(unpack('H*', pack("V*", $value)));
}

function binFlipByteOrder($string) {
    return implode('', array_reverse(str_split($string, 1)));
}

function merkleroot($txids) {

    // Check for when the result is ready, otherwise recursion
    if (count($txids) === 1) {
        return $txids[0];
    }

    // Calculate the next row of hashes
    $pairhashes = [];
    while (count($txids) > 0) {
        if (count($txids) >= 2) {
            // Get first two
            $pair_first = $txids[0];
            $pair_second = $txids[1];

            // Hash them
            $pair = $pair_first.$pair_second;
            $pairhashes[] = hash('sha256', hash('sha256', $pair, true), true);

            // Remove those two from the array
            unset($txids[0]);
            unset($txids[1]);

            // Re-set the indexes (the above just nullifies the values) and make a new array without the original first two slots.
            $txids = array_values($txids);
        }

        if (count($txids) == 1) {
            // Get the first one twice
            $pair_first = $txids[0];
            $pair_second = $txids[0];

            // Hash it with itself
            $pair = $pair_first.$pair_second;
            $pairhashes[] = hash('sha256', hash('sha256', $pair, true), true);

            // Remove it from the array
            unset($txids[0]);

            // Re-set the indexes (the above just nullifies the values) and make a new array without the original first two slots.
            $txids = array_values($txids);
        }
    }

    return merkleroot($pairhashes);
}


function sampling($chars, $size, $combinations = array()) {

    # if it's the first iteration, the first set 
    # of combinations is the same as the set of characters
    if (empty($combinations)) {
        $combinations = $chars;
    }

    # we're done if we're at size 1
    if ($size == 1) {
        return $combinations;
    }

    # initialise array to put new values in
    $new_combinations = array();

    # loop through existing combinations and character set to create strings
    foreach ($combinations as $combination) {
        foreach ($chars as $char) {
            $new_combinations[] = $combination . $char;
        }
    }

    # call same function again for the next iteration
    return sampling($chars, $size - 1, $new_combinations);

}

function comb($m, $a) {
    if (!$m) {
        yield [];
        return;
    }
    if (!$a) {
        return;
    }
    $h = $a[0];
    $t = array_slice($a, 1);
    foreach(comb($m - 1, $t) as $c)
        yield array_merge([$h], $c);
    foreach(comb($m, $t) as $c)
        yield $c;
}

// example
//$chars = array(0,1,2,6,8,5,9,3,7,4);

// function to generate and print all N! permutations of $str. (N = strlen($str)).
function permute($str,$i,$n) {
   if ($i == $n)
       print "$str, ";
   else {
        for ($j = $i; $j < $n; $j++) {
          swap($str,$i,$j);
          permute($str, $i+1, $n);
          swap($str,$i,$j); // backtrack.
       }
   }
}

// function to swap the char at pos $i and $j of $str.
function swap(&$str,$i,$j) {
    $temp = $str[$i];
    $str[$i] = $str[$j];
    $str[$j] = $temp;
}   
echo '<br/>';
///$str = "0123456789";
//permute($str,0,strlen($str)); // call the function.

//foreach(range(0,9) as $n)
//   	foreach(comb($n, $chars) as $c)
///     	echo join(' ', $c), "<br/>";

//$output = sampling($chars, 6);
//var_dump($output);
?>