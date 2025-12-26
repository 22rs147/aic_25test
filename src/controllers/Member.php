<?php

namespace aic\controllers;

use aic\models\Security;
use aic\models\KsuCode;

class Member extends Controller
{

    /**
     * 会員詳細情報を表示するアクション
     */
    public function detailAction($id = null)
    {
        // IDがURLで指定されていない場合、ログイン中のユーザーの会員IDを使用
        if (is_null($id)) {
            $id = $this->user->getLoginMemberId();
        }

        // IDが特定できない場合は会員一覧へリダイレクト
        if (is_null($id)) {
            $this->redirect('index.php?to=mbr&do=list');
            return;
        }

        $row = $this->model->getDetail($id);

        if (!$row) {
            // 会員情報が見つからない場合
            $this->view->assign('error_message', '指定された会員情報は存在しません。');
        } else {
            $this->view->assign('row', $row);
            $this->view->assign('is_admin', $this->user->isAdmin());
            $this->view->assign('is_owner', $this->user->isOwner($id));
        }

        $this->view->render('mbr_detail.php');
    }

    /**
     * 会員一覧を表示するアクション (管理者のみ)
     */
    public function listAction($page = 1)
    {
        // 1. 権限チェック (管理者のみ)
        (new Security())->require('admin');

        // 2. フィルタリング条件の取得 (会員種別)
        $category = 0;
        if (isset($_POST['category'])) {
            $category = (int)$_POST['category'];
            $_SESSION['selected_category'] = $category;
        } elseif (isset($_SESSION['selected_category'])) {
            $category = $_SESSION['selected_category'];
        }

        // 3. ページネーションの準備
        $page = (int)$page;
        $where = ($category == 0) ? '1' : 'category=' . $category;
        $num_rows = $this->model->getNumRows($where, 'id');

        // 4. 会員リストの取得
        $rows = $this->model->getList($where, 'authority,id', $page);

        // 5. ビューに渡すためのデータ準備
        // 会員種別プルダウンの選択肢
        $category_options = KsuCode::MBR_CATEGORY;
        $category_options[0] = '～会員種別選択～';
        ksort($category_options);

        $data = [
            'rows' => $rows,
            'num_rows' => $num_rows,
            'page' => $page,
            'page_rows' => KsuCode::PAGE_ROWS,
            'category_options' => $category_options,
            'selected_category' => $category,
        ];

        // 6. ビューをレンダリング
        $this->view->render('mbr_list.php', $data);
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
        if (!$this->user->isAdmin()) {
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
            $this->redirect('index.php?to=mbr&do=list');
            return;
        }
        $mbr_id = (int)$_POST['id'];

        // 管理者でなければ、自分の情報しか保存できない
        if (!$this->user->isAdmin()) {
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
        $this->redirect('index.php?to=mbr&do=detail&id=' . $mbr_id);
    }

    /**
     * 予約権を付与/撤回するアクション
     * @param int|null $id member.id
     */
    public function grantAction($id = null)
    {
        // 1. 権限チェック (管理者のみ)
        (new Security)->require('admin');

        $mbr_id = (int)$id;

        // 2. 予約権をトグル
        $record = $this->model->getDetail($mbr_id);
        $new_authority = $record['authority'] == 1 ? 0 : 1;
        $this->model->write(['id' => $mbr_id, 'authority' => $new_authority]);

        // 3. 会員詳細ページにリダイレクト
        $this->redirect('index.php?to=mbr&do=detail&id=' . $mbr_id);
    }
}
