<?php
namespace aic;

use aic\models\Reserve;
use aic\models\User;
use aic\models\KsuCode;
use aic\models\Util;

use aic\views\Html;

$page = isset($_GET['page']) ? $_GET['page'] : 1; 

// ソート処理
$sort_columns = [
    1 => "code", 
    2 => "room_id",
    3 => "fullname", 
    4 => "reserved",
    5 => "stime",
    6 => "process_status"
];
$sort_by = $_GET['sort_by'] ?? ($_SESSION['sort_by'] ?? 5); // デフォルトは利用予定日順
$sort_order = $_GET['sort_order'] ?? ($_SESSION['sort_order'] ?? 'ASC');

$_SESSION['sort_by'] = $sort_by;
$_SESSION['sort_order'] = $sort_order;

$sort = isset($sort_columns[$sort_by]) ? $sort_columns[$sort_by] . ' ' . $sort_order : '';

echo '<h3>申請状況一覧</h3>' . PHP_EOL;
$inst_id = isset($_GET['inst']) ? $_GET['inst'] : 0;
include 'include/_rsv_search.inc.php';
// pagination on top
$num_rows = (new Reserve)->getNumRows($inst_id, $date1, $date2, $status);
echo Html::pagination($num_rows, KsuCode::PAGE_ROWS, $page);
echo '<table class="table table-hover">'. PHP_EOL;

$header_link = function($col_num, $title) use ($sort_by, $sort_order) {
    $order = ($sort_by == $col_num && $sort_order == 'ASC') ? 'DESC' : 'ASC';
    return sprintf('<a href="?do=rsv_list_copy&sort_by=%d&sort_order=%s">%s</a>', $col_num, $order, $title);
};

echo '<tr><th>' . $header_link(1, '<u>予約番号</u>') . '</th><th>' . $header_link(2, '<u>部屋No.</u>') . '</th>
      <th>' . $header_link(3, '<u>利用機器名</u>') . '</th><th>利用目的</th><th>' . $header_link(4, '<u>申請日</u>') . '</th><th>' . $header_link(5, '<u>利用予定日</u>') . '</th>
      <th>利用時間帯</th><th>利用責任者</th><th>利用代表者</th><th>' . $header_link(6, '<u>承認状態</u>') . '</th><th>操　作</th></tr>'. PHP_EOL;


$rows= (new Reserve)->getListByInst($inst_id, $date1, $date2, $status, $page, $sort);
foreach ($rows as $row){ //予約テーブルにある予約の数だけ繰り返す
  echo '<tr>'. 
  '<td>' . $row['code'] . '</td>' . PHP_EOL . 
  '<td>' . $row['room_no'] . '</td>' . PHP_EOL . 
    
    //'<td>' . $row['apply_name'] . '</td>' . PHP_EOL . //申請者氏名を表示
    //'<td>' . $row['fullname'] . '</td>' . PHP_EOL . //利用機器名を表示
    '<td>' . $row['shortname'] . '</td>' . PHP_EOL . //利用機器名(省略)を表示
    '<td>' . KsuCode::RSV_PURPOSE[$row['purpose_id']] .' ' . $row['purpose'] . '</td>' . PHP_EOL .
    '<td>' . $row['reserved'] . '</td>' . PHP_EOL;//申請日時を表示
  $date1 = Util::jpdate($row['stime']) ;
  $date2 = Util::jpdate($row['etime']) ;
  echo '<td>' . $date1 . '</td>' . PHP_EOL; //利用日を表示
  $time2 = ($date1==$date2) ? substr($row['etime'], 10,6) : '';//日をまかがった予約は終了時刻表示なし
  echo '<td>' . substr($row['stime'], 10,6) . '～' . $time2 . '</td>'; //利用時間帯を表示
  echo '<td>' . $row['master_name'] . '</td>';//利用責任者者氏名を表示
  echo '<td>' . $row['apply_name'] . '</td>';//申請者氏名を表示
  $i = $row['process_status'];
  $status = $rsv_status[$i];
  
  echo '<td>' . $status. '</td>';
  $rsv_id = $row['id'];
  echo '<td>';
  echo '<a class="btn btn-sm btn-outline-success" href="?do=rsv_detail&id='.$row['id'].'&page='.$page.'">詳細</a>' .
    '</td></tr>' . PHP_EOL;
}
echo '</table>';

// pagination at bottom
echo Html::pagination($num_rows, KsuCode::PAGE_ROWS, $page);
