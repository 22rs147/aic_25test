<?php
use aic\models\KsuCode;
use aic\views\Html;
// htmlspecialcharsのショートカット
$h = 'htmlspecialchars';
?>

<?php if ($row): ?>
    <form method="post" action="?to=mbr&do=save">
        <?= Html::input('hidden', 'id', $mbr_id) ?>
        <h3 class="text-primary">会員情報編集</h3>
        <table class="table table-hover">
            <tbody>
                <tr><th width="20%">会員ID</th><td><?= $h($row['sid'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>ログインID</th><td><?= $h($row['uid'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>日本語名</th><td><?= Html::input('text','ja_name', $h($row['ja_name'], ENT_QUOTES, 'UTF-8')) ?></td></tr>
                <tr><th>日本語読み</th><td><?= Html::input('text','ja_yomi', $h($row['ja_yomi'], ENT_QUOTES, 'UTF-8')) ?></td></tr>
                <tr><th>英語名</th><td><?= Html::input('text','en_name', $h($row['en_name'], ENT_QUOTES, 'UTF-8')) ?></td></tr>
                <tr><th>英語読み</th><td><?= Html::input('text','en_yomi', $h($row['en_yomi'], ENT_QUOTES, 'UTF-8')) ?></td></tr>
                <tr><th>会員種別</th><td><?= $h(KsuCode::MBR_CATEGORY[$row['category']], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>メールアドレス</th><td><?= Html::input('text','email', $h($row['email'], ENT_QUOTES, 'UTF-8')) ?></td></tr>
                <tr><th>電話番号</th><td><?= Html::input('text','tel_no', $h($row['tel_no'], ENT_QUOTES, 'UTF-8')) ?></td></tr>
                <tr><th>性別</th><td><?= Html::select(KsuCode::MBR_SEX, 'sex', [$row['sex']], 'radio') ?></td></tr>
                <tr><th>所属</th><td><?= Html::input('text','dept_name', $h($row['dept_name'], ENT_QUOTES, 'UTF-8')) ?></td></tr>
                <tr><th>所属番号</th><td><?= Html::input('text','dept_code', $h($row['dept_code'], ENT_QUOTES, 'UTF-8')) ?></td></tr>
                <tr><th>予約権有無</th><td><?= $h(KsuCode::MBR_AUTHORITY[$row['authority']], ENT_QUOTES, 'UTF-8') ?></td></tr>
            </tbody>
        </table>
        <div class="pb-5 mb-5">
            <button type="submit" class="btn btn-outline-primary m-1">保存</button>
            <a href="?to=mbr&do=detail&id=<?= $mbr_id ?>" class="btn btn-outline-info m-1">戻る</a>
        </div>
    </form>
<?php else: ?>
    <p class="alert alert-warning">会員情報は存在しません！</p>
<?php endif; ?>

<!-- Modal HTML -->
<div id="myModal" class="modal fade">
  <div class="modal-dialog modal-confirm">
    <div class="modal-content">
      <div class="modal-header">
        <div class="icon-box">
          <i class="material-icons">&#xE5CD;</i>
        </div>
        <h4 class="text-info">この会員を削除しますか？</h4>
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      </div>
      <div class="modal-body">
        <p>「はい」を押したら、この会員を削除します。</p>
      </div>
      <div class="modal-footer">
        <a href="" data-url="?to=mbr&do=delete" class="btn btn-danger" id="deleteBtn">はい</a>
        <button type="button" class="btn btn-info" data-dismiss="modal">いいえ</button>
      </div>
    </div>
  </div>
</div>
<script>
  $('#myModal').on('shown.bs.modal', function(event) {
    var id = $(event.relatedTarget).data('id');
    var href = $(this).find('#deleteBtn').data('url') +'&id=' + id;
    $(this).find('#deleteBtn').attr('href', href);
  });
</script>

