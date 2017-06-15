<?php

require "twitteroauth/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;

define("YH_APPID", "XXXX");

$consumerKey = "XXXX";
$consumerSecret = "XXXX";
$accessToken = "XXXX";
$accessTokenSecret = "XXXX";

$connection = new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);

function func(array $arr)
{
    $max = max($arr);
    $arrFind = array_keys($arr, $max);
    $key = array_rand($arrFind, 1);
    return $arrFind[$key];
}

function callYhKeyPhraseApi($text)
{
    $yh_url = sprintf(
        "http://jlp.yahooapis.jp/KeyphraseService/V1/extract?appid=%s&sentence=%s",
        YH_APPID,
        urlencode($text)
    );
    $response = simplexml_load_string(file_get_contents($yh_url));

    $result = array();
    foreach ($response->Result as $v) {
        $result[(string) $v->Keyphrase] = (string) $v->Score;
    }
    return $result;
}

function there_alnum($text)
{
    if (preg_match("/[a-zA-Z0-9]/",$text)) {
        return TRUE;
    } else {
        return FALSE;
    }
}

$getsampleparams = ['count' => '20', 'exlcude_replies' => 'true'];
$getsample = $connection->get('statuses/home_timeline', $getsampleparams);

$gethashtags = array();
for($i = 0; $i < 20; $i++){
	if(strcmp($getsample[$i]->{'user'}->{'screen_name'}, "botsetsu") != 0){
		if(empty($getsample[$i]->{'entities'}->{'hashtags'}) != true){
			foreach($getsample[$i]->{'entities'}->{'hashtags'} as $value){
				$gethashtags[] = $value->text;
			}
		}
	}
}

$hashtagrank = array_count_values($gethashtags);
if(max($hashtagrank) < 5){
	exit("no hashtag");
}

$hashtag = func($hashtagrank);

$getjikkyoparams = ['q' => '#'.$hashtag, 'count' => '10'];
$getjikkyo = $connection->get('search/tweets', $getjikkyoparams);

$texts = array();
foreach ($getjikkyo->statuses as $value) {
	$texts[] = $value->text;
}

$scoreboard = array();
foreach($texts as $value){
	$output = callYhKeyPhraseApi($value);
	foreach($output as $key => $value){
		if(there_alnum($key) == FALSE){
			$scoreboard[$key] += $value;
		}
	}
}

unset($scoreboard[$hashtag]);
arsort($scoreboard);
$sorted_scoreboard = array_keys($scoreboard);
var_dump($scoreboard);
array_splice($sorted_scoreboard,round(count($sorted_scoreboard)/2));
var_dump($sorted_scoreboard);

shuffle($sorted_scoreboard);
$generate = $sorted_scoreboard[0]." #".$hashtag;
print $generate;

$res = $connection->post("statuses/update", array("status" => $generate));

?>