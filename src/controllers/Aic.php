<?php
namespace aic\controllers;

use aic\models\Instrument;
use aic\models\Reserve;
use DateTime;
use aic\models\Util;

class Aic extends Controller
{
    /**
     * 全ての機器の予約状況タイムラインを表示します。
     */
    public function listAction($d = null, $id = null)
    {
        // 日付入力を処理します。無効な場合や指定がない場合は今日の日付を使用します。
        $ymd = $d ?? date("ymd");
        $inst_id_filter = $id ? (int)$id : 0;
        try {
            $start_date = DateTime::createFromFormat('!ymd', $ymd);
            if ($start_date === false) {
                throw new \Exception('無効な日付形式です。');
            }
        } catch (\Exception $e) {
            $start_date = new DateTime();
            $ymd = $start_date->format('ymd');
        }
        $start_date->setTime(0, 0, 0);

        // タイムラインに表示する時間軸の範囲を定義します。
        $timeline_start_str = $start_date->format('Y-m-d 08:00:00');
        $timeline_end_str = $start_date->format('Y-m-d 23:59:59');

        // 予約状況と機器情報を取得するために、それぞれのモデルをインスタンス化します。
        $reserve_model = new Reserve();
        $instrument_model = new Instrument();

        $items = $reserve_model->getItems($inst_id_filter, $timeline_start_str, $timeline_end_str);

        // タイムラインの各行（機器リスト）を作成し、詳細ページへのリンクを設定します。
        $groups = [];
        if ($inst_id_filter > 0) {
            // フィルタが指定されている場合は、該当する機器のみをグループに追加します。
            $instrument = $instrument_model->getDetail($inst_id_filter);
            if ($instrument) {
                $link = sprintf(
                    '<a class="btn btn-info" href="?to=aic&do=detail&id=%d&d=%s">%s</a>',
                    $instrument['id'],
                    $ymd,
                    htmlspecialchars($instrument['fullname'], ENT_QUOTES, 'UTF-8')
                );
                $groups[] = ['id' => $instrument['id'], 'content' => $link];
            }
        } else {
            // フィルタがない場合は、登録されている全ての機器を表示対象とします。
            $instruments = $instrument_model->getList();
            foreach ($instruments as $instrument) {
                $link = sprintf(
                    '<a class="btn btn-info" href="?to=aic&do=detail&id=%d&d=%s">%s</a>',
                    $instrument['id'],
                    $ymd,
                    htmlspecialchars($instrument['fullname'], ENT_QUOTES, 'UTF-8')
                );
                $groups[] = ['id' => $instrument['id'], 'content' => $link];
            }
        }

        // 日付移動用のナビゲーションリンクを作成します。
        $navbar_defs = ['-7' => '1週間前', '-1' => '前の日', '+1' => '次の日', '+7' => '1週間後'];
        $navbar_links = [];
        foreach ($navbar_defs as $delta => $label) {
            $nav_date = clone $start_date;
            $nav_date->modify($delta . ' days');
            $url = '?to=aic&do=list&d=' . $nav_date->format('ymd');
            if ($inst_id_filter > 0) {
                $url .= '&id=' . $inst_id_filter;
            }
            $navbar_links[] = ['url' => $url, 'label' => $label];
        }

        // 準備した全てのデータをビューに渡してレンダリングします。
        $this->view->render('aic_list.php', [
            'items_json' => json_encode($items),
            'groups_json' => json_encode($groups),
            'nav_links' => $navbar_links,
            'timeline_start' => $timeline_start_str,
            'timeline_end' => $timeline_end_str,
        ]);
    }

    /**
     * 特定の機器の予約状況タイムライン（7日間）を表示します。
     */
    public function detailAction($id = 0, $d = null)
    {
        $inst_id = (int)$id;
        // 機器IDが不正な場合は、安全のために機器一覧画面へリダイレクトします。
        if ($inst_id === 0) {
            $this->redirect('?to=inst&do=list');
            return;
        }

        // 表示の基準となる日付を決定し、適切なDateTimeオブジェクトを作成します。
        $date_curr = date("ymd");
        $selected_ymd = $d ?? $date_curr;
        
        // パラメータから開始日を生成し、不正な場合は現在日時をデフォルトとして使用します。
        try {
            $_start = DateTime::createFromFormat('!ymd', $selected_ymd);
            if (!$_start) throw new \Exception();
        } catch (\Exception $e) {
            $_start = new DateTime();
        }
        $_start->setTime(0, 0, 0);

        // タイムラインに表示する1週間分のデータを取得するため、検索期間を設定します。
        $date_start_str = $_start->format('Y-m-d 00:00:00');
        $date_end_obj   = (clone $_start)->modify('+6 days'); // +7 daysだと8日分になる可能性があるため調整
        $date_end_str   = $date_end_obj->format('Y-m-d 23:59:59');

        // 機器の詳細情報と、その機器に関連する予約データを取得します。
        $instrument_model = new Instrument();
        $instrument = $instrument_model->getDetail($inst_id);

        if (!$instrument) {
            $this->view->render('aic_detail.php', ['error' => '指定された機器は存在しません。']);
            return;
        }

        $reserve_model = new Reserve();
        // 指定された期間内に含まれる予約データをデータベースから取得します。
        $reservations = $reserve_model->getListByInst($inst_id, $date_start_str, $date_end_str);
        
        // 異なる日付の予約を同一の時間軸上に並べるため、日付部分をダミーデータに置き換えて正規化します。
        
        $dummy_date = '2000-01-01'; 
        $timeline_view_start = $dummy_date . ' 08:00:00';
        $timeline_view_end   = $dummy_date . ' 23:59:59';

        // データベースから取得した生の予約データを、タイムライン表示用の形式に変換します。
        $raw_items = Reserve::toItemsByDate($reservations);
        $normalized_items = [];

        foreach ($raw_items as $item) {
            // データベースの開始日時をDateTimeオブジェクトとして扱います。
            $real_start = new DateTime($item['start']);
            $real_end   = new DateTime($item['end']);
            
            // タイムラインの行を識別するため、実際の日付をグループIDとして設定します。
            $group_id = $real_start->format('Y-m-d');

            // グラフ上の位置を揃えるため、時刻情報のみを維持して日付を統一します。
            $view_start = $dummy_date . ' ' . $real_start->format('H:i:s');
            $view_end   = $dummy_date . ' ' . $real_end->format('H:i:s');

            $normalized_items[] = [
                'id'      => $item['id'],
                'group'   => $group_id,        // どの行（日付）に表示するか
                'content' => $item['content'], // 表示文字
                'start'   => $view_start,      // 表示上の開始時間
                'end'     => $view_end,        // 表示上の終了時間
                'className' => $item['className'] ?? '',
                'title'   => $item['title'] ?? '' 
            ];
        }

        // タイムラインの縦軸となる7日分の日付行データを生成します。
        $groups = [];
        $current = clone $_start;
        // 開始日から1週間分の日付行を順番に作成します。
        for ($i = 0; $i < 7; $i++) {
            $date_str = $current->format('Y-m-d');
            $ymd      = $current->format('ymd');
            
            // ユーザーが直感的に理解できるよう、日付を日本語形式に変換します。
            $label = Util::jpdate($date_str);
            
            // 予約入力画面へ遷移するためのボタンHTMLを組み立てます。
            $link_html = sprintf(
                '<a class="btn btn-info" href="?to=rsv&do=input&inst=%d&d=%s">%s予約する</a>',
                $inst_id,
                $ymd,
                $label
            );
            
            $groups[] = ['id' => $date_str, 'content' => $link_html];
            
            $current->modify('+1 day');
        }

        // 前後の期間へ移動するためのナビゲーションリンクを生成します。
        $nav_defs = ['-7' => '1週間前', '+7' => '1週間後'];
        $nav_links = [];
        foreach ($nav_defs as $delta => $label) {
            $nav_date = clone $_start;
            $nav_date->modify($delta . ' days');
            $nav_links[] = [
                'url' => '?to=aic&do=detail&id=' . $inst_id . '&d=' . $nav_date->format('ymd'),
                'label' => $label
            ];
        }

        // 機器の画像を表示するために、ファイルの存在確認を行い適切なパスを設定します。
        $image_path = 'img/instrument/' . $inst_id . '.webp';
        $image_url = (file_exists($image_path) && @getimagesize($image_path))
            ? $image_path
            : 'img/dummy-image-square1.webp';

        // 準備した全てのデータをビューに渡してレンダリングします。
        $this->view->render('aic_detail.php', [
            'instrument'     => $instrument,
            'image_url'      => $image_url,
            'nav_links'      => $nav_links,
            'display_start'  => $date_start_str,
            'display_end'    => $date_end_str,
            'items_json'     => json_encode($normalized_items), 
            'groups_json'    => json_encode($groups),
            'timeline_start' => $timeline_view_start, 
            'timeline_end'   => $timeline_view_end,     
            'inst_id'        => $inst_id,
        ]);
    }
}
