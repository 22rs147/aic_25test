<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card mt-5">
            <div class="card-header">
                <h4>ログイン</h4>
            </div>
            <div class="card-body">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <form action="index.php?to=user&do=auth" method="post">
                    <div class="form-group">
                        <label for="uid">ユーザーID</label>
                        <input type="text" class="form-control" id="uid" name="uid" required>
                    </div>
                    <div class="form-group">
                        <label for="upass">パスワード</label>
                        <input type="password" class="form-control" id="upass" name="upass" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">ログイン</button>
                </form>
            </div>
        </div>
    </div>
</div>