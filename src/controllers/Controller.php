<?php
namespace aic\controllers;

use aic\models\Model;
use aic\views\View;

class Controller
{
    protected $model;
    protected $view;

    public function __construct(Model $model, View $view)
    {
        $this->model = $model;
        $this->view = $view;
    }
}