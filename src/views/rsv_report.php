<?php
use aic\models\Util;

if (defined('DEVELOP_ENV') && DEVELOP_ENV && isset($debug_info)): ?>
    <div class="alert alert-warning">
        <strong>Debug Info:</strong>
        <pre class="mb-0"><?= htmlspecialchars(print_r($debug_info, true), ENT_QUOTES, 'UTF-8') ?></pre>
    </div>
<?php endif;

$total = ['student_n' => 0, 'staff_n' => 0, 'other_n' => 0];
$grand_total = 0;
?>

<h3>申請状況集計</h3>
<p>
    <strong>期間：</strong>
    <?= Util::jpdate($date1, true) ?> ～ <?= Util::jpdate($date2, true) ?>
</p>

<table class="table table-hover table-bordered">
    <thead class="thead-light">
        <tr>
            <th>日付</th>
            <th>学生利用者数</th>
            <th>教職員利用者数</th>
            <th>その他利用者数</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($report_data as $date => $arr): ?>
            <tr>
                <td><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></td>
                <?php foreach ($arr as $key => $val): ?>
                    <?php $total[$key] += $val; ?>
                    <td><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="table-info">
            <th>小計</th>
            <th><?= htmlspecialchars($total['student_n'], ENT_QUOTES, 'UTF-8') ?></th>
            <th><?= htmlspecialchars($total['staff_n'], ENT_QUOTES, 'UTF-8') ?></th>
            <th><?= htmlspecialchars($total['other_n'], ENT_QUOTES, 'UTF-8') ?></th>
        </tr>
    </tfoot>
</table>

<h4 class="float-right">合計利用者数：<?= htmlspecialchars(array_sum($total), ENT_QUOTES, 'UTF-8') ?></h4>

<a href="?to=rsv&do=list" class="btn btn-outline-info m-2">戻る</a>