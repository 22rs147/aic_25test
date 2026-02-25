<?php
use aic\views\Html;
use aic\models\Util;
?>

<?php // デバッグ情報の表示 (開発環境のみ) ?>
<!-- <?php if (defined('DEVELOP_ENV') && DEVELOP_ENV && isset($debug_info)): ?>
    <div class="alert alert-warning">
        <strong>Debug Info:</strong>
        <pre class="mb-0"><?= htmlspecialchars(print_r($debug_info, true), ENT_QUOTES, 'UTF-8') ?></pre>
    </div>
<?php endif; ?> -->

<h3>申請状況一覧</h3>

<!-- 検索フォーム -->
<div class="text-left">
    <form method="post" action="?to=rsv&do=list" class="form-inline">
        <div class="form-group mb-2">
            <?php
            // 機器のプルダウンメニュー
            $inst_opts = Html::toOptions($instrument_options, 'id', 'shortname', [0 => '～全ての機器～']);
            echo Html::select($inst_opts, 'id', [$inst_selected]);

            // 年のプルダウンメニュー
            $year_opts = Html::rangeOptions(date('Y') - 1, date('Y') + 1, '年');
            echo Html::select($year_opts, 'y', [$selected_y]);

            // 月のプルダウンメニュー
            $month_opts = Html::rangeOptions(1, 12, '月');
            echo Html::select($month_opts, 'm', [$selected_m]);

            // 日のプルダウンメニュー
            $day_opts = Html::rangeOptions(0, 31, '日', [0 => '指定なし']);
            echo Html::select($day_opts, 'd', [$selected_d]);

            // 期間のプルダウンメニュー
            $period_options = [1 => '１日間', 7 => '１週間', 30 => '１ヶ月'];
            echo Html::select($period_options, 't', [$selected_t]);

            // 承認状態のプルダウンメニュー
            echo Html::select($status_options, 'status', [$status]);

            // ボタン
            echo '<button type="submit" class="btn btn-outline-primary m-1" data-placement="top" data-toggle="tooltip" title="条件で絞り込む">絞込</button>';
            echo '<button type="submit" formaction="?to=rsv&do=report" class="btn btn-outline-success m-1" data-placement="top" data-toggle="tooltip" title="利用者数集計">集計</button>';
            echo '<button type="submit" formaction="?to=rsv&do=export" class="btn btn-outline-success m-1" data-placement="top" data-toggle="tooltip" title="利用状況Excel出力">出力</button>';
            ?>
        </div>
    </form>
</div>

<?php
// ページネーションの表示をします。
echo Html::pagination($num_rows, $page_rows, $page);
?>

<!-- 予約一覧テーブル -->
<?php
// ソート用のリンクを生成するヘルパー関数
function sort_link($label, $column, $current_col, $current_dir) {
    $dir = ($column === $current_col && $current_dir === 'asc') ? 'desc' : 'asc';
    $icon = '';
    // if ($column === $current_col) {
    //     $icon = ($current_dir === 'asc') ? ' ▲' : ' ▼';
    // }
    $url = "?to=rsv&do=list&sort_col={$column}&sort_dir={$dir}";
    return "<a href=\"{$url}\"style=\"color: #6c757d;\">{$label}{$icon}</a>";
}
?>
<table class="table table-hover">
    <tr>
        <th><?= sort_link('予約番号', 'code', $sort_col, $sort_dir) ?></th>
        <th><?= sort_link('部屋No.', 'room_no', $sort_col, $sort_dir) ?></th>
        <th><?= sort_link('利用機器名', 'shortname', $sort_col, $sort_dir) ?></th>
        <th>利用目的</th>
        <th><?= sort_link('申請日', 'reserved', $sort_col, $sort_dir) ?></th>
        <th><?= sort_link('利用予定日', 'stime', $sort_col, $sort_dir) ?></th>
        <th>利用時間帯</th>
        <th>利用責任者</th>
        <th>利用代表者</th>
        <th><?= sort_link('承認状態', 'status', $sort_col, $sort_dir) ?></th>
        <th>操　作</th>
    </tr>
    <?php // 予約データをループしてテーブルの行を生成します。 ?>
    <?php foreach ($rows as $row): ?>
        <tr>
            <?php // 予約番号 ?>
            <td><?= $row['code'] ?></td>
            <?php // 部屋番号 ?>
            <td><?= $row['room_no'] ?></td>
            <?php // 利用機器名（略称） ?>
            <td><?= $row['shortname'] ?></td>
            <?php // 利用目的 ?>
            <td><?= ($rsv_purpose_map[$row['purpose_id'] ?? 0] ?? '') . ' ' . htmlspecialchars($row['purpose'] ?? '') ?></td>
            <?php // 申請日 ?>
            <td><?= $row['reserved'] ?></td>
            <?php // 日本語フォーマットの利用開始日 (Viewでフォーマット) ?>
            <td><?= isset($row['stime']) ? Util::jpdate($row['stime']) : '' ?></td>
            <?php // 利用時間帯（開始時刻〜終了時刻） ?>
            <td><?php
                if (isset($row['stime']) && isset($row['etime'])) {
                    $jp_stime = Util::jpdate($row['stime']);
                    $jp_etime = Util::jpdate($row['etime']);
                    echo substr($row['stime'], 10, 6) . '～' . (($jp_stime == $jp_etime) ? substr($row['etime'], 10, 6) : '');
                }
            ?></td>
            <?php // 利用責任者名 ?>
            <td><?= $row['master_name'] ?? '' ?></td>
            <?php // 利用代表者（申請者）名 ?>
            <td><?= $row['apply_name'] ?? '' ?></td>
            <?php // 承認状態 ?>
            <td><?php if ($row['is_pending']): ?><b>
                        <font color="red"><?= htmlspecialchars($row['status_name']) ?></font>
                    </b><?php else: ?><?= htmlspecialchars($row['status_name']) ?><?php endif; ?></td>
            <?php // 操作ボタン ?>
            <td>
                <?php $rsv_id = $row['id']; ?>
                <?php // 管理者のみ承認/却下ボタンを表示します。 ?>
                <?php if ($is_admin): ?>
                    <a class="btn btn-sm btn-outline-info" href="?to=rsv&do=grant&id=<?= $rsv_id ?>"><?= $row['grant_label'] ?></a>
                <?php endif; ?>
                <?php // 詳細ページへのリンク ?>
                <a class="btn btn-sm btn-outline-success" href="?to=rsv&do=detail&id=<?= $row['id'] ?>&page=<?= $page ?>">詳細</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php // テーブルの下にもページネーションを表示します。 ?>
<?php echo Html::pagination($num_rows, $page_rows, $page); ?>