<?php
namespace aic\controllers;

use aic\models\KsuCode;

class Instrument extends Controller
{
    public function listAction($c = null): void
    {
        $selected = 0;
        $where = '1';
        $orderby = 'room_id';

        // URLパラメータからカテゴリIDを取得し、一覧の絞り込み条件を構築します。
        if ($c !== null) {
            $where = 'category=' . intval($c);
            $selected = intval($c);
        }

        // 機器情報に加えて部屋情報を結合して取得するために、モデルのgetListメソッドを実行します。
        $rows = $this->model->getList($where, $orderby);
        
        // 画面のプルダウンメニューや予約可否の判定に使用するデータを準備します。
        $categories = KsuCode::INST_CATEGORY;
        $can_reserve = $this->user->canReserve();

        // 準備した機器リストとカテゴリ情報をビューに渡し、一覧画面をレンダリングします。
        $this->view->render('inst_list.php', [
            'rows' => $rows,
            'categories' => $categories,
            'selected' => $selected,
            'can_reserve' => $can_reserve,
        ]);
    }

    public function detailAction($id = 0)
    {
        $id = (int)$id;

        // 特定の機器に関する詳細な情報を表示するために、データベースからデータを取得します。
        $instrument = $this->model->getDetail($id);

        if ($instrument) {
            // ユーザーが機器の外観を確認できるように、画像ファイルの存在をチェックして適切なパスを設定します。
            $image_path = 'img/instrument/' . $instrument['id'] . '.webp';
            $image_url = (file_exists($image_path) && @getimagesize($image_path))
                ? $image_path
                : 'img/dummy-image-square1.webp';

            $data = [
                'row' => $instrument,
                'image_url' => $image_url,
                'is_admin' => $this->user->isAdmin(),
                'can_reserve' => $this->user->canReserve(),
            ];
        } else {
            $data['error'] = '指定された機器は存在しません。';
        }

        // 取得した詳細データと画像情報をビューに渡し、詳細画面を表示します。
        $this->view->render('inst_detail.php', $data);
    }
}
