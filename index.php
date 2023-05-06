<?php

require_once ('easybitcoin.php');
require_once ('nonces0.php');

sort($nonces, SORT_NUMERIC);

$servername = "localhost";
$username = "root";
$password = "DominO@786#";
$dbname = "bitcoin";
$start_time = "";
$end_time = "";
$finalHashes = array();
$filename = addslashes($_SERVER['PHP_SELF']);

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $bitcoin = new Bitcoin('mbabbas', 'Virtual786', 'localhost', '8332');
    //while(true){
    $blockTemplate = $bitcoin->getblocktemplate();
    if (isset($blockTemplate['version'])) {

        $start_time = date("Y-m-d H:i:s");
        $version = $blockTemplate['version'];
        $previousblockhash = $blockTemplate['previousblockhash'];
        $target = $blockTemplate['target']; //adjustment with difficult
        $curtime = $blockTemplate['curtime']; //this or current time
        $bits = $blockTemplate['bits'];
        $height = $blockTemplate['height'];
        $transactions = $blockTemplate['transactions'];
        $txids = array();
        $data = array();

        $height_hex = littleEndian($height);
        $heightLength = strlen($height_hex) / 2;

        $coinbaseTransaction = "01000000010000000000000000000000000000000000000000000000000000000000000000ffffffff";

        $signature = "3353ZKhVm4xiBdsXKx3MR1A1Ja1GJ7zToM";
        $extraNonce = "26859374";
        $extraNonce = littleEndian($extraNonce);
        $publicKey = "PKMiner";
        $publicKeyHash = hash('sha256', $publicKey);
        //$scriptSig = "PKMiner 3353ZKhVm4xiBdsXKx3MR1A1Ja1GJ7zToM 26859374 " . date('Y-m-d H:i:s', strtotime('now'));
        $scriptSig = $signature . $extraNonce . $publicKey;
        
        $scriptSigHex = implode(unpack("H*", $scriptSig));
        $scriptSigLength = dechex((int)strlen($scriptSigHex) / 2);
        $scriptSigLength = strlen($scriptSigLength) < 10 ? "0" . $scriptSigLength : $scriptSigLength;
        $sequence = '00000000';
        $output = '01';
        $coinbaseTransaction .= $scriptSigHex;
        $transactionFeeSum = 0;
        foreach ($transactions as $transaction) {
            if ($transaction != null && isset($transaction['txid'])) {
                array_push($txids, $transaction['txid']);
                array_push($data, $transaction['data']);
                $transactionFeeSum += $transaction['fee'];
            }
        }
        $value = (($transactionFeeSum / 100000000) + 25);
        $value = $value * 100000000;
        $value = littleEndian($value);
        $value .= "00000000";
        $subScriptSig = "OP_DUP OP_HASH160 " . $publicKeyHash . " OP_EQUALVERIFY OP_CHECKSIG";
        $subScriptSigHex = implode(unpack("H*", $subScriptSig));
        $subScriptSigLength = dechex((int)strlen($subScriptSigHex) / 2);
        //echo strlen($subScriptSigLength) . "<br/>";
        //$subScriptSigLength = strlen($subScriptSigLength) < 10 ? "0" . $subScriptSigLength : $subScriptSigLength;
        $locktime = '00000000';
        $coinbaseTransaction .= $scriptSigLength . $heightLength . $height_hex . $scriptSigHex .
            $sequence . $output . $value . $subScriptSigLength . $subScriptSigHex . $locktime;
        //echo strlen($subScriptSigLength) . "<br/>";
        //$coinbaseTransaction = implode(unpack("H*", $coinbaseTransaction));
        //convert from hex to binary
        //        $coinbaseTransaction = "0100000001186f9f998a5aa6f048e51dd8419a14d8a0f1a8a2836dd734d2804fe65fa35779000000008b483045022100884d142d86652a3f47ba4746ec719bbfbd040a570b1deccbb6498c75c4ae24cb02204b9f039ff08df09cbe9f6addac960298cad530a863ea8f53982c09db8f6e381301410484ecc0d46f1918b30928fa0e4ed99f16a0fb4fde0735e7ade8416ab9fe423cc5412336376789d172787ec3457eee41c04f4938de5cc17b4a10fa336a8d752adfffffffff0260e31600000000001976a914ab68025513c3dbd2f7b92a94e0581f5d50f654e788acd0ef8000000000001976a9147f9b1a7fb68d60c536c2fd8aeaa53a8f3cc025a888ac00000000";
        //        echo $coinbaseTransaction . "<br/>";
        $coinbaseTransactionBin = hex2bin($coinbaseTransaction);
        //hash it then convert from hex to binary
        $firstHash = hex2bin(hash('sha256', $coinbaseTransactionBin));
        //Hash it for the seconded time
        $coinbaseTransactionId = hash('sha256', $firstHash);

        $coinbaseTransactionId = SwapOrder($coinbaseTransactionId);
        array_unshift($txids, $coinbaseTransactionId);
        array_unshift($data, $coinbaseTransaction);
        if (sizeof($txids) > 0) {
            $txidsBEbinary = [];
            foreach ($txids as $txidBE) {
                // covert to binary, then flip
                $txidsBEbinary[] = binFlipByteOrder(hex2bin($txidBE));
            }
            $root = merkleroot($txidsBEbinary);
            $calulatedHash = bin2hex(binFlipByteOrder($root));
            $version = littleEndian($version);
            $prevBlockHash = SwapOrder($previousblockhash);
            $rootHash = SwapOrder($calulatedHash);
            $time = littleEndian($curtime);
            $bits = SwapOrder($bits);

            foreach ($nonces as $nonce) {
                $nonce = littleEndian($nonce);

                //concat it all
                $header_hex = $version . $prevBlockHash . $rootHash . $time . $bits . $nonce;

                //convert from hex to binary
                $header_bin = hex2bin($header_hex);
                //hash it then convert from hex to binary
                $pass1 = hex2bin(hash('sha256', $header_bin));
                //Hash it for the seconded time
                $pass2 = hash('sha256', $pass1);
                //fix the order
                $finalHash = SwapOrder($pass2);
                if ($finalHash < $target) {
                    //$conn = new mysqli($servername, $username, $password, $dbname);
                    $sql = "INSERT INTO success (blockheader, nonce, target, finalhash, inserted_date) VALUES ('" .
                        $header_hex . "', '" . $nonce . "','" . $target . "', '" . $finalHash .
                        "', now())";
                    $conn->query($sql) or throw_ex($conn->error);
                    //$conn->close();
                    $block = $header_hex . sizeof($data) . implode("", $data);
                    $bitcoin->submitblock($block);
                    break;
                }
                (!empty(trim($finalHash)) && trim($finalHash) != "") ? array_push($finalHashes, trim($finalHash)) : "";
                $currentTime = strtotime('now');
                $currentTime = littleEndian($currentTime);

                //concat it all
                $header_hex = $version . $prevBlockHash . $rootHash . $currentTime . $bits . $nonce;

                //convert from hex to binary
                $header_bin = hex2bin($header_hex);
                //hash it then convert from hex to binary
                $pass1 = hex2bin(hash('sha256', $header_bin));
                //Hash it for the seconded time
                $pass2 = hash('sha256', $pass1);
                //fix the order
                $finalHash = SwapOrder($pass2);
                if ($finalHash < $target) {
                    //$conn = new mysqli($servername, $username, $password, $dbname);
                    $sql = "INSERT INTO success (blockheader, nonce, target, finalhash, inserted_date) VALUES ('" .
                        $header_hex . "', '" . $nonce . "','" . $target . "', '" . $finalHash .
                        "', now())";
                    $conn->query($sql) or throw_ex($conn->error);
                    //$conn->close();
                    $block = $header_hex . sizeof($data) . implode("", $data);
                    $bitcoin->submitblock($block);
                    break;
                }
                (!empty(trim($finalHash)) && trim($finalHash) != "") ? array_push($finalHashes, trim($finalHash)) : "";
            }
        }
        $finalHashes = array_filter($finalHashes);
        sort($finalHashes, SORT_ASC);
        $end_time = date("Y-m-d H:i:s");
        $sql = "INSERT INTO logs (start_time, end_time, file_name, no_of_transactions, prevblockhash, smallestHash, inserted_date) VALUES ('" .
            $start_time . "', '" . $end_time . "', '" . $filename . "', " . sizeof($txids) . ", '" . $previousblockhash . "', '" . $finalHashes[0] . "', now())";
        $conn->query($sql) or throw_ex($conn->error);
        
    }
    //}
}
catch (exception $e) {
    //$conn = new mysqli($servername, $username, $password, $dbname);
    $sql = "INSERT INTO exceptions (file_name, message, inserted_date) VALUES ('" .
        $filename . "', '" . addslashes($e->getMessage()) . "', now())";
    $conn->query($sql);
    //$conn->close();
}
finally {
    $conn->close();
}
/************************* FUNCTIONS *********************************/


function throw_ex($er)
{
    throw new Exception($er);
}

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

function binFlipByteOrder($string)
{
    return implode('', array_reverse(str_split($string, 1)));
}

function merkleroot($txids)
{

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
            $pair = $pair_first . $pair_second;
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
            $pair = $pair_first . $pair_second;
            $pairhashes[] = hash('sha256', hash('sha256', $pair, true), true);

            // Remove it from the array
            unset($txids[0]);

            // Re-set the indexes (the above just nullifies the values) and make a new array without the original first two slots.
            $txids = array_values($txids);
        }
    }

    return merkleroot($pairhashes);
}
?>