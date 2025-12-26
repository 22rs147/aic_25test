<?php
namespace aic\controllers;

use aic\models\Model;
use aic\views\View;
use aic\models\User;

/**
 * アプリケーション全体の共通処理を集約し、各コントローラーの基盤を提供します。
 */
abstract class Controller
{
    protected $model;
    protected $view;
    protected $user;

    public function __construct(Model $model, View $view)
    {
        $this->model = $model;
        $this->view = $view;
        $this->user = new User();
    }

    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit();
    }
}