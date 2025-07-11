<?php
namespace aic\controllers;

use aic\models\User;

class Member extends Controller
{

    /**
     * 会員詳細情報を表示するアクション
     */
    public function detailAction($id = null)
    {
        $user = new User();

        // IDがURLで指定されていない場合、ログイン中のユーザーの会員IDを使用
        if (is_null($id)) {
            $id = $user->getLoginMemberId();
        }

        // IDが特定できない場合は会員一覧へリダイレクト
        if (is_null($id)) {
            $this->view->redirect('index.php?to=mbr&do=list');
            return;
        }

        $row = $this->model->getDetail($id);

        if (!$row) {
            // 会員情報が見つからない場合
            $this->view->assign('error_message', '指定された会員情報は存在しません。');
        } else {
            $this->view->assign('row', $row);
            $this->view->assign('is_admin', $user->isAdmin());
            $this->view->assign('is_owner', $user->isOwner($id));
        }
        
        $this->view->render('mbr_detail.php');
    }

    // listAction, saveAction などの他のアクションもここに追加できます
}