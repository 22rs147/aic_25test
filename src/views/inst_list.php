<?php

use aic\models\KsuCode;
?>

<div class="text-left">
    <?php foreach ($categories as $c => $label): ?>
        <?php $disabled = ($c == $selected) ? 'disabled' : ''; ?>
        <a href="index.php?to=inst&do=list&c=<?= $c ?>" class="btn btn-outline-primary <?= $disabled ?> m-1"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php foreach ($rows as $row):
    $url = 'img/instrument/' . $row['id'] . '.webp';
    if (!@GetImageSize($url)) {
        $url = 'img/dummy-image-square1.webp';
    }
?>
    <div class="row border border-bottom-0 m-1">
        <div class="col-md-4 pl-0">
            <img src="<?= $url ?>" height="200px" width="280px" class="rounded">
        </div>
        <div class="col-md-8">
            <h4 class="mt-0"><?= htmlspecialchars($row['fullname']) ?></h4>
            <div><span class="badge badge-secondary">主な用途</span> <?= htmlspecialchars($row['purpose']) ?></div>
            <div><span class="badge badge-secondary">メーカー・型式</span> <?= htmlspecialchars($row['maker'] . ' ' . $row['model']) ?></div>
            <div><span class="badge badge-secondary">設置場所</span> 〔<?= htmlspecialchars($row['room_no']) ?>〕<?= htmlspecialchars($row['room_name']) ?></div>
            <div class="small"><?= htmlspecialchars($row['detail']) ?></div>
            <div class="align-self-end">
                <a class="btn btn-sm btn-outline-danger m-1" href="index.php?to=inst&do=detail&id=<?= $row['id'] ?>">詳細</a>
                <a class="btn btn-sm btn-outline-danger m-1" href="index.php?to=aic&do=detail&id=<?= $row['id'] ?>">空き状況</a>
                <?php if ($can_reserve): ?>
                    <a class="btn btn-sm btn-outline-success m-1" href="index.php?to=rsv&do=input&inst=<?= $row['id'] ?>">予約</a>
                <?php endif; ?>
            </div>
        </div>
        <hr>
    </div>
<?php endforeach; ?>