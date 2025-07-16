<?php
namespace aic\controllers;

use aic\models\Instrument;
use aic\models\Reserve as ReserveModel;
use aic\models\Member;
use aic\models\Staff;
use aic\models\Security;
use aic\models\KsuCode;
use aic\models\RsvMember;
use aic\models\RsvSample;
use aic\models\User;
use aic\models\Util;
use DateTime;

class Reserve extends Controller
{
    public function detailAction($id = 0, $page = 1)
    {
        // 1. 権限チェック (詳細表示はログイン必須)
        (new Security)->require('login');

        $rsv_id = (int)$id;
        if ($rsv_id === 0) {
            $this->view->redirect('index.php?to=rsv&do=list');
            return;
        }

        // 2. モデルから予約詳細データを取得
        $rsv = $this->model->getDetail($rsv_id);

        if (!$rsv) {
            // 予約が見つからない場合のエラー処理
            $this->view->assign('error_message', '指定された予約情報は見つかりませんでした。');
            $this->view->render('rsv_detail.php');
            return;
        }

        // 3. ビューに渡すデータを準備
        $user_model = new User();
        $is_admin = $user_model->isAdmin();
        $is_owner = $user_model->isOwner($rsv['apply_mid']); // 申請者IDで所有者かチェック

        $status = $rsv['process_status'];
        $status_label = ($status == 1 || $status == 3) ? '承認' : '却下';

        $status_class_map = [
            1 => 'text-info',    // 申請中
            2 => 'text-success', // 承認済
            3 => 'text-danger',  // 却下済
            4 => 'text-muted',   // キャンセル済 (例)
        ];
        $status_class = $status_class_map[$status] ?? 'text-dark';

        // ビューにデータを渡す
        $this->view->assign('rsv', $rsv);
        $this->view->assign('is_admin', $is_admin);
        $this->view->assign('is_owner', $is_owner);
        $this->view->assign('status_label', $status_label);
        $this->view->assign('status_class', $status_class);
        $this->view->assign('page', (int)$page); // 戻るボタン用にページ番号を渡す

        // 4. ビューをレンダリング
        $this->view->render('rsv_detail.php');
    }

    /**
     * 予約入力フォームを表示するアクション
     * 新規作成と編集の両方を扱う
     */
    public function inputAction($id = 0, $inst = null, $d = null, $copy = 0)
    {
        // 1. 権限チェック
        (new Security)->require('login');
        (new Security)->require('reserve');

        // 2. データの取得と準備
        $rsv_id = (int)$id;
        $is_copy = ($copy == 1);

        // コピーの場合、元のデータを取得して新しいデータにマージ
        if ($is_copy && $rsv_id > 0) {
            $source_rsv = $this->model->getDetail($rsv_id);
            $rsv = $this->model->getDetail(0); // 新規作成用のテンプレートを取得

            // 必要なフィールドをコピー
            $fields_to_copy = [
                'instrument_id', 'purpose', 'master_mid', 'rsv_member',
                'other_num', 'other_user', 'sample_name', 'sample_state',
                'sample_nature', 'sample_other', 'xray_chk', 'memo'
            ];
            foreach ($fields_to_copy as $field) {
                if (isset($source_rsv[$field])) {
                    $rsv[$field] = $source_rsv[$field];
                }
            }
            $rsv_id = 0; // コピーなのでIDは0にする
        } else {
            $rsv = $this->model->getDetail($rsv_id);
        }

        // URLパラメータから機器IDや日付が指定された場合の処理
        if ($inst !== null) {
            $rsv['instrument_id'] = (int)$inst;
        }

        $instrument = null;
        // instrument_id が空でないことを確認してから getDetail を呼び出す
        if (!empty($rsv['instrument_id'])) {
            $instrument = (new Instrument)->getDetail($rsv['instrument_id']);
        }
        // instrument が null の場合に備えて null 合体演算子を使用
        $rsv['instrument_name'] = $instrument['fullname'] ?? '';

        // 開始・終了日時の設定
        $stime = date('Y-m-d H:00');
        if ($d !== null) {
            $ymd = DateTime::createFromFormat('ymd', $d);
            if ($ymd) {
                $stime = $ymd->format('Y-m-d H:00');
            }
        }

        if ($rsv_id == 0) { // 新規作成またはコピーの場合
            $rsv['stime'] = $stime;
            $rsv['etime'] = $stime;
        }

        // 3. ビューに渡すデータを準備
        $staffs = (new Staff)->getOptions('responsible');
        $master_sid = isset($rsv['master_member']['sid']) ? $rsv['master_member']['sid'] : '';

        // 予約済みの時間帯を取得 (バリデーション用)
        $occupied_periods = []; // ここで取得ロジックを実装

        // 4. ビューにデータを渡してレンダリング
        $this->view->render('rsv_input.php', [
            'rsv' => $rsv,
            'rsv_id' => $rsv_id,
            'rsv_code' => $rsv['code'] ?? '',
            'master_sid' => $master_sid,
            'staffs' => $staffs,
            'rsv_purpose_options' => KsuCode::RSV_PURPOSE,
            'sample_state_options' => KsuCode::SAMPLE_STATE,
            'sample_nature_options' => KsuCode::SAMPLE_NATURE,
            'yesno_options' => KsuCode::YESNO,
            'occupied_periods_json' => json_encode($occupied_periods),
        ]);
    }

    /**
     * 予約情報を保存するアクション (rsv_inputから呼び出される)
     * inst_saveAction という名前で作成
     */
    public function saveAction()
    {
        // 1. 権限チェック
        (new Security)->require('login');
        (new Security)->require('reserve');

        // 2. データ取得と初期化
        $data = $_POST;
        $rsv_id = (int)($data['id'] ?? 0);

        // rsv_input.php のフォーム項目に対応するホワイトリスト
        $rsv_fields = [
            'id' => 0, 'code' => '', 'instrument_id' => 0, 'apply_mid' => 0, 'master_mid' => 0,
            'process_status' => 1, 'purpose_id' => 0, 'purpose' => '', 'other_num' => 0,
            'other_user' => '', 'stime' => '', 'etime' => '', 'sample_name' => '', 'sample_state' => 1,
            'xray_chk' => 0, 'xray_num' => '', 'memo' => '',
        ];

        $rsv = [];
        foreach ($rsv_fields as $key => $default) {
            $rsv[$key] = $data[$key] ?? $default;
        }
        $rsv['id'] = $rsv_id;

        // 3. バリデーション
        $errors = [];

        // 3-1. 予約時間の重複チェック
        $existed_rsv = $this->model->getListByInst($rsv['instrument_id'], $rsv['stime'], $rsv['etime']);
        $is_overlapping = false;
        foreach ($existed_rsv as $existing) {
            if ((int)$existing['id'] !== $rsv_id) {
                $is_overlapping = true;
                break;
            }
        }
        if ($is_overlapping) {
            $errors[] = sprintf(
                "ほかの予約時間帯と被っています：%s～%s",
                Util::jpdate($rsv['stime'], true),
                Util::jpdate($rsv['etime'], true)
            );
        }

        // 3-2. 開始・終了時刻の妥当性チェック
        if (strtotime($rsv['stime']) >= strtotime($rsv['etime'])) {
            $errors[] = "無効な時間帯です。終了時刻は開始時刻より後に設定してください。";
        }

        // 3-3. 予約期間の長さチェック
        $diff_days = (strtotime($rsv['etime']) - strtotime($rsv['stime'])) / (60 * 60 * 24);
        if ($diff_days >= 7) {
            $errors[] = "予約期間は1週間までです";
        }

        // 3-4. 利用責任者のチェック
        $member_model = new Member();
        if (empty($data['master_sid'])) {
            $errors[] = "利用責任者を選択してください";
        } else {
            $master_member = $member_model->getDetailBySid($data['master_sid']);
            if ($master_member) {
                $rsv['master_mid'] = $master_member['id'];
            } else {
                $errors[] = "指定された利用責任者は無効です。";
            }
        }

        // 3-5. 申請者の設定
        $user_model = new User();
        $rsv['apply_mid'] = $user_model->getLoginMid();
        if (empty($rsv['apply_mid'])) {
            $errors[] = "申請者の情報が取得できませんでした。再度ログインしてください。";
        }

        // 3-6. 利用代表者のチェック
        $rsv_members = [];
        if (isset($data['rsv_member']) && is_array($data['rsv_member'])) {
            foreach ($data['rsv_member'] as $sid) {
                if (empty(trim($sid))) continue;
                $member = $member_model->getDetailBySid(trim($sid));
                if ($member) {
                    $rsv_members[] = $member;
                } else {
                    $errors[] = sprintf("'%s'：無効な利用代表者IDです", htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'));
                }
            }
        }
        if (empty($rsv_members)) {
            $errors[] = "有効な利用代表者を1名以上指定してください";
        }

        // 4. エラー処理
        if (count($errors) > 0) {
            $this->view->assign('errors', $errors);
            $this->view->assign('rsv', array_merge($rsv, $data)); // ユーザーの入力を保持
            $this->inputAction($rsv_id, $rsv['instrument_id']); // 入力画面を再表示
            return;
        }

        // 5. データ保存
        if ($rsv_id == 0) { // 新規作成
            $rsv['code'] = $this->model->nextCode();
            $rsv['reserved'] = date('Y-m-d H:i:s');
        }
        $new_rsv_id = $this->model->write($rsv);
        $rsv_id = ($rsv_id == 0) ? $new_rsv_id : $rsv_id;

        // 関連データの保存
        (new RsvMember)->reset($rsv_id);
        foreach ($rsv_members as $member) {
            (new RsvMember)->write(['id' => 0, 'reserve_id' => $rsv_id, 'member_id' => $member['id']]);
        }

        (new RsvSample)->reset($rsv_id);
        if (isset($data['rsv_sample']) && is_array($data['rsv_sample'])) {
            foreach ($data['rsv_sample'] as $val) {
                $other = ($val == 4 && isset($data['sample_other'])) ? $data['sample_other'] : '';
                (new RsvSample)->write(['id' => 0, 'reserve_id' => $rsv_id, 'nature' => $val, 'other' => $other]);
            }
        }

        // 6. 完了後、詳細ページにリダイレクト
        $this->view->redirect('index.php?to=rsv&do=detail&id=' . $rsv_id);
    }
}