<?php
use aic\models\KsuCode;

if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <a href="?to=inst&do=list" class="btn btn-outline-info m-1">機器一覧に戻る</a>
<?php else: ?>
    <p><img src="<?= htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') ?>" height="240" width="320" class="rounded" alt="<?= htmlspecialchars($row['fullname'], ENT_QUOTES, 'UTF-8') ?>"></p>
    <h3 class="text-primary"><?= htmlspecialchars($row['fullname'], ENT_QUOTES, 'UTF-8') ?></h3>
    <table class="table table-hover">
        <tr><th width="20%">機器ID</th><td><?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><th width="20%">機器名称</th><td><?= htmlspecialchars($row['fullname'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><th>略称</th><td><?= htmlspecialchars($row['shortname'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><th>主な用途</th><td><?= htmlspecialchars($row['purpose'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><th>状態</th><td><?= htmlspecialchars(KsuCode::INST_STATE[$row['state']] ?? '不明', ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><th>カテゴリ</th><td><?= htmlspecialchars(KsuCode::INST_CATEGORY[$row['category']] ?? '不明', ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><th>メーカー</th><td><?= htmlspecialchars($row['maker'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><th>型式</th><td><?= htmlspecialchars($row['model'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><th>導入年月</th><td><?= htmlspecialchars($row['bought_year'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><th>設置場所</th><td><?= htmlspecialchars($row['room_name'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><th>場所番号</th><td><?= htmlspecialchars($row['room_no'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><th>詳細</th><td><?= nl2br(htmlspecialchars($row['detail'], ENT_QUOTES, 'UTF-8')) ?></td></tr>
    </table>
    <div class="pb-5 mb-5">
    <?php if ($is_admin): ?>
        <a class="btn btn-outline-primary m-1" href="?to=inst&do=input&id=<?= (int)$row['id'] ?>">編集</a>
        <a href="#myModal" class="btn btn-outline-danger m-1" data-id="<?= (int)$row['id'] ?>" data-toggle="modal">削除</a>
    <?php endif; ?>
    <?php if ($can_reserve): ?>
        <a class="btn btn-outline-success m-1" href="?to=rsv&do=input&inst=<?= (int)$row['id'] ?>">予約</a>
    <?php endif; ?>
    <a class="btn btn-outline-success m-1" href="?to=aic&do=detail&id=<?= (int)$row['id'] ?>">空き状態</a>
    <a href="?to=inst&do=list" class="btn btn-outline-info m-1">戻る</a>
    </div>

    <!-- Modal HTML -->
    <div id="myModal" class="modal fade">
      <div class="modal-dialog modal-confirm">
        <div class="modal-content">
          <div class="modal-header">
            <div class="icon-box">
              <i class="material-icons">&#xE5CD;</i>
            </div>
            <h4 class="text-info">この機器設備を削除しますか？</h4>
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          </div>
          <div class="modal-body">
            <p>「はい」を押したら、この機器設備を削除します。</p>
          </div>
          <div class="modal-footer">
            <a href="#" data-url="?to=inst&do=delete" class="btn btn-danger" id="deleteBtn">はい</a>
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