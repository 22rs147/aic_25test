<?php
namespace aic\controllers;

use aic\models\Instrument;
use aic\models\Reserve;
use DateTime;
use aic\models\User;
use aic\models\Util;

class Aic extends Controller
{
    /**
     * 全ての機器の予約状況タイムラインを表示します。
     */
    public function listAction($d = null, $id = null)
    {
        // 1. 日付入力を処理します。無効な場合や指定がない場合は今日の日付を使用します。
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

        // 表示するタイムラインの時間範囲を定義します。
        $timeline_start_str = $start_date->format('Y-m-d 08:00:00');
        $timeline_end_str = $start_date->format('Y-m-d 23:59:59');

        // 2. モデルからデータを取得します。
        $reserve_model = new Reserve();
        $instrument_model = new Instrument();

        $items = $reserve_model->getItems($inst_id_filter, $timeline_start_str, $timeline_end_str);

        // 3. ビューに渡すデータを準備します。
        // タイムラインのグループ（機器リスト）を作成し、詳細ページへのリンクを設定します。
        $groups = [];
        if ($inst_id_filter > 0) {
            // 特定の機器のみ表示
            $instrument = $instrument_model->getDetail($inst_id_filter);
            if ($instrument) {
                $link = sprintf(
                    '<a class="btn btn-info" href="?to=inst&do=detail&id=%d">%s</a>',
                    $instrument['id'],
                    htmlspecialchars($instrument['fullname'], ENT_QUOTES, 'UTF-8')
                );
                $groups[] = ['id' => $instrument['id'], 'content' => $link];
            }
        } else {
            // 全ての機器を表示
            $instruments = $instrument_model->getList();
            foreach ($instruments as $instrument) {
                $link = sprintf(
                    '<a class="btn btn-info" href="?to=inst&do=detail&id=%d">%s</a>',
                    $instrument['id'],
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

        // 4. 準備した全てのデータをビューに渡してレンダリングします。
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
        if ($inst_id === 0) {
            $this->view->redirect('index.php?to=inst&do=list');
            return;
        }

        // 1. 日付の処理
        $date_curr = date("ymd");
        $selected_ymd = $d ?? $date_curr;
        $_start = \DateTime::createFromFormat('!ymd', $selected_ymd) ?: new \DateTime();
        $_start->setTime(0, 0, 0);

        $date_start = $_start->format('Y-m-d 00:00:00');
        $date_end = (clone $_start)->modify('+6 days')->format('Y-m-d 23:59:59');

        // 2. モデルからデータを取得
        $instrument_model = new Instrument();
        $instrument = $instrument_model->getDetail($inst_id);

        if (!$instrument) {
            $this->view->render('aic_detail.php', ['error' => '指定された機器は存在しません。']);
            return;
        }

        $reserve_model = new Reserve();
        $reservations = $reserve_model->getListByInst($inst_id, $date_start, $date_end);
        $user_model = new User();

        // 3. ビューに渡すデータを準備
        // 画像URL
        $image_path = 'img/instrument/' . $inst_id . '.webp';
        $image_url = (file_exists($image_path) && @getimagesize($image_path))
            ? $image_path
            : 'img/dummy-image-square1.webp';

        // ナビゲーションリンク
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

        // タイムラインのグループ (日付ごと)
        $groups = [];
        $current_date_obj = clone $_start;
        for ($i = 0; $i < 7; $i++) {
            $date_str = $current_date_obj->format('Y-m-d');
            $ymd = $current_date_obj->format('ymd');
            $link = sprintf('<a class="btn btn-info" href="?to=rsv&do=input&inst=%d&d=%s">%s予約する</a>', $inst_id, $ymd, Util::jpdate($date_str));
            $groups[] = ['id' => $date_str, 'content' => $link];
            $current_date_obj->modify('+1 day');
        }

        // 4. 準備した全てのデータをビューに渡してレンダリング
        $this->view->render('aic_detail.php', [
            'instrument'     => $instrument,
            'image_url'      => $image_url,
            'can_reserve'    => $user_model->canReserve(),
            'nav_links'      => $nav_links,
            'items_json'     => json_encode(Reserve::toItemsByDate($reservations)),
            'groups_json'    => json_encode($groups),
            'timeline_start' => $_start->format('Y-m-d 08:00:00'),
            'timeline_end'   => (clone $_start)->modify('+0 days')->format('Y-m-d 23:59:59'),
            'inst_id'        => $inst_id,
        ]);
    }
}
