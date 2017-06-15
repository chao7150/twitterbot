<?php

//夜間停止
$t = date("G");
if(1<$t and $t<10){
	exit('夜間停止');
}
$db = new PDO('mysql:host=XXXX;dbname=XXXX;charset=utf8', 'XXXX', 'XXXX');

require "twitteroauth/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;

$consumerKey = "XXXX";
$consumerSecret = "XXXX";
$accessToken = "XXXX";
$accessTokenSecret = "XXXX";
$connection = new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);

//重みつき抽選
function array_rand_weighted($entries){
    $sum  = 0;
	for($i = 0; $i < count($entries); $i++){
		$sum += $entries[$i][3];
	}
	$rand = rand(1, $sum);
 
    for($i = 0; $i < count($entries); $i++){
        if (($sum -= $entries[$i][3]) < $rand) return $entries[$i];
    }
}

//引数（文字列）をfirstにもつデータを検索し、その中から一つ選んで返す
function searchdb($w)
{
	global $db;
	$sta = $db->query("SELECT * FROM memory WHERE first = '$w'");   //3つめの単語が最初に来るデータを探す
	while($row = $sta->fetch(PDO::FETCH_ASSOC)) {     //読みだしてきたデータが終わるまで繰り返す
    	$choice_table[] = array($row['first'], $row['second'], $row['third'], $row['weight'], $row['hash']);    //$key_table配列に入れていく
    }
    $result = array_rand_weighted($choice_table);       //新しい単語列を選ぶ
	$sta->closeCursor();
	return $result;
}

//ツイート文を作る。まずfirstが'STARTKEY'であるデータを読み出す
$searchkey = "STARTKEY";
$key = searchdb($searchkey);
$sentence = $key[1];								  //$sentenceに最初の単語を入れる
echo $key[0].' '.$key[1].' '.$key[2].' '.$key[3].' '.$key[4]."<br><br>";
if(strcmp($key[2], 'ENDKEY') != 0){						
	$sentence = $sentence.$key[2];
}
$searchkey = $key[2];

for($i = 0; $i < 12; $i++){				  //$keyの3つ目がENDKEYでなければ続く	
	$selected = searchdb($searchkey);                //$searchkeyで始まる3単語の列を取得
	echo $selected[0].' '.$selected[1].' '.$selected[2].' '.$selected[3].' '.$selected[4]."<br>";
	$sentence = $sentence . $selected[1];			//2単語目はとりあえず出力文に入れる
	if(strcmp($selected[2],'ENDKEY')==0){           //3単語目はENDKEYかどうかチェック
		break;   									//ENDKEYならループ終了
	}
	$sentence = $sentence.$selected[2];				//そうでなければ3単語目も出力文に入れる
	$searchkey = $selected[2];						//$searchkeyを再設定
}
var_dump($sentence);
$sentence = $sentence.'.';
$res = $connection->post("statuses/update", array("status" => $sentence));

$db = null;

?>