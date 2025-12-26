<?php

namespace aic\controllers;

use aic\models\Member;
use aic\models\Staff as StaffModel;
use aic\models\Security;
use aic\models\KsuCode;

class Staff extends Controller
{
    /**
     * 教職員詳細情報を表示するアクション
     */
    public function detailAction($id = 0)
    {
        $mbr_id = (int)$id;

        // IDが指定されていない場合は会員一覧へリダイレクト
        if ($mbr_id === 0) {
            $this->redirect('index.php?to=mbr&do=list');
            return;
        }

        $staff_model = new StaffModel();
        $member_model = new Member();

        $member = $member_model->getDetail($mbr_id);
        $staffs = $staff_model->getList('member_id=' . $mbr_id);
        $staff = (count($staffs) > 0) ? $staffs[0] : null;

        if (!$member || !$staff) {
            // 教職員情報が見つからない場合
            $this->view->assign('error_message', '指定された教職員情報は存在しません。');
            $this->view->render('stf_detail.php');
            return;
        }

        // ビューに渡すデータを準備
        $data = [
            'member' => $member,
            'staff' => $staff,
            'is_admin' => $this->user->isAdmin(),
            'mbr_authority' => KsuCode::MBR_AUTHORITY[$member['authority']],
            'staff_responsible' => KsuCode::STAFF_RESPONSIBLE[$staff['responsible']],
            'responsible_class' => ($staff['responsible'] == 1) ? 'text-success' : 'text-danger',
            'grant_label' => ($staff['responsible'] == 0) ? '責任者指定' : '責任者指定撤回',
        ];

        // ビューをレンダリング
        $this->view->render('stf_detail.php', $data);
    }

    /**
     * 責任者権限を付与/撤回するアクション
     * @param int|null $id staff.id
     */
    public function grantAction($id = null)
    {
        // 1. 権限チェック (管理者のみ)
        (new Security)->require('admin');

        $staff_id = (int)$id;

        // 2. 責任者権限をトグル
        $record = $this->model->getDetail($staff_id);
        $member_id = $record['member_id'];
        $responsible = $record['responsible'] == 0 ? 1 : 0;
        $data = ['id' => $staff_id, 'responsible' => $responsible];
        $this->model->write($data);

        // 3. 教職員詳細ページにリダイレクト
        $this->redirect('index.php?to=stf&do=detail&id=' . $member_id);
    }
}
