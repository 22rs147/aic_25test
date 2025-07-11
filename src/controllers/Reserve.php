<?php
namespace aic\controllers;

use aic\models\Instrument;
use aic\models\Reserve as ReserveModel;
use aic\models\Staff;
use aic\models\Security;
use aic\models\KsuCode;
use DateTime;

class Reserve extends Controller
{
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
}