<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bitcoin";
$filename = addslashes($_SERVER['PHP_SELF']);
//8333334
for ($j = 0; $j <= 10; $j++) {
    $nonces = "";
    for ($i = 0; $i <= 50000; $i++) {
        $nonces .= big_rand(10) . ", ";
    }
    $nonces = substr($nonces, 0, strlen($nonces) - 2);
    $code = '<?php $nonces = array(' . $nonces . ');?>';
    file_put_contents(__dir__ . "\\nonces" . $j . ".php", $code);
}
function big_rand($len = 9)
{
    $rand = '';
    while (!(isset($rand[$len - 1]))) {
        $rand .= mt_rand();
    }
    return substr($rand, 0, $len);
}
?>