<?php

//夜間停止
$t = date("G");
if(1<$t and $t<10){
	exit('夜間停止');
}
$db = new PDO('mysql:host=XXXX;dbname=XXXX;charset=utf8', 'XXXX', 'XXXX');

require "twitteroauth/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;

define("YH_APPID", "XXXX");

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

//引数（文字列）を$placeにもつデータを検索し、その中から一つ選んで返す
function searchdb($w, $place)
{
	global $db;
	$sta = $db->query("SELECT * FROM memory WHERE $place = '$w'");   //3つめの単語が最初に来るデータを探す
	while($row = $sta->fetch(PDO::FETCH_ASSOC)) {     //読みだしてきたデータが終わるまで繰り返す
    	$choice_table[] = array($row['first'], $row['second'], $row['third'], $row['weight'], $row['hash']);    //$key_table配列に入れていく
    }
    $result = array_rand_weighted($choice_table);       //新しい単語列を選ぶ
	$sta->closeCursor();
	return $result;
}

//特徴語抽出
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

//形態素解析
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

//最近のツイートの収集
$home_params = ['count' => '1'];
$gettw = $connection->get('statuses/home_timeline', $home_params);
$topicphrase = callYhKeyPhraseApi($gettw[0]->text);
$toptopic = callYhApi(array_search('100', $topicphrase));
$topic = $toptopic[rand(0, count($toptopic)-1)];
echo "抽出されたキーワードは「".$topic."」です。<br><br>";

//ツイート文を作る。まずsecondがtopicであるデータを読み出す
$searchkey = $topic;
$key = searchdb($searchkey, 'second');
$sentence = $key[1];								  //$sentenceに最初の単語を入れる
echo "最初にデータベースから選ばれた単語列<br>".$key[0].' '.$key[1].' '.$key[2].' '.$key[3].' '.$key[4]."<br><br>";
if(strcmp($key[2], 'ENDKEY') != 0){						
	$sentence = $sentence.$key[2];
}
$forwardsearchkey = $key[2];
if(strcmp($key[0], 'STARTKEY') != 0){						
	$sentence = $key[0].$sentence;
}
$backsearchkey = $key[0];
if(strcmp($forwardsearchkey, 'ENDKEY') != 0){   //最初に引いてきたkeyの3つ目がENDKEYでなければ
echo "順方向にマルコフ連鎖で選ばれた単語列<br>";
for($i = 0; $i < 6; $i++){				  //$keyの3つ目がENDKEYでなければ続く	
	$selected = searchdb($forwardsearchkey, 'first');                //$searchkeyで始まる3単語の列を取得
	echo $selected[0].' '.$selected[1].' '.$selected[2].' '.$selected[3].' '.$selected[4]."<br>";
	$sentence = $sentence . $selected[1];			//2単語目はとりあえず出力文に入れる
	if(strcmp($selected[2],'ENDKEY')==0){           //3単語目はENDKEYかどうかチェック
		break;   									//ENDKEYならループ終了
	}
	$sentence = $sentence.$selected[2];				//そうでなければ3単語目も出力文に入れる
	$forwardsearchkey = $selected[2];						//$searchkeyを再設定
}
}

echo "<br>";

if(strcmp($backsearchkey, 'STARTKEY') != 0){
echo "逆方向にマルコフ連鎖で選ばれた単語列<br>";
for($i = 0; $i < 6; $i++){				  //$keyの3つ目がENDKEYでなければ続く	
	$selected = searchdb($backsearchkey, 'third');                //$searchkeyで始まる3単語の列を取得
	echo $selected[0].' '.$selected[1].' '.$selected[2].' '.$selected[3].' '.$selected[4]."<br>";
	$sentence = $selected[1].$sentence;			//2単語目はとりあえず出力文に入れる
	if(strcmp($selected[0],'STARTKEY')==0){           //3単語目はENDKEYかどうかチェック
		break;   									//ENDKEYならループ終了
	}
	$sentence = $selected[0].$sentence;				//そうでなければ3単語目も出力文に入れる
	$backsearchkey = $selected[0];						//$searchkeyを再設定
}
}
echo "<br>完成した文字列<br>".$sentence;
$sentence = $sentence.'.';
$res = $connection->post("statuses/update", array("status" => $sentence));

$db = null;

?>