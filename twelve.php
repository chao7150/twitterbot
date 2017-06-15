<?php

// OAuthライブラリの読み込み
require "twitteroauth/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;

//TwitterOAuth接続
$consumerKey = "XXXX";
$consumerSecret = "XXXX";
$accessToken = "XXXX";
$accessTokenSecret = "XXXX";
$connection = new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);

function sd($arr){
	$ave = array_sum($arr) / count($arr);
	$dev = 0;
	foreach($arr as $a){
		$dev = $dev + ($a - $ave) * ($a - $ave);
	}
	$sd = round(sqrt($dev / (count($arr) - 1)), 2);
	return $sd;
}

class trg{
	public $out;
	public $name;
	public $id;
	public $state = 1;    //0…計測済み。1…未計測。2…5回分のデータ取得済み。3…メッセージ作成済み
	public $records = array();
	public $message;
	public $addmessage;
	public $ave;
	public function __construct($n, $i, $o){
		$this->out = $o;
		$this->name = $n;
		$this->id = $i;
		$filename = $this->name.'-log.txt';
		$log = file_get_contents($filename);
		if($log !== false){
			if($i <= $log){		//古い
				$this->state = 0;
			}
		}
	}		
	public function getrec(){
		global $connection;
		$records = $connection->get('statuses/user_timeline', array('screen_name' => $this->name, 'since_id' => $this->id));
		if(count($records) >= 5){	//5件に到達していなければ間違いなく記録はそろってないから通さない
			for($i = count($records) - 1; $i >= 0; $i--){		//$tのrecords配列に記録が5個入るか後ろから見ていく
				if(mb_strpos($records[$i]->text, 'Twelve') !== false){	//'Twelve'を含むもののみを通す
					$tmp = mb_substr($records[$i]->text, 11, 4);	//本文からタイムのみを抜き出す
					$this->putrec($tmp);		//含んでいたらそれをrecordsに加える
					if(count($this->records) == 5){
						$this->makemessage();
					}
				}
			}
		}
	}
	public function putrec($r){
		$this->records[] = $r;
	}
	public function makemessage(){
		$ave = round(array_sum($this->records) / 5, 2);		//平均タイムを算出
		$this->ave = $ave;
		$sd = sd($this->records);	//標準偏差を出してもらう
		$this->addmessage = $this->makeaddmessage($ave);
		if($this->out != 1){
			$this->write();
		}
		$url = $this->makepage();
		$this->message = '@'.$this->name.' '.sprintf("%.2f", $ave).'('.sprintf("%.2f", $sd).')'.PHP_EOL.PHP_EOL.$this->addmessage.PHP_EOL.$url;		//一つのツイート文に合成
		$this->tweet();
	}
	public function makeaddmessage($ave){
		$filename = $this->name.'.txt';
		$allrecs = file($filename, FILE_IGNORE_NEW_LINES);
		if($allrecs === false){
			return '過去の記録なし';
		}
		var_dump($allrecs);
		$best = min($allrecs);
		$n = count($allrecs);
		if($n > 10){
			for($i = 0; $i < 10; $i++){
		 		$latest10recs[] = $allrecs[$n - 1 - $i];
			}
		}
		else{
			for($i = 0; $i < $n; $i++){
		 		$latest10recs[] = $allrecs[$n - 1 - $i];
			}
		}
		$ave10 = round(array_sum($latest10recs) / count($latest10recs), 2);
		if($ave < $best){
			$bestmessage = PHP_EOL.'記録更新です！';
		}
		else{
			$bestmessage = '';
		}
		$addmessage = '最近10回の平均:'.$ave10.PHP_EOL.'最高記録:'.$best.$bestmessage;
		return $addmessage;
	}
	public function makepage(){
		$url = 'XXXX'.$this->name.'XXXX.php';
		$html = str_replace('searchtag', $this->name, file_get_contents('template.txt'));
		file_put_contents($this->name.'-chart.php', $html);
		return $url;
	}
	public function tweet(){
		global $connection;
		$post_params = array('status' => $this->message, 'in_reply_to_status_id' => $this->id);
		$rep = $connection->post('statuses/update', $post_params);
	}
	public function write(){
		$filenamel = $this->name.'-log.txt';
		$log = file_put_contents($filenamel, $this->id);
		$filenamer = $this->name.'.txt';
		file_put_contents($filenamer, $this->ave.PHP_EOL, FILE_APPEND);
	}
	
}

//ツイートの取得
$gettw = $connection->get('statuses/home_timeline', array('count' => '30'));

//「5連」を含むツイートを抽出しスクリーンネームとツイートIDをもとに$trgをつくる
foreach($gettw as $t){
	if(mb_strpos($t->text, "5連", 0, "UTF-8") !== false){
		if(mb_strpos($t->text, "なし", 0, "UTF-8") !== false){
			$trg[] = new trg($t->user->screen_name, $t->id, 1); //trg配列にキーのスクリーンネームとidを入れていく
		}
		else{
			$trg[] = new trg($t->user->screen_name, $t->id, 0);
		}
	}
}

//状態1の全てのトリガーについて、記録取得を行う
foreach($trg as $t){
	if($t->state){
		$t->getrec();
	}
}

//自動フォロー返し
$followers = $connection->get('followers/ids', array('cursor' => -1));
$friends = $connection->get('friends/ids', array('cursor' => -1));
    if ($followers && $friends && !empty($friends->ids)) {
        foreach ($followers->ids as $i => $id) {
            if (!in_array($id, $friends->ids)) {
                $connection->post('friendships/create', array('user_id' => $id));
            }
        }
    }


var_dump($trg);
		

?>