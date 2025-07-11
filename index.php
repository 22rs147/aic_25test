<?php
session_start();
date_default_timezone_set("Asia/Tokyo");

require __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . '/conf/develop_env.php';

// --- 1. アプリケーション設定 ---
$config = [
    'view_dir' => __DIR__ . '/src/views/',
    'db' => $conf, // conf/develop_env.php から
    'routes' => [
        // 'to' => ['c' => Controller/Model名, 'a' => 許可アクション]
        'user' => ['c' => 'User',       'a' => ['login', 'auth', 'logout', 'upass']],
        'inst' => ['c' => 'Instrument', 'a' => ['list', 'detail', 'input', 'save', 'delete']],
        'mbr'  => ['c' => 'Member',     'a' => ['list', 'detail', 'input', 'save', 'delete']],
        'rsv'  => ['c' => 'Reserve',    'a' => ['list', 'detail', 'input', 'save', 'delete']],
        'aic'  => ['c' => 'Aic',        'a' => ['list', 'detail', 'input', 'save', 'delete']],
    ],
    'default_route' => ['to' => 'inst', 'do' => 'list'],
];

// --- 2. 実行とエラーハンドリング ---
try {
    // --- 3. ルーティング ---
    $to = $_GET['to'] ?? $config['default_route']['to'];
    $do = $_GET['do'] ?? $config['default_route']['do'];
    $params = $_GET;
    unset($params['to'], $params['do']);

    // ルート定義からクラスとアクションを決定
    $routeInfo = $config['routes'][$to] ?? $config['routes'][$config['default_route']['to']];
    $mvcClass = $routeInfo['c'];
    $actionMethod = (in_array($do, $routeInfo['a'], true)) ? $do . 'Action' : 'listAction';

    // --- 4. MVCコンポーネントの準備 ---

    // Model
    aic\models\Model::setConnInfo($config['db']);
    $modelClass = "aic\\models\\" . $mvcClass;
    if (!class_exists($modelClass)) {
        $modelClass = "aic\\models\\Model";
    }
    $model = new $modelClass();

    // View
    $view = new aic\views\View($config['view_dir']);

    // Controller
    $controllerClass = "aic\\controllers\\" . $mvcClass;
    if (!class_exists($controllerClass)) {
        throw new \RuntimeException("Controller class '{$controllerClass}' not found.");
    }
    $controller = new $controllerClass($model, $view);

    // --- 5. アクションの実行 ---
    if (!is_callable([$controller, $actionMethod])) {
        throw new \RuntimeException("Action '{$actionMethod}' not found in controller '{$controllerClass}'.");
    }

    call_user_func_array([$controller, $actionMethod], $params);

} catch (\Throwable $e) {
    // --- 6. 例外処理 ---
    // 開発環境では詳細なエラーを表示し、本番環境では一般的なエラーメッセージを表示
    if (defined('DEVELOP_ENV') && DEVELOP_ENV) {
        header('HTTP/1.1 500 Internal Server Error');
        echo "<h1>Application Error</h1>";
        echo "<pre>";
        echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "\n\n";
        echo "<strong>File:</strong> " . $e->getFile() . " on line " . $e->getLine() . "\n\n";
        echo "<strong>Stack Trace:</strong>\n" . $e->getTraceAsString();
        echo "</pre>";
    } else {
        // // 本番環境ではエラーをログに記録し、汎用的なエラーページを表示することを推奨
        // // error_log($e->getMessage() . "\n" . $e->getTraceAsString());
        // header('HTTP/1.1 500 Internal Server Error');
        // // require 'error_page.html';
        // echo "<h1>An error occurred</h1><p>We are sorry, but something went wrong. Please try again later.</p>";
    }
    exit;
}