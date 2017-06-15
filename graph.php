<?php

$filename = 'PainEchos.txt';
$raw = file($filename);
$records[0] = array('回数', 'タイム');
for($i = 0; $i < count($raw); $i++){
	$records[] = array((int)$i, (float)$raw[$i]);
}
$json = json_encode($records);
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
 
  // ライブラリのロード
  // name:visualization(可視化),version:バージョン(1),packages:パッケージ(corechart)
  google.load('visualization', '1', {'packages':['corechart']});     
         
  // グラフを描画する為のコールバック関数を指定
  google.setOnLoadCallback(drawChart);
 
  // グラフの描画   
  function drawChart() {      
    
    // 配列からデータの生成
    var data = google.visualization.arrayToDataTable(<?php echo $json; ?>);
 
    // オプションの設定
    var options = {
      title: '記録の推移'
     };     
             
    // 指定されたIDの要素に折れ線グラフを作成
    var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
      
    // グラフの描画
    chart.draw(data, options);
  }
  
</script>
</head>
<body>
  
  <!--  グラフの描画エリア -->
  <div id="chart_div" style="width: 100%; height: 350px"></div>
  
</body>
</html>