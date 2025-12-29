<?php
use aic\models\Util;

// コントローラーから渡される変数: $report_data, $date1, $date2

$total = ['student_n'=>0, 'staff_n'=>0, 'other_n'=>0];

echo '<h3>申請状況集計</h3>' . PHP_EOL;
echo '期　間：'. Util::jpdate($date1,true) . '～' . Util::jpdate($date2,true);
echo '<table class="table table-hover table-bordered">'. PHP_EOL;
echo '<tr><th>日付</th><th>学生利用者数</th><th>教職員利用者数</th><th>その他利用者数</th></tr>'. PHP_EOL;
foreach ($report_data as $date=>$arr){
  echo '<tr>' . PHP_EOL;
  printf('<td>%s</td>', $date);
  foreach ($arr as $key=>$val){
    $total[$key] += $val;
    printf('<td>%d</td>', $val);
  }
  echo '</tr>' . PHP_EOL;
}
vprintf('<tr><th>小計</th><th>%d</th><th>%d</th><th>%d</th></tr>'. PHP_EOL, $total);

echo '</table>'. PHP_EOL;

echo '<h4 class="float-right">合計：', array_sum($total), '</h4>';

echo '<a href="?do=rsv_list" class="btn btn-outline-info m-2" onclick="history.back();">戻る</a> ';
