<?php
use aic\models\Util;

if (isset($error_message)): ?>
  <div class="alert alert-danger" role="alert">
    <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
  </div>
  <a href="index.php?to=rsv&do=list" class="btn btn-outline-info m-2">予約一覧へ戻る</a>
<?php else: ?>
  <h3>機器設備利用申請内容詳細</h3>
  <table class="table table-bordered table-hover">
    <tr>
        <td width="20%" class="text-info">利用申請者氏名</td>
        <td><?= htmlspecialchars($rsv['apply_member']['ja_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-info">会員番号</td>
        <td colspan="2"><?= htmlspecialchars($rsv['apply_member']['sid'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <td class="text-info">利用責任者氏名</td>
        <td><?= htmlspecialchars($rsv['master_member']['ja_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-info">学部学科</td>
        <td><?= htmlspecialchars($rsv['dept_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($rsv['master_member']['tel_no'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <td class="text-info">利用代表者氏名</td>
        <td class="pt-0 pb-0" colspan="4">
            <table class="table table-light" width="100%">
                <?php foreach($rsv['rsv_member'] as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['sid'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['ja_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['tel_no'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </td>
    </tr>
    <tr>
        <td class="text-info">教職員人数</td>
        <td><?= htmlspecialchars($rsv['staff_n'] ?? '0', ENT_QUOTES, 'UTF-8') ?>人</td>
        <td class="text-info">学生人数</td>
        <td colspan="2"><?= htmlspecialchars($rsv['student_n'] ?? '0', ENT_QUOTES, 'UTF-8') ?>人</td>
    </tr>
    <tr>
        <td class="text-info">その他利用者数</td>
        <td><?= htmlspecialchars($rsv['other_num'] ?? '0', ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-info">利用者説明</td>
        <td colspan="2"><?= htmlspecialchars($rsv['other_user'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <td class="text-info">希望利用機器</td>
        <td colspan="4"><?= htmlspecialchars($rsv['instrument_fullname'] ?? '', ENT_QUOTES, 'UTF-8') ?>（<?= htmlspecialchars($rsv['instrument_shortname'] ?? '', ENT_QUOTES, 'UTF-8') ?>）</td>
    </tr>
    <tr>
        <td class="text-info">設置場所</td>
        <td colspan="4"><?= htmlspecialchars($rsv['room_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>（<?= htmlspecialchars($rsv['room_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>）</td>
    </tr>
    <tr>
        <td class="text-info">予約申請日時</td>
        <td colspan="4"><?= isset($rsv['reserved']) ? Util::jpdate($rsv['reserved'], true, true) : '' ?></td>
    </tr>
    <tr>
        <td class="text-info">希望利用日時</td>
        <td colspan="4">
            <?= isset($rsv['stime']) ? Util::jpdate($rsv['stime'], true, true) : '' ?>
            　～　
            <?= isset($rsv['etime']) ? Util::jpdate($rsv['etime'], true, true) : '' ?>
        </td>
    </tr>
    <tr><td class="text-info">試料名</td><td colspan=4><?= htmlspecialchars($rsv['sample_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><td class="text-info">試料状態</td><td colspan=4><?= htmlspecialchars($rsv['sample_state_str'] ?? '', ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr>
      <td class="text-info">試料特性</td><td colspan=2><?= htmlspecialchars($rsv['sample_nature_str'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
      <td colspan=2><?= htmlspecialchars($rsv['sample_other'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
      <td class="text-info">X線取扱者登録の有無</td><td colspan=2><?= htmlspecialchars($rsv['xray_chk_str'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
      <td class="text-info">登録者番号</td><td colspan=2><?= htmlspecialchars($rsv['xray_num'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr><td class="text-info">備考</td><td colspan=4><?= htmlspecialchars($rsv['memo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr>
      <td class="text-info">承認状態</td>
      <td colspan=2 class="<?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($rsv['status_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
      <td class="text-info">申請番号</td><td><?= htmlspecialchars($rsv['code'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
  </table>

  <?php
    if ($is_admin) {
      // Note: 'rsv_grant' action should be added to the router in index.php to work correctly.
      echo '<a class="btn btn-outline-success m-2" href="index.php?to=rsv&do=grant&id=' . urlencode($rsv['id']) . '">' . htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') . '</a>';
    }
    if ($is_admin || $is_owner) {
      echo '<a class="btn btn-outline-primary m-2" href="index.php?to=rsv&do=input&id=' . urlencode($rsv['id']) . '&copy=1">コピー</a>' . PHP_EOL .
           '<a class="btn btn-outline-primary m-2" href="index.php?to=rsv&do=input&id=' . urlencode($rsv['id']) . '">編集</a>' . PHP_EOL .
           '<a href="#myModal" class="btn btn-outline-danger m-2" data-id="' . urlencode($rsv['id']) . '" data-toggle="modal">削除</a>' . PHP_EOL;
    }
    echo '<a href="index.php?to=rsv&do=list&page=' . urlencode($page) . '" class="btn btn-outline-info m-2">戻る</a>';
  ?>

  <!-- Modal HTML -->
  <div id="myModal" class="modal fade">
    <div class="modal-dialog modal-confirm">
      <div class="modal-content">
        <div class="modal-header"><div class="icon-box"><i class="material-icons">&#xE5CD;</i></div><h4 class="text-info">この予約を削除しますか？</h4><button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button></div>
        <div class="modal-body"><p>「はい」を押したら、この予約を削除します。</p></div>
        <div class="modal-footer">
          <a href="#" data-url="index.php?to=rsv&do=delete" class="btn btn-danger" id="deleteBtn">はい</a>
          <button type="button" class="btn btn-info" data-dismiss="modal">いいえ</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    $(document).ready(function() {
      $('#myModal').on('shown.bs.modal', function(event) {
        var id = $(event.relatedTarget).data('id');
        var href = $(this).find('#deleteBtn').data('url') +'&id=' + id;
        $(this).find('#deleteBtn').attr('href', href);
      });
    });
  </script>
<?php endif; ?>