<?php
use aic\views\Html;
use aic\models\KsuCode;

// フォームで使う変数を展開
foreach($rsv as $key => $value){
    $$key = $value;
}
?>
<h2>総合機器センター機器設備利用申請</h2>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="font-size: 1.25rem;">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<form class="needs-validation" method="post" action="?to=rsv&do=save">
<table class="table table-bordered table-hover">
<input type="hidden" name="id" value="<?= htmlspecialchars($rsv_id, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="code" value="<?= htmlspecialchars($rsv_code, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="instrument_id" value="<?= htmlspecialchars($instrument_id, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="apply_mid" value="<?= htmlspecialchars($apply_member['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
<tr><td width="20%" class="text-info">利用申請者</td>
    <td><?= htmlspecialchars($apply_member['ja_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
    <td class="text-info">会員番号</td>
    <td colspan="2"><?= htmlspecialchars($apply_member['sid'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
</tr>
<tr><td class="text-info form-group">利用目的※</td>
    <td colspan="2"><?= Html::select($rsv_purpose_options, 'purposed', [$purpose]) ?>
    </td>
    <td colspan="2"><?= Html::input('text', 'purpose', $purpose, ' placeholder="「その他」の内容"') ?></td>
</tr>
<tr><td class="text-info">利用責任者</td>
<td colspan="4"><?= Html::select($staffs, 'master_sid', [$master_sid], 'select', '1') ?></td>
</tr>
<tr><td class="text-info">利用者<div class="text-danger"> (学籍番号・職員番号を各欄に一つずつ入力。例: 21LL999)</div></td>
    <td class="pt-0 pb-0" colspan="4"><table class="table table-light" width="100%">
<?php
$n = count($rsv_member);
foreach(range(0,2) as $i){
    list($k1, $k2, $k3, $k4) = [4*$i, 4*$i+1, 4*$i+2, 4*$i+3];
    $sid1 = $k1 < $n ? $rsv_member[$k1]['sid'] : '';
    $sid2 = $k2 < $n ? $rsv_member[$k2]['sid'] : '';
    $sid3 = $k3 < $n ? $rsv_member[$k3]['sid'] : '';
    $sid4 = $k4 < $n ? $rsv_member[$k4]['sid'] : '';
    printf('<tr><td>%s</td>', Html::input('text',"rsv_member[]", $sid1 ));
    printf('<td>%s</td>',Html::input('text',"rsv_member[]", $sid2 ));
    printf('<td>%s</td>',Html::input('text',"rsv_member[]", $sid3 ));
    printf('<td>%s</td></tr>',Html::input('text',"rsv_member[]", $sid4 ));
}
?>
    </table></td>
</tr>
<tr><td class="text-info"><div class="text-danger"> (外部利用者のみ入力)</div>その他利用者数 (人)</td>
    <td><?= Html::input('number', 'other_num', $other_num, 'min=0') ?></td>
    <td colspan="3"><?= Html::input('text', 'other_user', $other_user, 'placeholder="内訳：○○株式会社４名、○○学校2名"') ?></td>
</tr>
<tr><td class="text-info">希望利用機器</td>
    <td colspan="4"><?= htmlspecialchars($instrument_name, ENT_QUOTES, 'UTF-8') ?></td>
</tr>
<tr><td class="text-info form-group"><div class="text-danger"> (1週間まで)</div>希望利用日時</td>
    <td colspan="2"><input type="datetime-local" name="stime" id="stime" value="<?= htmlspecialchars(substr($stime, 0, 16), ENT_QUOTES, 'UTF-8') ?>" min="" step="600"></td>
    <td colspan="2"><input type="datetime-local" name="etime" id="etime" value="<?= htmlspecialchars(substr($etime, 0, 16), ENT_QUOTES, 'UTF-8') ?>" step="600"></td>
</tr>
<tr><td class="text-info">試料名</td>
    <td colspan="4"><?= Html::input('text', 'sample_name', $sample_name) ?></td>
</tr>
<tr><td class="text-info">試料の形態</td>
    <td colspan="4"><?= Html::select($sample_state_options, 'sample_state', [$sample_state], 'radio') ?></td>
</tr>
<tr><td class="text-info">試料についての特記事項</td>
    <td colspan="3"><?= Html::select($sample_nature_options, 'rsv_sample[]', $sample_nature, 'checkbox') ?></td>
    <td><?= Html::input('text', 'sample_other', $sample_other, 'placeholder="「その他」の内容"') ?></td>
</tr>
<tr>
    <td class="text-info">X線取扱者登録の有無</td><td><?= Html::select($yesno_options, 'xray_chk', [$xray_chk], 'radio') ?></td>
</tr>
<tr><td class="text-info">備考</td>
    <td colspan="4"><?= Html::textarea('memo', $memo, 'class="form-control" rows="4"') ?></td>
</tr>
</table>
<div class="pb-5 mb-5">
<button type="submit" class="btn btn-outline-primary m-1">保存</button>
<button type="button" onclick="history.back();" class="btn btn-outline-info m-1">戻る</button>
</div>
</form>

<script>
const occupied = <?= $occupied_periods_json ?>;
$.validator.setDefaults({
  errorClass: "text-danger",
  validClass: "text-success",
  focusCleanup: true,
  highlight : function(element, errorClass, validClass) {
    $(element).closest(".form-group").addClass(errorClass).removeClass(validClass);
  },
  unhighlight : function(element, errorClass, validClass) {
    $(element).closest(".form-group").removeClass(errorClass).addClass(validClass);
  }
});
$( "form" ).validate({
  rules: {
    purpose: "required",
    stime : { required: true },
    etime : { required : true, validateTimePeriod: true },
  },
  messages: {
    purpose: "利用目的が必須です"
  },
});
var overlaped = function (a1, a2, b1, b2){
    return Math.max(a1, b1) < Math.min(a2,b2);
}
$.validator.addMethod(
    "validateTimePeriod",
    function(value, element) {
        const stime = new Date($('#stime').val());
        const etime = new Date($('#etime').val());
        if (stime >= etime) return false;
        for (const period of occupied) {
            const p0 = new Date(period[0]);
            const p1 = new Date(period[1]);
            if (overlaped(stime, etime, p0, p1)) return false;
        }
        return true;
    },
    "有効な期間ではありません。"
);
</script>