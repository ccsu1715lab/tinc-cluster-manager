-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2024-01-26 18:11:47
-- 服务器版本： 5.7.40-log
-- PHP 版本： 7.3.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
-- 文件开头区域添加 --
SET GLOBAL event_scheduler = ON;
SET @@global.event_scheduler = ON;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `47_120_35_14`
--

-- --------------------------------------------------------

--
-- 替换视图以便查看 `fa_allserver`
-- （参见下面的实际视图）
--
CREATE TABLE IF NOT EXISTS `fa_allserver` (
`cntonline` decimal(32,0)
,`timepoint` int(255)
);

-- --------------------------------------------------------

--
-- 表的结构 `fa_event`
--

CREATE TABLE IF NOT EXISTS `fa_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `details` varchar(1000) NOT NULL,
  `time` varchar(255) NOT NULL,
  `ifqueried` varchar(255) NOT NULL DEFAULT 'no',
  `result` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `fa_log_operations`
--

CREATE TABLE IF NOT EXISTS `fa_log_operations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occurrence_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- 替换视图以便查看 `fa_myuser`
-- （参见下面的实际视图）
--
CREATE TABLE IF NOT EXISTS `fa_myuser` (
`id` int(10) unsigned
,`username` varchar(20)
,`nickname` varchar(50)
,`status` varchar(30)
,`net_cnt` bigint(21)
,`node_cnt` decimal(32,0)
,`logintime` bigint(16)
);

-- --------------------------------------------------------

--
-- 表的结构 `fa_net`
--

CREATE TABLE IF NOT EXISTS `fa_net` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `net_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_flag` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户标记',
  `net_segment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '1',
  `config` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `node_cnt` int(11) DEFAULT '0',
  `desc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esbtime` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `port` int(255) DEFAULT NULL COMMENT '内网端口号',
  `server_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `server_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- 替换视图以便查看 `fa_netinserver`
-- （参见下面的实际视图）
--
CREATE TABLE IF NOT EXISTS `fa_netinserver` (
`server_name` varchar(255)
,`cntonline` decimal(32,0)
,`timepoint` int(255)
);

-- --------------------------------------------------------

--
-- 表的结构 `fa_net_segment`
--

CREATE TABLE IF NOT EXISTS `fa_net_segment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `net_segment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `server_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attribution` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `server_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- 表的结构 `fa_node`
--

CREATE TABLE IF NOT EXISTS `fa_node` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `sid` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `node_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `node_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `net_name` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `user_flag` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '已下线' COMMENT '节点状态（已上线，已下线',
  `updatetime` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `config` varchar(1000) CHARACTER SET utf8mb4 DEFAULT NULL,
  `uptime` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `downtime` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `desc` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `is_update` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `server_name` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `server_ip` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `editsuccess` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT 'waiting',
  `config_state` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '配置中' COMMENT '节点配置状态（配置中，配置失败，配置成功',
  `esbtime` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `current_time` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- 触发器 `fa_node`
--
DELIMITER $$
CREATE TRIGGER `del` AFTER DELETE ON `fa_node` FOR EACH ROW delete from fa_node_backup where sid=old.sid
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `infobackup` AFTER INSERT ON `fa_node` FOR EACH ROW insert into fa_node_backup (node_name,node_ip,id_foreign,sid) values (new.node_name,new.node_ip,new.id,new.sid)
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- 表的结构 `fa_nodeonline`
--

CREATE TABLE IF NOT EXISTS `fa_nodeonline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cntonline` int(11) NOT NULL DEFAULT '0',
  `server_name` varchar(255) NOT NULL,
  `net_name` varchar(255) NOT NULL,
  `timepoint` int(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `fa_node_backup`
--

CREATE TABLE IF NOT EXISTS `fa_node_backup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` varchar(255) NOT NULL,
  `node_name` varchar(255) NOT NULL,
  `node_ip` varchar(255) NOT NULL,
  `id_foreign` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `fa_serverstatus`
--

DROP TABLE IF EXISTS `fa_serverstatus`;
CREATE TABLE `fa_serverstatus`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cpu_rate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `memory_rate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `conn_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `daemon_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `uptime` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- --------------------------------------------------------

--
-- 表的结构 `fa_port`
--

CREATE TABLE IF NOT EXISTS `fa_port` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `server_ip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `port` int(11) NOT NULL,
  `attribution` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- 表的结构 `fa_server`
--

CREATE TABLE IF NOT EXISTS `fa_server` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `server_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `server_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `net_total` int(10) DEFAULT '0',
  `status` int(5) DEFAULT '1',
  `start_segment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_segment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `desc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `port_range` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, 
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- 视图结构 `fa_allserver`
--
DROP TABLE IF EXISTS `fa_allserver`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `fa_allserver`  AS SELECT sum(`fa_nodeonline`.`cntonline`) AS `cntonline`, `fa_nodeonline`.`timepoint` AS `timepoint` FROM `fa_nodeonline` GROUP BY `timepoint`  ;

-- --------------------------------------------------------

--
-- 视图结构 `fa_myuser`
--
DROP TABLE IF EXISTS `fa_myuser`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `fa_myuser`  AS SELECT `fa_admin`.`id` AS `id`, `fa_admin`.`username` AS `username`, `fa_admin`.`nickname` AS `nickname`, `fa_admin`.`status` AS `status`, `a`.`net_cnt` AS `net_cnt`, `a`.`node_cnt` AS `node_cnt`, `fa_admin`.`logintime` AS `logintime` FROM (`fa_admin` left join (select `fa_net`.`username` AS `username`,count(0) AS `net_cnt`,sum(`fa_net`.`node_cnt`) AS `node_cnt` from `fa_net` group by `fa_net`.`username`) `a` on((`a`.`username` = `fa_admin`.`username`)))  ;

-- --------------------------------------------------------

--
-- 视图结构 `fa_netinserver`
--
DROP TABLE IF EXISTS `fa_netinserver`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `fa_netinserver`  AS SELECT `fa_nodeonline`.`server_name` AS `server_name`, sum(`fa_nodeonline`.`cntonline`) AS `cntonline`, `fa_nodeonline`.`timepoint` AS `timepoint` FROM `fa_nodeonline` GROUP BY `fa_nodeonline`.`server_name`, `timepoint`  ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- 新增事件调度器 --
DELIMITER $$
CREATE EVENT IF NOT EXISTS update_node_status
ON SCHEDULE EVERY 10 SECOND
ON COMPLETION PRESERVE
DO BEGIN
    UPDATE fa_node 
    SET status = 
        CASE 
            WHEN TIMESTAMPDIFF(SECOND, STR_TO_DATE(updatetime, '%Y-%m-%d %H:%i:%s'), NOW()) > 5 
            THEN '连接断开' 
            ELSE '连接正常' 
        END;
END $$
DELIMITER ;