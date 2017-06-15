<?php

$db = new PDO('mysql:host=XXXX;dbname=XXXX;charset=utf8', 'XXXX', 'XXXX');
$res = $db->query('create table IF NOT EXISTS memory(hash char(32), first char(20), second char(20), third char(20), weight int)');

require "twitteroauth/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;

$consumerKey = "XXXX";
$consumerSecret = "XXXX";
$accessToken = "XXXX";
$accessTokenSecret = "XXXX";
$connection = new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);

$log = "log.txt";

define("YH_APPID", "XXXX");
function callYhApi($text)
{
    $yh_url = sprintf(
        "http://jlp.yahooapis.jp/MAService/V1/parse?appid=%s&sentence=%s&results=ma",
        YH_APPID,
        urlencode($text)
    );
    $response = simplexml_load_string(file_get_contents($yh_url));
                                      
    $result = array();
    foreach ($response->ma_result->word_list->word as $v) {
        $result[] = (string) $v->surface;
    }
    return $result;
}

$lastgettweetid = file_get_contents($log);

$get_params = ['screen_name' => 'XXXX', 'since_id' => $lastgettweetid, 'exclude_replies' => 'true'];
$gettw = $connection->get('statuses/user_timeline', $get_params);

if(count($gettw) != 0){

if(count($gettw) != 0){
	file_put_contents("log.txt", $gettw[0]->id);
}

foreach($gettw as $each){
	$texts[] = preg_replace('/[#][・｡-・ｺ・・・哂-Za-z_\p{Han}0-9・・・兔p{Hiragana}\p{Katakana}]+/u',' ', $each->text);
}
var_dump($texts);
for($i = 0; $i < count($texts); $i++){
	$words[$i][] = 'STARTKEY';
	$tmp = callYhApi($texts[$i]);
	for($j = 0; $j < count($tmp); $j++){
		$words[$i][$j+1] = $tmp[$j];
	}
	$words[$i][] = 'ENDKEY';

for($i = 0; $i < count($words); $i++){
	for($j = 0; $j < count($words[$i])-2; $j++){
		$hash = md5($words[$i][$j].$words[$i][$j+1].$words[$i][$j+2]);
		$st = $db->query("SELECT COUNT(hash) FROM memory WHERE hash='$hash'");
		if ($st->fetchColumn()==FALSE){
			$st = $db->prepare ( "INSERT INTO memory(hash, first, second, third, weight) 
			                                  values(:hash, :first, :second, :third, :weight)" 
			);
				
			$st->bindvalue(':hash', $hash, PDO::PARAM_STR); 
			$st->bindvalue(':first', $words[$i][$j], PDO::PARAM_STR);
			$st->bindvalue(':second', $words[$i][$j+1], PDO::PARAM_STR);
			$st->bindvalue(':third', $words[$i][$j+2], PDO::PARAM_STR);
			$st->bindvalue(':weight', 1, PDO::PARAM_INT);
				
			$st->execute();
		}
		else{
			$db->query("update memory set weight = weight + 1 where hash = '$hash'");
		}
	}
}
}
else{
	echo 'no recent tweets';
}



$db = null;
?>