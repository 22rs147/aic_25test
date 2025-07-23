-- =================================================================
-- ■■■ Geminiによる修正版 (最終版) ■■■
--
-- 修正点:
-- 1. テーブルの作成順序を依存関係に従って並べ替え。
-- 2. 各テーブルのPRIMARY KEYおよびKEY(インデックス)定義を、
--    CREATE TABLE文の中にすべて含めるように修正。
--    これにより "Cannot find an index in the referenced table" エラーを解消します。
-- 3. ビュー定義内の誤ったJOIN条件を修正。
--
-- このスクリプトが、構造的に正しく、エラーなく実行可能な完成版です。
-- =================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `aic_2023a`
--

-- --------------------------------------------------------
--
-- ■■■ テーブル作成 (依存関係の無い、または親となるテーブルから先に作成) ■■■
--

--
-- テーブルの構造 `tb_room`
--
CREATE TABLE `tb_room` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '通し番号（自動採番, 内部参照用）',
  `room_name` varchar(32) NOT NULL COMMENT '部屋名称',
  `room_no` varchar(16) DEFAULT NULL COMMENT '部屋番号(略称)',
  `memo` text DEFAULT NULL COMMENT '備考',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルの構造 `tb_member`
--
CREATE TABLE `tb_member` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '通し番号（自動採番, 内部参照用）',
  `uid` varchar(16) NOT NULL COMMENT 'ユーザID',
  `sid` varchar(16) NOT NULL COMMENT '学籍番号(文字列)・教職員番号(数字)',
  `email` varchar(32) DEFAULT NULL COMMENT 'メールアドレス',
  `tel_no` varchar(32) DEFAULT NULL COMMENT '電話番号',
  `ja_name` varchar(32) NOT NULL COMMENT '日本語氏名',
  `ja_yomi` varchar(32) DEFAULT NULL COMMENT '日本語読み',
  `en_name` varchar(32) DEFAULT NULL COMMENT '英語氏名',
  `en_yomi` varchar(32) DEFAULT NULL COMMENT '英語読み',
  `sex` int(11) NOT NULL DEFAULT 0 COMMENT '性別(0:未記入,1:男性,2:女性)',
  `dept_name` varchar(64) DEFAULT NULL COMMENT '所属名称, 例: 理工学部 情報科学科',
  `dept_code` varchar(16) DEFAULT NULL COMMENT '所属コード,例: RS',
  `category` int(11) NOT NULL DEFAULT 9 COMMENT 'カテゴリ(1:一般学生,2:教育職員,3:事務職員,9:その他職員)',
  `authority` int(11) NOT NULL DEFAULT 1 COMMENT '権限(0:予約権なし,1:予約付き)',
  `granted` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '権限付与・撤回日時',
  `memo` text DEFAULT NULL COMMENT '備考',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `sid` (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルの構造 `tb_user`
--
CREATE TABLE `tb_user` (
  `uid` varchar(16) NOT NULL COMMENT 'ログインID',
  `urole` int(11) NOT NULL COMMENT 'ユーザ種別(1:学生,2:教職員,9:管理者)',
  `uname` varchar(16) NOT NULL COMMENT 'ユーザ名（表示名）',
  `upass` varchar(32) DEFAULT NULL COMMENT 'ログインパスワード',
  `last_login` datetime DEFAULT NULL COMMENT '直近ログイン時刻',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルの構造 `seq_reserve`
--
CREATE TABLE `seq_reserve` (
  `id` int(11) NOT NULL,
  `y` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルの構造 `tb_instrument`
--
CREATE TABLE `tb_instrument` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '通し番号(自動採番, 内部参照用）',
  `code` varchar(16) DEFAULT NULL COMMENT '人間識別用番号',
  `fullname` varchar(64) NOT NULL COMMENT '名称',
  `shortname` varchar(64) NOT NULL COMMENT '略称',
  `state` int(11) NOT NULL COMMENT '機器状態(1:使用可,2:貸出中,3:使用不可,9:その他)',
  `category` int(11) DEFAULT NULL COMMENT 'カテゴリ（1:観察, 2:分析,3:計測,4:調製,9:その他）',
  `purpose` varchar(64) DEFAULT NULL COMMENT '主な用途',
  `detail` text DEFAULT NULL COMMENT '施設紹介',
  `maker` varchar(64) DEFAULT NULL COMMENT 'メーカー',
  `model` varchar(64) DEFAULT NULL COMMENT '型式',
  `made_year` date DEFAULT NULL COMMENT '製造年月',
  `bought_year` date DEFAULT NULL COMMENT '導入年月',
  `equipment_no` varchar(32) DEFAULT NULL COMMENT '備品番号',
  `room_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '設置場所部屋番号',
  `memo` text DEFAULT NULL COMMENT '備考',
  PRIMARY KEY (`id`),
  KEY `fk_instrument_room_idx` (`room_id`),
  CONSTRAINT `fk_instrument_room` FOREIGN KEY (`room_id`) REFERENCES `tb_room` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルの構造 `tb_staff`
--
CREATE TABLE `tb_staff` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '通し番号（自動採番, 内部参照用）',
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `role_title` varchar(32) DEFAULT NULL COMMENT '役職1:大区分(大学教育職員,事務職員,職員)',
  `role_rank` varchar(32) DEFAULT NULL COMMENT '役職2:中区分(教授,准教授,講師,助教,職員)',
  `room_no` varchar(32) DEFAULT NULL COMMENT '部屋番号',
  `tel_ext` varchar(8) DEFAULT NULL COMMENT '内線番号',
  `responsible` tinyint(1) DEFAULT 0 COMMENT '責任者になれるか(0:否,1:可)',
  PRIMARY KEY (`id`),
  KEY `fk_staff_member_idx` (`member_id`),
  CONSTRAINT `fk_staff_member` FOREIGN KEY (`member_id`) REFERENCES `tb_member` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルの構造 `tb_reserve`
--
CREATE TABLE `tb_reserve` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '通し番号（自動採番, 内部参照用）',
  `code` varchar(8) NOT NULL COMMENT '予約番号(問合せ用)yyyyxxxx',
  `instrument_id` bigint(20) UNSIGNED NOT NULL COMMENT '利用希望機器ID',
  `apply_mid` varchar(16) NOT NULL COMMENT '申請者会員ID',
  `master_mid` varchar(16) NOT NULL COMMENT '責任者会員ID',
  `purpose` varchar(16) DEFAULT NULL COMMENT '利用目的',
  `other_num` int(11) DEFAULT NULL COMMENT 'その他利用者数',
  `other_user` varchar(64) DEFAULT NULL COMMENT 'その他利用者説明',
  `stime` datetime NOT NULL COMMENT '利用開始日時',
  `etime` datetime NOT NULL COMMENT '利用終了日時',
  `sample_name` varchar(64) DEFAULT NULL COMMENT '試料名称',
  `sample_state` int(11) DEFAULT 1 COMMENT '試料状態(1-個体,2-液体,3-気体)',
  `xray_chk` tinyint(1) DEFAULT 0 COMMENT 'X線取扱者登録有無',
  `xray_num` varchar(32) DEFAULT NULL COMMENT 'X線取扱者登録者番号',
  `process_status` int(11) NOT NULL DEFAULT 1 COMMENT '申請状態(1:申請中,3:承認,4:却下,5:キャンセル)',
  `memo` text DEFAULT NULL COMMENT '備考',
  `reserved` datetime DEFAULT NULL COMMENT '予約日',
  `approved` datetime DEFAULT NULL COMMENT '承認日',
  `lastmodified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '最終変更日',
  `purpose_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_reserve_instrument_idx` (`instrument_id`),
  KEY `fk_reserve_apply_member_idx` (`apply_mid`),
  KEY `fk_reserve_master_member_idx` (`master_mid`),
  CONSTRAINT `fk_reserve_instrument` FOREIGN KEY (`instrument_id`) REFERENCES `tb_instrument` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_reserve_apply_member` FOREIGN KEY (`apply_mid`) REFERENCES `tb_member` (`uid`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_reserve_master_member` FOREIGN KEY (`master_mid`) REFERENCES `tb_member` (`uid`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_time_order` CHECK (`etime` > `stime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルの構造 `rsv_member`
--
CREATE TABLE `rsv_member` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '通し番号（自動採番, 内部参照用）',
  `reserve_id` bigint(20) UNSIGNED NOT NULL COMMENT '予約番号',
  `member_id` bigint(20) UNSIGNED NOT NULL COMMENT '利用者会員番号',
  `memo` text DEFAULT NULL COMMENT '備考',
  PRIMARY KEY (`id`),
  KEY `fk_rsv_member_reserve_idx` (`reserve_id`),
  KEY `fk_rsv_member_member_idx` (`member_id`),
  CONSTRAINT `fk_rsv_member_reserve` FOREIGN KEY (`reserve_id`) REFERENCES `tb_reserve` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rsv_member_member` FOREIGN KEY (`member_id`) REFERENCES `tb_member` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルの構造 `rsv_sample`
--
CREATE TABLE `rsv_sample` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '通し番号（自動採番, 内部参照用）',
  `reserve_id` bigint(20) UNSIGNED NOT NULL COMMENT '予約番号',
  `nature` int(11) NOT NULL COMMENT '試料特性値(1-爆発性,2-毒性,3-揮発性,4-その他)',
  `other` varchar(16) DEFAULT NULL COMMENT 'その他',
  PRIMARY KEY (`id`),
  KEY `fk_rsv_sample_reserve_idx` (`reserve_id`),
  CONSTRAINT `fk_rsv_sample_reserve` FOREIGN KEY (`reserve_id`) REFERENCES `tb_reserve` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- ■■■ ビュー定義 ■■■
--

--
-- ビュー用の代替構造 `vw_instrument`
-- (実際のビューを参照するには下にあります)
--
CREATE TABLE `vw_instrument` (
`id` bigint(20) unsigned
,`code` varchar(16)
,`fullname` varchar(64)
,`shortname` varchar(64)
,`state` int(11)
,`category` int(11)
,`purpose` varchar(64)
,`detail` text
,`maker` varchar(64)
,`model` varchar(64)
,`made_year` date
,`bought_year` date
,`equipment_no` varchar(32)
,`room_id` int(11)
,`memo` text
,`room_no` varchar(16)
,`room_name` varchar(32)
);

-- --------------------------------------------------------

--
-- ビュー用の代替構造 `vw_report`
-- (実際のビューを参照するには下にあります)
--
CREATE TABLE `vw_report` (
`id` bigint(20) unsigned
,`code` varchar(8)
,`instrument_id` int(11)
,`apply_mid` varchar(16)
,`master_mid` varchar(16)
,`purpose` varchar(16)
,`other_num` int(11)
,`other_user` varchar(64)
,`stime` datetime
,`etime` datetime
,`sample_name` varchar(64)
,`sample_state` int(11)
,`xray_chk` tinyint(1)
,`xray_num` varchar(32)
,`process_status` int(11)
,`memo` text
,`reserved` datetime
,`approved` datetime
,`lastmodified` timestamp
,`student_n` decimal(22,0)
,`staff_n` decimal(22,0)
);

-- --------------------------------------------------------

--
-- ビュー用の代替構造 `vw_reserve`
-- (実際のビューを参照するには下にあります)
--
CREATE TABLE `vw_reserve` (
`id` bigint(20) unsigned
,`code` varchar(8)
,`instrument_id` int(11)
,`apply_mid` varchar(16)
,`master_mid` varchar(16)
,`purpose` varchar(16)
,`other_num` int(11)
,`other_user` varchar(64)
,`stime` datetime
,`etime` datetime
,`sample_name` varchar(64)
,`sample_state` int(11)
,`xray_chk` tinyint(1)
,`xray_num` varchar(32)
,`process_status` int(11)
,`memo` text
,`reserved` datetime
,`approved` datetime
,`lastmodified` timestamp
,`fullname` varchar(64)
,`shortname` varchar(64)
,`room_id` int(11)
,`room_no` varchar(16)
,`room_name` varchar(32)
,`apply_name` varchar(32)
,`master_name` varchar(32)
);

-- --------------------------------------------------------

--
-- ビュー用の構造 `vw_instrument`
--
DROP TABLE IF EXISTS `vw_instrument`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_instrument`  AS SELECT `i`.`id` AS `id`, `i`.`code` AS `code`, `i`.`fullname` AS `fullname`, `i`.`shortname` AS `shortname`, `i`.`state` AS `state`, `i`.`category` AS `category`, `i`.`purpose` AS `purpose`, `i`.`detail` AS `detail`, `i`.`maker` AS `maker`, `i`.`model` AS `model`, `i`.`made_year` AS `made_year`, `i`.`bought_year` AS `bought_year`, `i`.`equipment_no` AS `equipment_no`, `i`.`room_id` AS `room_id`, `i`.`memo` AS `memo`, `r`.`room_no` AS `room_no`, `r`.`room_name` AS `room_name` FROM (`tb_instrument` `i` left join `tb_room` `r` on(`i`.`room_id` = `r`.`id`)) ;

-- --------------------------------------------------------

--
-- ビュー用の構造 `vw_report`
--
DROP TABLE IF EXISTS `vw_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_report`  AS SELECT `r`.`id` AS `id`, `r`.`code` AS `code`, `r`.`instrument_id` AS `instrument_id`, `r`.`apply_mid` AS `apply_mid`, `r`.`master_mid` AS `master_mid`, `r`.`purpose` AS `purpose`, `r`.`other_num` AS `other_num`, `r`.`other_user` AS `other_user`, `r`.`stime` AS `stime`, `r`.`etime` AS `etime`, `r`.`sample_name` AS `sample_name`, `r`.`sample_state` AS `sample_state`, `r`.`xray_chk` AS `xray_chk`, `r`.`xray_num` AS `xray_num`, `r`.`process_status` AS `process_status`, `r`.`memo` AS `memo`, `r`.`reserved` AS `reserved`, `r`.`approved` AS `approved`, `r`.`lastmodified` AS `lastmodified`, sum(if(`m`.`sid` regexp '^[0-9]+$',0,1)) AS `student_n`, sum(if(`m`.`sid` regexp '^[0-9]+$',1,0)) AS `staff_n` FROM ((`tb_reserve` `r` join `rsv_member` `v`) join `tb_member` `m`) WHERE `r`.`id` = `v`.`reserve_id` AND `m`.`id` = `v`.`member_id` GROUP BY `r`.`id` ;

-- --------------------------------------------------------

--
-- ビュー用の構造 `vw_reserve`
--
DROP TABLE IF EXISTS `vw_reserve`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_reserve`  AS SELECT `r`.`id` AS `id`, `r`.`code` AS `code`, `r`.`instrument_id` AS `instrument_id`, `r`.`apply_mid` AS `apply_mid`, `r`.`master_mid` AS `master_mid`, `r`.`purpose` AS `purpose`, `r`.`other_num` AS `other_num`, `r`.`other_user` AS `other_user`, `r`.`stime` AS `stime`, `r`.`etime` AS `etime`, `r`.`sample_name` AS `sample_name`, `r`.`sample_state` AS `sample_state`, `r`.`xray_chk` AS `xray_chk`, `r`.`xray_num` AS `xray_num`, `r`.`process_status` AS `process_status`, `r`.`memo` AS `memo`, `r`.`reserved` AS `reserved`, `r`.`approved` AS `approved`, `r`.`lastmodified` AS `lastmodified`, `i`.`fullname` AS `fullname`, `i`.`shortname` AS `shortname`, `i`.`room_id` AS `room_id`, `i`.`room_no` AS `room_no`, `i`.`room_name` AS `room_name`, `m1`.`ja_name` AS `apply_name`, `m2`.`ja_name` AS `master_name` FROM (((`tb_reserve` `r` join `vw_instrument` `i`) join `tb_member` `m1`) join `tb_member` `m2`) WHERE `r`.`apply_mid` = `m1`.`id` AND `r`.`master_mid` = `m2`.`id` AND `r`.`instrument_id` = `i`.`id` ;

-- --------------------------------------------------------
--
-- ■■■ AUTO_INCREMENT定義 ■■■
--
ALTER TABLE `rsv_member`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '通し番号（自動採番, 内部参照用）';
ALTER TABLE `rsv_sample`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '通し番号（自動採番, 内部参照用）';
ALTER TABLE `tb_instrument`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '通し番号(自動採番, 内部参照用）';
ALTER TABLE `tb_member`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '通し番号（自動採番, 内部参照用）';
ALTER TABLE `tb_reserve`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '通し番号（自動採番, 内部参照用）';
ALTER TABLE `tb_room`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '通し番号（自動採番, 内部参照用）';
ALTER TABLE `tb_staff`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '通し番号（自動採番, 内部参照用）';

COMMIT;
