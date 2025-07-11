<?php
use aic\models\KsuCode;
?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <a href="index.php?to=mbr&do=list" class="btn btn-outline-info m-1">会員一覧に戻る</a>

<?php elseif (isset($row)): ?>
    <h3 class="text-primary">「<?= htmlspecialchars($row['ja_name']) ?>」会員情報</h3>
    <table class="table table-hover">
        <tr><th width="20%">会員ID</th><td><?= htmlspecialchars($row['sid']) ?></td></tr>
        <tr><th>ログインID</th><td><?= htmlspecialchars($row['uid']) ?></td></tr>
        <tr><th>日本語名</th><td><?= htmlspecialchars($row['ja_name']) ?></td></tr>
        <tr><th>日本語読み</th><td><?= htmlspecialchars($row['ja_yomi']) ?></td></tr>
        <tr><th>英語名</th><td><?= htmlspecialchars($row['en_name']) ?></td></tr>
        <tr><th>英語読み</th><td><?= htmlspecialchars($row['en_yomi']) ?></td></tr>
        <tr>
            <th>会員種別</th>
            <td>
                <?= htmlspecialchars(KsuCode::MBR_CATEGORY[$row['category']] ?? '不明') ?>
                <?php if ($row['category'] > 1): //教育職員 ?>
                    <span class="float-right">
                        <a class="btn btn-outline-primary ml-1" href="index.php?to=mbr&do=stf_detail&id=<?= htmlspecialchars($row['id']) ?>">教職員詳細</a>
                    </span>
                <?php endif; ?>
            </td>
        </tr>
        <tr><th>メールアドレス</th><td><?= htmlspecialchars($row['email']) ?></td></tr>
        <tr><th>電話番号</th><td><?= htmlspecialchars($row['tel_no']) ?></td></tr>
        <tr><th>性別</th><td><?= htmlspecialchars(KsuCode::MBR_SEX[$row['sex']] ?? '未登録') ?></td></tr>
        <tr><th>所属</th><td><?= htmlspecialchars($row['dept_name']) ?></td></tr>
        <tr><th>所属番号</th><td><?= htmlspecialchars($row['dept_code']) ?></td></tr>
        <tr>
            <th>予約権有無</th>
            <?php $class = ($row['authority'] == 1) ? 'text-success' : 'text-danger'; ?>
            <td class="<?= $class ?>"><?= htmlspecialchars(KsuCode::MBR_AUTHORITY[$row['authority']] ?? '不明') ?></td>
        </tr>
    </table>

    <div class="pb-5 mb-5">
        <?php if ($is_admin): ?>
            <?php $label = ($row['authority']) ? '予約権撤回' : '予約権付与'; ?>
            <a class="btn btn-outline-success m-1" href="index.php?to=mbr&do=grant&id=<?= htmlspecialchars($row['id']) ?>"><?= $label ?></a>
        <?php endif; ?>

        <?php if ($is_admin || $is_owner): ?>
            <a class="btn btn-outline-primary m-1" href="index.php?to=mbr&do=input&id=<?= htmlspecialchars($row['id']) ?>">編集</a>
        <?php endif; ?>

        <?php if ($is_admin): ?>
            <a href="#myModal" class="btn btn-outline-danger m-1" data-id="<?= htmlspecialchars($row['id']) ?>" data-toggle="modal">削除</a>
        <?php endif; ?>

        <a href="index.php?to=mbr&do=list" class="btn btn-outline-info m-1">戻る</a>
    </div>

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
            <a href="#" data-url="index.php?to=mbr&do=delete" class="btn btn-danger" id="deleteBtn">はい</a>
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
<?php endif; ?>