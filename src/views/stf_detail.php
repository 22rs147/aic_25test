<?php
// エラーメッセージがある場合は表示
if (isset($error_message)) : ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
    <a href="?to=mbr&do=list" class="btn btn-outline-info m-1">会員一覧に戻る</a>
<?php return;
endif;

// データがなければ処理を中断
if (!isset($member) || !isset($staff)) {
    return;
}
?>

<h3 class="text-primary">「<?= htmlspecialchars($member['ja_name'], ENT_QUOTES, 'UTF-8') ?>」教職員情報</h3>
<table class="table table-hover">
    <tr><th width="20%">会員ID</th><td><?= htmlspecialchars($member['sid'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>ログインID</th><td><?= htmlspecialchars($member['uid'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>日本語名</th><td><?= htmlspecialchars($member['ja_name'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>所属</th><td><?= htmlspecialchars($member['dept_name'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>職員種別</th><td><?= htmlspecialchars($staff['role_title'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>役職</th><td><?= htmlspecialchars($staff['role_rank'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>メールアドレス</th><td><?= htmlspecialchars($member['email'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>電話番号</th><td><?= htmlspecialchars($member['tel_no'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>内線番号</th><td><?= htmlspecialchars($staff['tel_ext'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>部屋番号</th><td><?= htmlspecialchars($staff['room_no'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>予約権有無</th><td><?= htmlspecialchars($mbr_authority, ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>責任者可否</th><td class="<?= $responsible_class ?>"><?= htmlspecialchars($staff_responsible, ENT_QUOTES, 'UTF-8') ?></td></tr>
</table>

<div class="pb-5 mb-5">
    <?php if ($is_admin) : ?>
        <a class="btn btn-outline-primary m-1" href="?to=stf&do=grant&id=<?= htmlspecialchars($staff['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($grant_label, ENT_QUOTES, 'UTF-8') ?></a>
    <?php endif; ?>
    <a href="?to=mbr&do=detail&id=<?= htmlspecialchars($member['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-info m-1">戻る</a>
</div>

<!-- Modal HTML (元のファイルから流用) -->
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
