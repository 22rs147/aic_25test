<?php
namespace aic\controllers;

use aic\models\Room;
use aic\models\User;
use aic\models\KsuCode;

class Instrument extends Controller
{
    public function listAction($c = null): void
    {
        $selected = 0;
        $where = '1';
        $orderby = 'room_id';

        if ($c !== null) {
            $where = 'category=' . intval($c);
            $selected = intval($c);
        }

        // モデルからデータを取得 (JOINにより部屋情報も含まれる)
        $rows = $this->model->getList($where, $orderby);
        
        // ビューで使用するデータを準備
        $categories = KsuCode::INST_CATEGORY;
        $user_model = new User();
        $can_reserve = $user_model->canReserve();

        // ビューに渡す
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

        // Get data from the model
        $instrument = $this->model->getDetail($id);

        if ($instrument) {
            // Check for image, using file_exists and getimagesize for reliability
            $image_path = 'img/instrument/' . $instrument['id'] . '.webp';
            $image_url = (file_exists($image_path) && @getimagesize($image_path))
                ? $image_path
                : 'img/dummy-image-square1.webp';

            // Check user permissions
            $user = new User();

            $data = [
                'row' => $instrument,
                'image_url' => $image_url,
                'is_admin' => $user->isAdmin(),
                'can_reserve' => $user->canReserve(),
            ];
        } else {
            $data['error'] = '指定された機器は存在しません。';
        }

        // Render the view, passing all prepared data
        $this->view->render('inst_detail.php', $data);
    }
}
