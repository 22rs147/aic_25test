<div class="text-left">
<?php foreach ($nav_links as $link): ?>
    <a href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary m-1">
        <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
    </a>
<?php endforeach; ?>
</div>

<div id="visualization" class="mt-3"></div>

<script type="text/javascript">
  const items = <?= $items_json ?>;
  const groups = <?= $groups_json ?>;
  const date_start = "<?= htmlspecialchars($timeline_start, ENT_QUOTES, 'UTF-8') ?>";
  const date_end = "<?= htmlspecialchars($timeline_end, ENT_QUOTES, 'UTF-8') ?>";
  const step = 3; // タイムラインの時間軸のステップ（時間単位）

  // タイムラインを描画します。
  make_timeline("visualization", items, groups, date_start, date_end, step);
</script>