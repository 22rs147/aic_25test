<?php
use aic\models\Util;

if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <a href="?to=inst&do=list" class="btn btn-outline-info m-1">機器一覧に戻る</a>
<?php else: ?>

<p>
    <img src="<?= htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') ?>" height="240px" width="320px" class="m-1 rounded">
</p>
<h3 class=""><?= htmlspecialchars($instrument['fullname'], ENT_QUOTES, 'UTF-8') ?></h3>
<p><?= htmlspecialchars($instrument['detail'], ENT_QUOTES, 'UTF-8') ?></p>

<?php
// コントローラーから渡された本来の日付範囲を表示に使用
$jpdate_start = Util::jpdate($display_start);
$jpdate_end   = Util::jpdate($display_end);
?>
<h4><?= $jpdate_start ?> から <?= $jpdate_end ?> までの予約一覧</h4>

<div class="text-left">
    <?php foreach ($nav_links as $link): ?>
        <a href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary m-1">
            <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php endforeach; ?>
</div>

<style>
    .vis-time-axis .vis-grid.vis-horizontal.vis-first {
      display: none; /* 1行目のグリッド非表示 */
    }
    .vis-time-axis .vis-text.vis-minor.vis-foreground {
      display: none; /* 小目盛りラベルの非表示 */
    }
    .vis-time-axis .vis-text.vis-major {
      display: none; /* 大目盛りラベルの非表示 */
    }
</style>

<?php foreach ($days_data as $index => $day): ?>
    <div id="visualization_<?= $index ?>" class="mt-2 border-bottom pb-2"></div>
    <script type="text/javascript">
        (function() {
            const items = <?= $day['items_json'] ?>;
            const groups = <?= $day['groups_json'] ?>;
            const date_start = "<?= $day['start'] ?>";
            const date_end = "<?= $day['end'] ?>";
            const step = 3;
            make_timeline("visualization_<?= $index ?>", items, groups, date_start, date_end, step);
        })();
    </script>
<?php endforeach; ?>

<div class="pb-2 m-2">
    <a href="?to=inst&do=list" class="btn btn-outline-info m-1">機器設備一覧へ</a>
    <a href="?to=aic&do=list" class="btn btn-outline-info m-1">空き状況一覧へ</a>
</div>

<script type="text/javascript">
  // PHPデータをJS変数へ
  const items = <?= $items_json ?>;
  const groups = <?= $groups_json ?>;
  
  // タイムラインの表示範囲（ここではダミー日付が入るため、画面上の軸が揃う）
  const date_start = "<?= htmlspecialchars($timeline_start, ENT_QUOTES, 'UTF-8') ?>";
  const date_end = "<?= htmlspecialchars($timeline_end, ENT_QUOTES, 'UTF-8') ?>";
  
  const step = 2; // 目盛りの間隔（時間単位）

  // タイムライン描画関数の呼び出し
  // make_timeline関数は共通JS側で定義されていると想定
  make_timeline("visualization", items, groups, date_start, date_end, step);
</script>

<?php endif; ?>