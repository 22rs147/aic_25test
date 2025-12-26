<?php
use aic\views\Html;
use aic\models\KsuCode;

// htmlspecialcharsのショートカット
$h = 'htmlspecialchars';
?>

<h3>会員一覧</h3>

<div class="text-left">
    <form method="post" action="?to=mbr&do=list" class="form-inline">
        <div class="form-group mb-2">
            <?= Html::select($category_options, 'category', [$selected_category]) ?>
        </div>
        <div class="form-group mx-sm-3 mb-2">
            <button type="submit" name="s" class="btn btn-outline-primary mt-1 mb-1 mr-1">絞り込み</button>
        </div>
    </form>
</div>

<?php
// pagination on top
echo Html::pagination($num_rows, $page_rows, $page);
?>

<table class="table table-hover">
    <tr><th>会員ID</th><th>会員名</th><th>所属</th><th>種別</th><th>電話番号</th><th>予約権</th><th>詳細</th></tr>
    <?php foreach ($rows as $row): ?>
    <tr>
        <td><?= $h($row['sid']) ?></td>
        <td><?= $h($row['ja_name']) ?></td>
        <td><?= $h($row['dept_name']) ?></td>
        <td><?= $h(KsuCode::MBR_CATEGORY[$row['category']] ?? '不明') ?></td>
        <td><?= $h($row['tel_no']) ?></td>
        <td><?= $h(KsuCode::MBR_AUTHORITY[$row['authority']] ?? '不明') ?></td>
        <td><a class="btn btn-sm btn-outline-success" href="?to=mbr&do=detail&id=<?= $row['id'] ?>">詳細</a></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php
// pagination at bottom
echo Html::pagination($num_rows, $page_rows, $page);
?>
