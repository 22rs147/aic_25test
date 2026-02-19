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
use aic\models\Util;
use DateTime;

class Reserve extends Controller
{
    /**
     * 登録された予約を一覧形式で表示します。
     */
    public function listAction(
        $inst = 0,
        $status = 0,
        $y = null,
        $m = null,
        $d = 0,
        $t = 7, // timespan
        $room = null, // to avoid "Unknown named parameter" error
        $page = 1,
        $sort_col = null,
        $sort_dir = null
    ) {
        // 権限チェックとして、ログインしているか確認します。
        (new Security)->require('login');

        // デバッグ情報用の変数を初期化します。
        $debug_info = [];

        // 予約モデルのインスタンスを生成します。
        $reserve_model = new ReserveModel();

        // 検索パラメータを取得します。POSTリクエストがあればそれを優先し、なければセッションから、それもなければデフォルト値を使用します。
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $debug_info['source'] = 'POST';
            $inst = $_POST['id'] ?? $inst;
            $status = $_POST['status'] ?? $status;
            $y = $_POST['y'] ?? date('Y');
            $m = $_POST['m'] ?? date('m');
            $d = $_POST['d'] ?? 0;
            $t = $_POST['t'] ?? 7;
            $_SESSION['selected_inst'] = $inst;
            $_SESSION['selected_status'] = $status;
            $_SESSION['selected_year'] = $y;
            $_SESSION['selected_month'] = $m;
            $_SESSION['selected_day'] = $d;
            $_SESSION['selected_timespan'] = $t;
        } else if (isset($_SESSION['selected_inst'], $_SESSION['selected_status'])) {
            $debug_info['source'] = 'Session';
            $inst = $_SESSION['selected_inst'];
            $status = $_SESSION['selected_status'];
            $y = $_SESSION['selected_year'];
            $m = $_SESSION['selected_month'];
            $d = $_SESSION['selected_day'];
            $t = $_SESSION['selected_timespan'];
        } else {
            $debug_info['source'] = 'Default';
            $y = date('Y');
            $m = date('m');
            $d = 0;
            $t = 7;
        }

        // デバッグ情報を格納します。
        $debug_info['params'] = [
            'inst_id' => $inst,
            'status' => $status,
            'year' => $y,
            'month' => $m,
            'day' => $d,
            'timespan' => $t,
        ];

        // 一覧の表示順序を制御するために、ソート条件を設定します。
        $sort_map = [
            'code' => 'r.code',
            'room_no' => 'i.room_no',
            'shortname' => 'i.shortname',
            'reserved' => 'r.reserved',
            'stime' => 'r.stime',
            'status' => 'r.process_status'
        ];

        if (isset($sort_map[$sort_col])) {
            $_SESSION['rsv_list_sort_col'] = $sort_col;
            $_SESSION['rsv_list_sort_dir'] = ($sort_dir === 'asc' || $sort_dir === 'desc') ? $sort_dir : 'asc';
        }

        $current_sort_col = $_SESSION['rsv_list_sort_col'] ?? 'stime';
        $current_sort_dir = $_SESSION['rsv_list_sort_dir'] ?? 'desc';

        $sort_order = $sort_map[$current_sort_col] . ' ' . $current_sort_dir;

        $debug_info['sort'] = [
            'current_col' => $current_sort_col,
            'current_dir' => $current_sort_dir,
            'order_by' => $sort_order,
        ];

        // Modelに日付範囲の計算を委譲します。
        list($date1, $date2) = $reserve_model->calculateDateRange($y, $m, $d, $t);

        // モデルから予約データを取得します。
        $num_rows = $reserve_model->getNumRows($inst, $date1, $date2, $status);
        $rows_raw = $reserve_model->getListByInst($inst, $date1, $date2, $status, $page, $sort_order);
        
        // Viewで表示しやすいようにデータを加工します。
        $rsv_status_map = KsuCode::RSV_STATUS;
        $rows = [];
        foreach ($rows_raw as $row) {
            $status_id = $row['process_status'] ?? 0;
            $row['status_name'] = $rsv_status_map[$status_id] ?? '';
            $row['is_pending'] = ($row['status_name'] == '申請中');
            $row['grant_label'] = ($status_id == 1 || $status_id == 3) ? '承認' : '却下';
            $rows[] = $row;
        }

        // Viewで使うプルダウンの選択肢を準備します。
        $status_options_for_select = KsuCode::RSV_STATUS;
        $status_options_for_select[0] = '全て';
        ksort($status_options_for_select);

        $instrument_options = (new Instrument)->getList();
        $is_admin = $this->user->isAdmin();

        // ビューにデータを渡してレンダリングします。
        $this->view->render('rsv_list.php', [
            'rows' => $rows,
            'num_rows' => $num_rows,
            'inst_selected' => $inst,
            'rsv_purpose_map' => KsuCode::RSV_PURPOSE, // 利用目的の名称解決用に渡す
            'selected_y' => $y,
            'selected_m' => $m,
            'selected_d' => $d,
            'selected_t' => $t,
            'status' => $status,
            'instrument_options' => $instrument_options,
            'status_options' => $status_options_for_select,
            'is_admin' => $is_admin,
            'page_rows' => KsuCode::PAGE_ROWS,
            'page' => $page,
            'sort_col' => $current_sort_col,
            'sort_dir' => $current_sort_dir,
            'debug_info' => $debug_info,
        ]);
    }
    /**
     * 特定の予約に関する詳細な構成情報を表示します。
     */
    public function detailAction($id = 0, $page = 1)
    {
        // 権限チェックとして、ログインしているか確認します。
        (new Security)->require('login');

        $rsv_id = (int)$id;
        if ($rsv_id === 0) {
            // 予約IDが指定されていない場合は、予約一覧にリダイレクトします。
            $this->redirect('index.php?to=rsv&do=list');
            return;
        }

        // モデルから予約詳細データを取得します。
        $rsv = $this->model->getDetail($rsv_id);

        if (!$rsv) {
            // 予約が見つからない場合はエラーメッセージを設定します。
            $this->view->assign('error_message', '指定された予約情報は見つかりませんでした。');
            return;
        }

        // 画面表示に必要な権限情報やラベル情報を準備します。
        $is_admin = $this->user->isAdmin();
        $is_owner = $this->user->isOwner($rsv['apply_mid']); // 予約の申請者本人かどうかを判定します。

        // 承認状態に応じて、承認/却下ボタンのラベルを決定します。
        $status = $rsv['process_status'];
        $status_label = ($status == 1 || $status == 3) ? '承認' : '却下';

        // 承認状態に応じたCSSクラスをマッピングします。
        $status_class_map = [
            1 => 'text-info',    // 申請中
            2 => 'text-success', // 承認済
            3 => 'text-danger',  // 却下済
            4 => 'text-muted',   // キャンセル済
        ];
        $status_class = $status_class_map[$status] ?? 'text-dark';

        // ビューにデータを渡します。
        $this->view->assign('rsv', $rsv);
        $this->view->assign('is_admin', $is_admin);
        $this->view->assign('is_owner', $is_owner);
        $this->view->assign('status_label', $status_label);
        $this->view->assign('status_class', $status_class);
        $this->view->assign('page', (int)$page); // 一覧に戻る際のページ番号を渡します。

        // ビューをレンダリングします。
        $this->view->render('rsv_detail.php');
    }

    /**
     * 予約の新規登録または編集を行うための入力画面を表示します。
     */
    public function inputAction($id = 0, $inst = null, $d = null, $copy = 0, $rsv_data = null)
    {
        // 権限チェックとして、ログインと予約権限を確認します。
        (new Security)->require('login');
        (new Security)->require('reserve');

        // 予約IDやコピーフラグを取得して、データを準備します。
        $rsv_id = (int)$id;
        $is_copy = ($copy == 1);

        // 既存の予約内容を再利用するために、元のデータを取得して新規データへコピーします。
        if ($is_copy && $rsv_id > 0) {
            $source_rsv = $this->model->getDetail($rsv_id);
            $rsv = $this->model->getDetail(0); // 新規作成用のテンプレートを取得

            // コピー元のデータから必要なフィールドを新しい予約データにコピーします。
            $fields_to_copy = [
                'instrument_id',
                'purpose',
                'master_mid',
                'rsv_member',
                'other_num',
                'other_user',
                'sample_name',
                'sample_state',
                'sample_nature',
                'sample_other',
                'xray_chk',
                'memo'
            ];
            foreach ($fields_to_copy as $field) {
                if (isset($source_rsv[$field])) {
                    $rsv[$field] = $source_rsv[$field];
                }
            }
            $rsv_id = 0; // コピーして新規作成するため、IDは0にリセットします。
        } else {
            $rsv = $this->model->getDetail($rsv_id);
        }

        // エラーなどで入力データが渡された場合は、DB等のデータを上書きして入力内容を復元します。
        if ($rsv_data) {
            $rsv = array_merge($rsv, $rsv_data);
        }

        // URLパラメータで機器IDが指定されている場合、予約データに反映します。
        if ($inst !== null) {
            $rsv['instrument_id'] = (int)$inst;
        }

        $instrument = null;
        // 機器IDが設定されている場合、機器詳細を取得します。
        if (!empty($rsv['instrument_id'])) {
            $instrument = (new Instrument)->getDetail($rsv['instrument_id']);
        }
        // 機器名を設定します。機器情報が取得できなかった場合は空文字を設定します。
        $rsv['instrument_name'] = $instrument['fullname'] ?? '';

        // 予約の期間を特定するために、開始日時と終了日時の初期値を設定します。
        $stime = date('Y-m-d H:00');
        if ($d !== null) {
            $ymd = DateTime::createFromFormat('ymd', $d);
            if ($ymd) {
                $stime = $ymd->format('Y-m-d H:00');
            }
        }

        // 新規作成またはコピーの場合は、現在時刻を初期値として設定します。
        if ($rsv_id == 0) {
            $rsv['stime'] = $stime;
            $rsv['etime'] = $stime;
        }

        // ビューに渡すためのデータを準備します。
        $staffs = (new Staff)->getOptions('responsible');
        $master_sid = isset($rsv['master_member']['sid']) ? $rsv['master_member']['sid'] : '';

        // 予約済みの時間帯を取得して、クライアントサイドのバリデーションで使用します。
        $occupied_periods = []; // TODO: 予約済みの時間帯を取得するロジックを実装します。

        // ビューにデータを渡してレンダリングします。
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
     * 入力された予約情報を検証し、データベースへの保存または更新を行います。
     */
    public function saveAction()
    {
        // 権限チェックとして、ログインと予約権限を確認します。
        (new Security)->require('login');
        (new Security)->require('reserve');

        // POSTされたデータを取得し、予約IDを初期化します。
        $data = $_POST;
        $rsv_id = (int)($data['id'] ?? 0);

        // 保存対象となる予約データのフィールドを定義します。
        $rsv_fields = [
            'id' => 0,
            'code' => '',
            'instrument_id' => 0,
            'apply_mid' => 0,
            'master_mid' => 0,
            'process_status' => 1,
            'purpose_id' => 0,
            'purpose' => '',
            'other_num' => 0,
            'other_user' => '',
            'stime' => '',
            'etime' => '',
            'sample_name' => '',
            'sample_state' => 1,
            'xray_chk' => 0,
            'xray_num' => '',
            'memo' => '',
        ];

        // POSTデータから予約データを作成します。未定義のキーにはデフォルト値を設定します。
        $rsv = [];
        foreach ($rsv_fields as $key => $default) {
            $rsv[$key] = $data[$key] ?? $default;
        }
        $rsv['id'] = $rsv_id;

        // 入力内容の整合性を保つために、各種バリデーションを実行します。
        $errors = [];

        // 予約時間の重複をチェックします。
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

        // 開始時刻と終了時刻の順序が正しいかチェックします。
        if (strtotime($rsv['stime']) >= strtotime($rsv['etime'])) {
            $errors[] = "無効な時間帯です。終了時刻は開始時刻より後に設定してください。";
        }

        // 予約期間が長すぎないかチェックします（例: 1週間以内）。
        $diff_days = (strtotime($rsv['etime']) - strtotime($rsv['stime'])) / (60 * 60 * 24);
        if ($diff_days >= 7) {
            $errors[] = "予約期間は1週間までです";
        }

        // 利用責任者が選択されているか、また有効なメンバーかチェックします。
        $member_model = new Member();
        if (empty($data['master_sid'])) {
            $errors[] = "利用責任者を選択してください";
        } else {
            $master_member = $member_model->getDetailBySid($data['master_sid']);
            if ($master_member) {
                $rsv['master_mid'] = $master_member['uid'];
            } else {
                $errors[] = "指定された利用責任者は無効です。";
            }
        }

        // ログイン中のユーザーを申請者として設定します。
        $rsv['apply_mid'] = $this->user->getLoginUid();
        if (empty($rsv['apply_mid'])) {
            $errors[] = "申請者の情報が取得できませんでした。再度ログインしてください。";
        } else {
            // 申請者が会員テーブルに存在するかチェックします。
            if (!$member_model->getDetailByUid($rsv['apply_mid'])) {
                $errors[] = "申請者の会員情報が見つかりません。";
            }
        }

        // 利用代表者が1名以上指定されており、それぞれが有効なメンバーかチェックします。
        $rsv_members = [];
        $check_dedupe = [];
        if (isset($data['rsv_member']) && is_array($data['rsv_member'])) {
            foreach ($data['rsv_member'] as $sid) {
                if (empty(trim($sid))) continue;
                $member = $member_model->getDetailBySid(trim($sid));
                if ($member) {
                    if (!in_array($member['id'], $check_dedupe)) {
                        $rsv_members[] = $member;
                        $check_dedupe[] = $member['id'];
                    }
                } else {
                    $errors[] = sprintf("'%s'：無効な利用代表者IDです", htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'));
                }
            }
        }
        if (empty($rsv_members)) {
            $errors[] = "有効な利用代表者を1名以上指定してください";
        }

        // バリデーションエラーがあった場合、エラーメッセージと共に再度入力フォームを表示します。
        if (count($errors) > 0) {
            $this->view->assign('errors', $errors);
            $rsv_merged = array_merge($rsv, $data);
            // ビューが期待する形式（配列の配列）に利用代表者データを変換します
            if (isset($data['rsv_member']) && is_array($data['rsv_member'])) {
                $rsv_merged['rsv_member'] = array_map(function($sid) { return ['sid' => $sid]; }, $data['rsv_member']);
            }

            $this->inputAction($rsv_id, $rsv['instrument_id'], null, 0, $rsv_merged);
            return;
        }

        // バリデーションを通過したデータを、データベースの予約テーブルへ永続化します。
        if ($rsv_id == 0) { // 新規作成の場合は、申請番号を採番し、申請日時を記録します。
            $rsv['code'] = $this->model->nextCode();
            $rsv['reserved'] = date('Y-m-d H:i:s');
        }
        $new_rsv_id = $this->model->write($rsv);
        $rsv_id = ($rsv_id == 0) ? $new_rsv_id : $rsv_id;

        // 関連テーブル（利用代表者）のデータを保存します。
        (new RsvMember)->reset($rsv_id);
        foreach ($rsv_members as $member) {
            (new RsvMember)->write(['id' => 0, 'reserve_id' => $rsv_id, 'member_id' => $member['id']]);
        }
        // 関連テーブル（試料情報）のデータを保存します。
        (new RsvSample)->reset($rsv_id);
        if (isset($data['rsv_sample']) && is_array($data['rsv_sample'])) {
            $samples = array_unique($data['rsv_sample']);
            foreach ($samples as $val) {
                $other = ($val == 4 && isset($data['sample_other'])) ? $data['sample_other'] : '';
                (new RsvSample)->write(['id' => 0, 'reserve_id' => $rsv_id, 'nature' => $val, 'other' => $other]);
            }
        }

        // 保存完了後、予約詳細ページにリダイレクトします。
        $this->redirect('index.php?to=rsv&do=detail&id=' . $rsv_id);
    }

    /**
     * 管理者権限により、予約の承認ステータスを更新します。
     */
    public function grantAction($id = null)
    {
        // 権限チェックとして、管理者であるか確認します。
        (new Security)->require('admin');

        $rsv_id = (int)$id;

        // 予約ステータスを切り替えます。(申請中/却下済 -> 承認済, 承認済 -> 却下済)
        $rsv = $this->model->getDetail($rsv_id);
        $new_status = ($rsv['process_status'] == 1 || $rsv['process_status'] == 3) ? 2 : 3; // ステータスを承認済（2）または却下済（3）に切り替えます。

        // 更新するデータを準備します。
        $data = [
            'id' => $rsv_id,
            'process_status' => $new_status
        ];
        // ステータスが「承認済」になる場合のみ、承認日時を更新します。
        if ($new_status == 2) {
            $data['approved'] = date('Y-m-d H:i:s');
        }
        $this->model->write($data);

        // 処理完了後、元のページ（予約一覧や詳細ページ）にリダイレクトします。
        $this->redirect($_SERVER['HTTP_REFERER'] ?? 'index.php?to=rsv&do=list');
    }

    /**
     * 指定された予約データおよび関連情報をシステムから削除します。
     */
    public function deleteAction($id = null)
    {
        // 権限チェックとして、ログインしているか確認します。
        (new Security)->require('login');

        $rsv_id = (int)$id;
        $rsv = $this->model->getDetail($rsv_id);

        // 管理者または予約の申請者本人でなければ、操作を許可しません。
        if (!$this->user->isAdmin() && !$this->user->isOwner($rsv['apply_mid'])) {
            (new Security)->require('owner', $rsv['apply_mid']);
        }

        // 予約データおよび関連する共同利用者・試料情報をデータベースから物理削除します。
        (new RsvMember)->reset($rsv_id);
        (new RsvSample)->reset($rsv_id);
        $this->model->delete($rsv_id);

        // 削除完了後、予約一覧ページへリダイレクトします。
        $this->redirect('index.php?to=rsv&do=list');
    }

    /**
     * 指定された期間内の予約状況を集計し、レポートとして表示します。
     */
    public function reportAction()
    {
        // ログイン状態を確認し、権限のないユーザーのアクセスを制限します。
        (new Security)->require('login');

        // 処理の経過を確認するためのデバッグ情報用変数を初期化します。
        $debug_info = [];

        // POSTリクエストがあればそれを優先し、なければセッションから検索条件を取得します。
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $debug_info['source'] = 'POST';
            $inst_id = $_POST['id'] ?? 0;
            $status = $_POST['status'] ?? 0;
            $y = $_POST['y'] ?? date('Y');
            $m = $_POST['m'] ?? date('m');
            $d = $_POST['d'] ?? 0;
            $t = $_POST['t'] ?? 7;
            $_SESSION['selected_inst'] = $inst_id;
            $_SESSION['selected_status'] = $status;
            $_SESSION['selected_year'] = $y;
            $_SESSION['selected_month'] = $m;
            $_SESSION['selected_day'] = $d;
            $_SESSION['selected_timespan'] = $t;
        } else {
            $debug_info['source'] = 'Session';
            $inst_id = $_SESSION['selected_inst'] ?? 0;
            $status = $_SESSION['selected_status'] ?? 0;
            $y = $_SESSION['selected_year'] ?? date('Y');
            $m = $_SESSION['selected_month'] ?? date('m');
            $d = $_SESSION['selected_day'] ?? 0;
            $t = $_SESSION['selected_timespan'] ?? 7;
        }

        // 取得した検索パラメータをデバッグ情報に格納します。
        $debug_info['params'] = [
            'inst_id' => $inst_id,
            'status' => $status,
            'year' => $y,
            'month' => $m,
            'day' => $d,
            'timespan' => $t,
        ];

        // 指定された条件に基づき、レポートの集計対象となる期間を計算します。
        $day = $d > 0 ? $d : 1;
        $date = new \DateTimeImmutable($y . '-' . $m . '-' . $day);
        $def = [1 => 'P1D', 7 => 'P1W', 30 => 'P1M'];
        $period = new \DateInterval($def[$t] ?? 'P1W'); // 不正な値の場合は1週間にフォールバック
        $date1 = $date->format('Y-m-d 00:00:00');
        $date2 = $date->add($period)->format('Y-m-d 00:00:00');

        // 計算した期間と条件を用いて、モデルから集計データを取得します。
        $data = $this->model->getReport($inst_id, $date1, $date2, $status);

        // 集計結果をビューに渡し、レポート画面をレンダリングします。
        $this->view->render('rsv_report.php', [
            'report_data' => $data['report'],
            'date1' => $date1,
            'date2' => $date2,
            'debug_info' => $debug_info,
        ]);
    }
}
