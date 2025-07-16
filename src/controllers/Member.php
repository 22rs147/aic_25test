<?php

namespace aic\controllers;

use aic\models\User;
use aic\models\Security;

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

    /**
     * 会員情報編集フォーム表示
     */
    public function inputAction($id = null)
    {
        $security = new Security();
        $security->require('login'); // ログイン必須

        $mbr_id = (int)$id;

        // 管理者でない場合は、自分の情報しか編集できない
        if (!(new User)->isAdmin()) {
            $security->require('owner', $mbr_id);
        }

        $row = $this->model->getDetail($mbr_id);

        $data = [
            'row' => $row,
            'mbr_id' => $mbr_id
        ];

        $this->view->render('mbr_input.php', $data);
    }

    // 会員情報保存
    public function saveAction()
    {
        // 1. 権限チェック
        $security = new Security();
        $security->require('login'); // ログインは必須

        // POSTデータからIDを取得。なければ一覧へリダイレクト
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $this->view->redirect('index.php?to=mbr&do=list');
            return;
        }
        $mbr_id = (int)$_POST['id'];

        // 管理者でなければ、自分の情報しか保存できない
        $user = new User();
        if (!$user->isAdmin()) {
            $security->require('owner', $mbr_id);
        }

        // 2. 保存するデータをPOSTから取得 (mbr_input.phpのフォーム項目に対応)
        $data = [
            'id'        => $mbr_id,
            'ja_name'   => $_POST['ja_name']   ?? null,
            'ja_yomi'   => $_POST['ja_yomi']   ?? null,
            'en_name'   => $_POST['en_name']   ?? null,
            'en_yomi'   => $_POST['en_yomi']   ?? null,
            'email'     => $_POST['email']     ?? null,
            'tel_no'    => $_POST['tel_no']    ?? null,
            'sex'       => $_POST['sex']       ?? null,
            'dept_name' => $_POST['dept_name'] ?? null,
            'dept_code' => $_POST['dept_code'] ?? null,
        ];

        // 3. モデルのwriteメソッドでデータを保存
        $this->model->write($data);

        // 4. 保存後、詳細ページにリダイレクト
        $this->view->redirect('index.php?to=mbr&do=detail&id=' . $mbr_id);
    }
}
