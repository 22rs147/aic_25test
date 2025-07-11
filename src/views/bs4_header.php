<?php
$app_name = '総合機器センター機器設備予約システム';
?>
<!DOCTYPE html>
<html lang="jp">
<head>
 <title><?=$app_name?></title>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!-- Meta, title, CSS, favicons, etc. -->
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<!-- Bootstrap -->
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">

<link rel="stylesheet" type="text/css" href="./css/style.css">
<link rel="stylesheet" href="./css/vis-timeline-graph2d.min.css">

<!-- Use the full version of jQuery for better compatibility with plugins -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
<script src="./js/vis-timeline-graph2d.min.js"></script>
<script src="./js/aic-timeline.js"></script>
<script src="./js/datetime-local.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment-with-locales.min.js"></script>

<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
 <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
 <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->
</head>
<body>
<div class="container">
<!-- page header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark rounded">
 <a class="navbar-brand" href="index.php"><?=$app_name?></a>
 <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExample09" aria-controls="navbarsExample09" aria-expanded="false" aria-label="Toggle navigation">
 <span class="navbar-toggler-icon"></span>
 </button>

 <div class="collapse navbar-collapse">
 <ul class="navbar-nav mr-auto">
 <li class="nav-item active">
 <a class="nav-link" href="index.php"><i class="material-icons">home</i><span class="sr-only">(current)</span></a>
 </li></ul>

 <ul class="navbar-nav">
 <li class="navbar-brand text-muted"><?=isset($_SESSION['uname'])?$_SESSION['uname']:'ゲスト'?></li> 
<?php
if (isset($_SESSION['urole'])){
  $menu = array();
  if (($_SESSION['urole']==1 )||($_SESSION['urole']==2)){  //利用者
    $menu = array(   //申請者メニュー
      '会員情報'  => 'index.php?to=mbr&do=detail',
      '機器設備一覧'  => 'index.php?to=inst&do=list',
      '空き状況一覧'  => 'index.php?to=aic&do=list',
      '利用申請一覧'  => 'index.php?to=rsv&do=list',
    );
  }
  if($_SESSION['urole']==9) { //管理者
    $menu = array(   //管理者メニュー
      '会員一覧'  => 'index.php?to=mbr&do=list',
      '機器設備一覧'  => 'index.php?to=inst&do=list',
      '空き状況一覧'  => 'index.php?to=aic&do=list',
      //'年間スケジュール'  => '',
      //'お知らせ一覧' => '',
      '利用申請一覧' => 'index.php?to=rsv&do=list',
      //'利用状況集計' => ''
    );
  }
  foreach($menu as $_label=>$_action){ 
    echo  '<li class="nav-item"><a class="nav-link" href="' . $_action . '">' . $_label . '</a></li>' ;
  }
  echo  '<li class="nav-item"><a class="nav-link" href="index.php?to=user&do=logout">ログアウト</a></li>' ;
}else{
  echo  '<li class="nav-item"><a class="nav-link" href="index.php?to=user&do=login">ログイン</a></li>' ;
}
?>
</ul>
</div>
</nav>
<div class="container">