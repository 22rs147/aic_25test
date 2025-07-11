<?php
namespace aic\controllers;

use aic\models\Member;

class User extends Controller
{

    public function loginAction()
    {
        // ログイン済みならホームページへリダイレクト
        if (isset($_SESSION['uid'])) {
            $this->view->redirect('index.php?to=inst&do=list');
            return; // redirect()内でexit()が呼ばれる
        }
        $this->view->render('usr_login.php');
    }

    /**
     * ログアウト処理
     */
    public function logoutAction()
    {
        // セッション変数をすべて空にする
        $_SESSION = [];

        // セッションクッキーを削除
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // セッションを完全に破棄
        session_destroy();

        // ログインページにリダイレクト
        $this->view->redirect('index.php?to=user&do=login');
    }

    /**
     * ユーザー認証処理
     */
    public function authAction()
    {
        if (!isset($_POST['uid']) || !isset($_POST['upass'])) {
            $this->view->redirect('index.php?to=user&do=login');
            return;
        }

        $uid = $_POST['uid'];
        $upass = $_POST['upass'];

        // 既存のUserモデルのcheckメソッドで認証
        $user = $this->model->check($uid, $upass);
        
        if ($user){
            // 認証成功：セッションIDを再生成し、情報を格納
            session_regenerate_id(true);

            $_SESSION['uid'] = $user['uid'];
            $_SESSION['uname'] = $user['uname'];
            $_SESSION['urole'] = $user['urole'];

            $this->model->updateLoginTime($user['uid'], date('Y-m-d H:i:s'));

            // 関連する会員情報を取得してセッションに格納
            $member_model = new Member();
            $member = $member_model->getDetailByUid($user['uid']);
            if ($member) {
                $_SESSION['member_id'] = $member['id'];
                $_SESSION['member_authority'] = $member['authority'];
            }

            $this->view->redirect('index.php?to=inst&do=list');
        } else {
            // 認証失敗
            $this->view->assign('error_message', 'ユーザーIDまたはパスワードが間違っています。');
            $this->view->render('usr_login.php'); 
        }
    }
}