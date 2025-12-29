<?php
namespace aic\controllers;

use aic\models\Member;

class User extends Controller
{

    public function loginAction()
    {
        // 既にログイン済みの場合は、機器一覧画面へリダイレクトします。
        if (isset($_SESSION['uid'])) {
            $this->view->redirect('index.php?to=inst&do=list');
            return;
        }
        $this->view->render('usr_login.php');
    }

    /**
     * ログアウト処理を行います。
     */
    public function logoutAction()
    {
        // セッション変数をすべて空にします。
        $_SESSION = [];

        // セッションクッキーを削除します。
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // セッションを完全に破棄します。
        session_destroy();

        // ログアウト完了後、ログインページにリダイレクトします。
        $this->view->redirect('index.php?to=user&do=login');
    }

    /**
     * ユーザー認証処理を行います。
     */
    public function authAction()
    {
        if (!isset($_POST['uid']) || !isset($_POST['upass'])) {
            $this->view->redirect('index.php?to=user&do=login');
            return;
        }

        $uid = $_POST['uid'];
        $upass = $_POST['upass'];

        // Userモデルを使用して認証を行います。
        $user = $this->model->check($uid, $upass);
        
        if ($user){
            // 認証に成功した場合、セッションIDを再生成し、ユーザー情報を格納します。
            session_regenerate_id(true);

            $_SESSION['uid'] = $user['uid'];
            $_SESSION['uname'] = $user['uname'];
            $_SESSION['urole'] = $user['urole'];

            $this->model->updateLoginTime($user['uid'], date('Y-m-d H:i:s'));

            // 関連する会員情報を取得し、セッションに格納します。
            $member_model = new Member();
            $member = $member_model->getDetailByUid($user['uid']);
            if ($member) {
                $_SESSION['member_id'] = (int)$member['id'];
                $_SESSION['member_authority'] = $member['authority'];
            }

            $this->view->redirect('index.php?to=inst&do=list');
        } else {
            // 認証に失敗した場合は、エラーメッセージを表示します。
            $this->view->assign('error_message', 'ユーザーIDまたはパスワードが間違っています。');
            $this->view->render('usr_login.php'); 
        }
    }
}