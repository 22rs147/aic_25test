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
        // IDがURLで指定されていない場合は、ログイン中のユーザーの会員IDを使用します。
        if (is_null($id)) {
            $id = $this->user->getLoginMemberId();
        }

        // IDが特定できない場合は、会員一覧へリダイレクトします。
        if (is_null($id)) {
            $this->redirect('index.php?to=mbr&do=list');
            return;
        }

        $row = $this->model->getDetail($id);

        if (!$row) {
            // 会員情報が見つからない場合は、エラーメッセージを表示します。
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
        // 会員種別によるフィルタリング条件を取得します。
        $category = 0;
        if (isset($_POST['category'])) {
            $category = (int)$_POST['category'];
            $_SESSION['selected_category'] = $category;
        } elseif (isset($_SESSION['selected_category'])) {
            $category = $_SESSION['selected_category'];
        }

        // ページネーションの準備を行います。
        $page = (int)$page;
        $where = ($category == 0) ? '1' : 'category=' . $category;
        $num_rows = $this->model->getNumRows($where, 'id');

        // 会員リストを取得します。
        $rows = $this->model->getList($where, 'authority,id', $page);

        // ビューに渡すためのデータを準備します。
        // 会員種別プルダウンの選択肢を設定します。
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

        // ビューをレンダリングします。
        $this->view->render('mbr_list.php', $data);
    }


    /**
     * 会員情報編集フォーム表示
     */
    public function inputAction($id = null)
    {
        $security = new Security();
        $security->require('login');

        $mbr_id = (int)$id;

        // 管理者でない場合は、自分の情報しか編集できないように制限します。
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

    /**
     * 会員情報を保存します。
     */
    public function saveAction()
    {
        // 権限チェックを行います。
        $security = new Security();
        $security->require('login');

        // POSTデータからIDを取得します。IDがない場合は一覧へリダイレクトします。
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $this->redirect('index.php?to=mbr&do=list');
            return;
        }
        $mbr_id = (int)$_POST['id'];

        // 管理者でなければ、自分の情報しか保存できないように制限します。
        if (!$this->user->isAdmin()) {
            $security->require('owner', $mbr_id);
        }

        // 保存するデータをPOSTから取得します。
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

        // モデルを使用してデータを保存します。
        $this->model->write($data);

        // 保存完了後、詳細ページにリダイレクトします。
        $this->redirect('index.php?to=mbr&do=detail&id=' . $mbr_id);
    }

    /**
     * 予約権を付与/撤回するアクション
     */
    public function grantAction($id = null)
    {
        // 管理者にのみこの操作を許可します。
        (new Security)->require('admin');

        $mbr_id = (int)$id;

        // 現在の予約権限を反転させて保存します。
        $record = $this->model->getDetail($mbr_id);
        $new_authority = $record['authority'] == 1 ? 0 : 1;
        $this->model->write(['id' => $mbr_id, 'authority' => $new_authority]);

        // 処理完了後、会員詳細ページにリダイレクトします。
        $this->redirect('index.php?to=mbr&do=detail&id=' . $mbr_id);
    }
}
