<?php
use aic\models\Util;

if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <a href="?to=inst&do=list" class="btn btn-outline-info m-1">機器一覧に戻る</a>
<?php else: ?>

<p><img src="<?= htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') ?>" height="240px" width="320px" class="m-1 rounded"></p>
<h3 class=""><?= htmlspecialchars($instrument['fullname'], ENT_QUOTES, 'UTF-8') ?></h3>
<p><?= htmlspecialchars($instrument['detail'], ENT_QUOTES, 'UTF-8') ?></p>

<?php
$jpdate_start = Util::jpdate($timeline_start);
$jpdate_end = Util::jpdate($timeline_end);
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
      display: none; /* 1行目の非表示 */
    }
    .vis-time-axis .vis-text.vis-minor.vis-foreground {
      display: none; /* 小目盛りラベルの非表示 */
    }
    .vis-time-axis .vis-text.vis-major {
      display: none; /* 大目盛りラベルの非表示 */
    }
</style>

<div id="visualization" class="mt-2"></div>

<div class="pb-2 m-2">
    <a href="?to=inst&do=list" class="btn btn-outline-info m-1">機器設備一覧へ</a>
    <a href="?to=aic&do=list" class="btn btn-outline-info m-1">空き状況一覧へ</a>
</div>

<script type="text/javascript">
  // PHPから渡されたデータを安全にJavaScript変数にエンコードします。
  const items = <?= $items_json ?>;
  const groups = <?= $groups_json ?>;
  const date_start = "<?= htmlspecialchars($timeline_start, ENT_QUOTES, 'UTF-8') ?>";
  const date_end = "<?= htmlspecialchars($timeline_end, ENT_QUOTES, 'UTF-8') ?>";
  const step = 3; // タイムラインの時間軸のステップ（時間単位）

  // タイムラインを描画します。
  make_timeline("visualization", items, groups, date_start, date_end, step);
</script>

<?php endif; ?>