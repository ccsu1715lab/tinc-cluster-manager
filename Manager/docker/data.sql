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

/* 关闭外键约束检查(如果有外键关系) */
SET FOREIGN_KEY_CHECKS = 0;

/* 删除所有事件调度器 */
DROP EVENT IF EXISTS `delete_old_records_event`;
DROP EVENT IF EXISTS `update_node_status`;
DROP EVENT IF EXISTS `update_net_status`;

/* 删除所有触发器 */
DROP TRIGGER IF EXISTS `tr_net_status_recovery`;
DROP TRIGGER IF EXISTS `tr_net_status_offline`;
DROP TRIGGER IF EXISTS `tr_net_status_offline_update_downtime`;
DROP TRIGGER IF EXISTS `del`;
DROP TRIGGER IF EXISTS `infobackup`;
DROP TRIGGER IF EXISTS `network_status_change_trigger`;

/* 删除所有视图 */
DROP VIEW IF EXISTS `fa_netinserver`;

/* 删除所有表 */
DROP TABLE IF EXISTS `fa_network_disruption_log`;
DROP TABLE IF EXISTS `fa_network_recovery_log`;
DROP TABLE IF EXISTS `fa_network_health_score`;
DROP TABLE IF EXISTS `fa_node_ping_log`;
DROP TABLE IF EXISTS `fa_node_packet_loss`;
DROP TABLE IF EXISTS `fa_node_traffic`;
DROP TABLE IF EXISTS `fa_maintenance_log`;
DROP TABLE IF EXISTS `fa_nodeonline`;
DROP TABLE IF EXISTS `fa_node_backup`;
DROP TABLE IF EXISTS `fa_node`;
DROP TABLE IF EXISTS `fa_port`;
DROP TABLE IF EXISTS `fa_server`;
DROP TABLE IF EXISTS `fa_serverstatus`;
DROP TABLE IF EXISTS `fa_net_segment`;
DROP TABLE IF EXISTS `fa_net`;
DROP TABLE IF EXISTS `fa_event`;
DROP TABLE IF EXISTS `fa_log_operations`;
DROP TABLE IF EXISTS `fa_node_online_rate`;
DROP TABLE IF EXISTS `fa_netinserver`;
DROP TABLE IF EXISTS `fa_network_status_log`;

/* 创建所有表 */

/* 创建 fa_event 表 */
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

/* 创建 fa_log_operations 表 */
CREATE TABLE IF NOT EXISTS `fa_log_operations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occurrence_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


/* 创建 fa_net 表 */
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
  `offline_time` datetime DEFAULT NULL COMMENT '离线时间',
  `online_time` datetime DEFAULT NULL COMMENT '在线时间',
  `keepalive_time` datetime DEFAULT NULL COMMENT '心跳时间',
  `port` int(255) DEFAULT NULL COMMENT '内网端口号',
  `server_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `server_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


/* 创建 fa_net_segment 表 */
CREATE TABLE IF NOT EXISTS `fa_net_segment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `net_segment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `server_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attribution` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `server_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

/* 创建 fa_node 表 */
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

/* 创建 fa_node_online_rate 表 */
CREATE TABLE IF NOT EXISTS `fa_node_online_rate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timepoint` datetime NOT NULL COMMENT '统计时间',
  `online_rate` decimal(5,2) NOT NULL COMMENT '在线率百分比',
  `total_nodes` int(11) NOT NULL COMMENT '总节点数',
  `online_nodes` int(11) NOT NULL COMMENT '在线节点数',
  PRIMARY KEY (`id`),
  KEY `idx_timepoint` (`timepoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* 创建 fa_nodeonline 表 */
CREATE TABLE IF NOT EXISTS `fa_nodeonline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cntonline` int(11) NOT NULL DEFAULT '0',
  `server_name` varchar(255) NOT NULL,
  `net_name` varchar(255) NOT NULL,
  `timepoint` int(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* 创建 fa_node_backup 表 */
CREATE TABLE IF NOT EXISTS `fa_node_backup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` varchar(255) NOT NULL,
  `node_name` varchar(255) NOT NULL,
  `node_ip` varchar(255) NOT NULL,
  `id_foreign` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* 创建 fa_serverstatus 表 */
CREATE TABLE `fa_serverstatus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cpu_rate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `memory_rate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `conn_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `daemon_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `uptime` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

/* 创建 fa_port 表 */
CREATE TABLE IF NOT EXISTS `fa_port` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `server_ip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `port` int(11) NOT NULL,
  `attribution` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

/* 创建 fa_server 表 */
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

/* 创建 fa_network_recovery_log 表 */
CREATE TABLE IF NOT EXISTS `fa_network_recovery_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_name` varchar(255) NOT NULL COMMENT '服务器名',
  `net_name` varchar(100) NOT NULL COMMENT '网络名称',
  `offline_time` datetime NOT NULL COMMENT '离线时间',
  `recovery_time` datetime NOT NULL COMMENT '恢复时间',
  `duration` int(11) NOT NULL COMMENT '恢复时长(分钟)',
  `create_time` datetime NOT NULL COMMENT '记录创建时间',
  PRIMARY KEY (`id`),
  INDEX `idx_create_time` (`create_time`),
  INDEX `idx_server_net` (`server_name`, `net_name`),
  INDEX `idx_recovery_time` (`recovery_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='网络恢复时间日志';

/* 创建 fa_network_disruption_log 表 */
CREATE TABLE IF NOT EXISTS `fa_network_disruption_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_name` varchar(255) NOT NULL COMMENT '服务器名称',
  `net_name` varchar(100) NOT NULL COMMENT '网络名称',
  `offline_time` datetime NOT NULL COMMENT '离线时间',
  `recovery_time` datetime DEFAULT NULL COMMENT '恢复时间',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录创建时间',
  PRIMARY KEY (`id`),
  INDEX `idx_create_time` (`create_time`),
  INDEX `idx_server_net` (`server_name`, `net_name`),
  INDEX `idx_offline_time` (`offline_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='网络中断日志表';

/* 创建 fa_maintenance_log 表 */
CREATE TABLE IF NOT EXISTS `fa_maintenance_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operation` varchar(100) NOT NULL COMMENT '操作类型',
  `details` text COMMENT '详细信息',
  `execute_time` datetime NOT NULL COMMENT '执行时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:0=失败,1=成功',
  `admin_id` int(11) DEFAULT NULL COMMENT '管理员ID',
  `admin_name` varchar(100) DEFAULT NULL COMMENT '管理员名称',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_execute_time` (`execute_time`),
  KEY `idx_operation` (`operation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统维护日志表';

/* 创建 fa_network_health_score 表 */
CREATE TABLE IF NOT EXISTS `fa_network_health_score` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `net_id` int(11) NOT NULL COMMENT '网络ID',
  `score` float NOT NULL COMMENT '总健康分数',
  `stability_score` float NOT NULL COMMENT '稳定性分数',
  `performance_score` float NOT NULL COMMENT '性能分数',
  `quality_score` float NOT NULL COMMENT '质量分数',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_net_id` (`net_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='网络健康分数记录表';

/* 创建 fa_node_ping_log 表 */
CREATE TABLE IF NOT EXISTS `fa_node_ping_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_id` int(11) NOT NULL COMMENT '节点ID',
  `ping_time` float NOT NULL COMMENT 'ping时间(ms)',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_node_id` (`node_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='节点Ping记录表';

CREATE TABLE IF NOT EXISTS `fa_network_status_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_name` varchar(255) NOT NULL COMMENT '服务器名称',
  `net_name` varchar(255) NOT NULL COMMENT '网络名称',
  `status` varchar(20) NOT NULL COMMENT '状态(在线/离线)',
  `change_time` datetime NOT NULL COMMENT '状态变化时间',
  PRIMARY KEY (`id`),
  KEY `idx_server_net` (`server_name`, `net_name`),
  KEY `idx_change_time` (`change_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='网络状态变化日志表';


/* 创建触发器 */
DELIMITER $$

/* 创建 del 触发器 */
CREATE TRIGGER `del` AFTER DELETE ON `fa_node` FOR EACH ROW 
BEGIN
    DELETE FROM fa_node_backup WHERE sid = old.sid;
END$$

/* 创建 infobackup 触发器 */
CREATE TRIGGER `infobackup` AFTER INSERT ON `fa_node` FOR EACH ROW 
BEGIN
    INSERT INTO fa_node_backup (node_name, node_ip, id_foreign, sid) 
    VALUES (new.node_name, new.node_ip, new.id, new.sid);
END$$

/* 创建 tr_net_status_offline_update_downtime 触发器 */
CREATE TRIGGER `tr_net_status_offline_update_downtime` 
BEFORE UPDATE ON `fa_net`
FOR EACH ROW
BEGIN
    /* 当状态从"在线"变为"离线"时，更新down_time字段 */
    IF OLD.`status` = '在线' AND NEW.`status` = '离线' THEN
        SET NEW.offline_time = NOW();
    END IF;
END$$

/* 创建 tr_net_status_offline 触发器 */
CREATE TRIGGER `tr_net_status_offline` 
AFTER UPDATE ON `fa_net`
FOR EACH ROW
BEGIN
    /* 当状态从"在线"变为"离线"时 */
    IF OLD.`status` = '在线' AND NEW.`status` = '离线' THEN
        /* 插入中断记录 */
        INSERT INTO `fa_network_disruption_log` (
            `server_name`, 
            `net_name`, 
            `offline_time`,
            `recovery_time`,
            `create_time`
        ) VALUES (
            NEW.server_name,
            NEW.net_name,
            NOW(),          /* 使用当前时间作为离线时间 */
            NEW.online_time,       /* 使用最近一次在线时间 */
            NOW()
        );
    END IF;
END$$

/* 创建 tr_net_status_recovery 触发器 */
CREATE TRIGGER `tr_net_status_recovery` 
AFTER UPDATE ON `fa_net`
FOR EACH ROW
BEGIN
    /* 当状态从"离线"变为"在线"时 */
    IF OLD.`status` = '离线' AND NEW.`status` = '在线' THEN
        /* 计算恢复时长（分钟） */
        SET @duration = TIMESTAMPDIFF(MINUTE, NEW.offline_time, NOW());
        
        /* 确保恢复时长为正数且合理 */
        IF @duration < 0 THEN
            SET @duration = 0;
        ELSEIF @duration > 24*60 THEN  /* 限制最大为24小时 */
            SET @duration = 24*60;
        END IF;
        
        /* 插入恢复记录 */
        INSERT INTO `fa_network_recovery_log` (
            `server_name`, 
            `net_name`, 
            `offline_time`, 
            `recovery_time`, 
            `duration`, 
            `create_time`
        ) VALUES (
            NEW.server_name,
            NEW.net_name,
            NEW.offline_time,  /* 使用fa_net表中的offline_time作为离线时间 */
            NOW(),          /* 使用当前时间作为恢复时间 */
            @duration,
            NOW()
        );
        
        /* 更新中断日志的恢复时间 */
        UPDATE `fa_network_disruption_log`
        SET `recovery_time` = NOW()
        WHERE `server_name` = NEW.server_name
        AND `net_name` = NEW.net_name
        AND `recovery_time` IS NULL
        ORDER BY `id` DESC
        LIMIT 1;
    END IF;
END$$

DELIMITER $$
CREATE TRIGGER network_status_change_trigger
AFTER UPDATE ON fa_net
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO fa_network_status_log (server_name, net_name, status, change_time)
        VALUES (NEW.server_name, NEW.net_name, NEW.status, NOW());
    END IF;
END$$

DELIMITER ;

/* 检查事件调度器是否开启 */
SET @event_scheduler_status = (SELECT @@event_scheduler);
SET GLOBAL event_scheduler = ON;

DELIMITER $$

/* 创建节点状态更新事件 */
CREATE EVENT `update_node_status`
ON SCHEDULE EVERY 10 SECOND
ON COMPLETION PRESERVE
DO 
BEGIN
    UPDATE fa_node 
    SET status = 
        CASE 
            WHEN TIMESTAMPDIFF(SECOND, STR_TO_DATE(updatetime, '%Y-%m-%d %H:%i:%s'), NOW()) > 5 
            THEN '连接断开' 
            ELSE '连接正常' 
        END;
END$$

/* 创建网络状态更新事件 */
CREATE EVENT `update_net_status`
ON SCHEDULE EVERY 10 SECOND
ON COMPLETION PRESERVE
DO 
BEGIN
    UPDATE fa_net
    SET status = 
        CASE 
            WHEN TIMESTAMPDIFF(SECOND, STR_TO_DATE(keepalive_time, '%Y-%m-%d %H:%i:%s'), NOW()) > 5 
            THEN '离线' 
            ELSE '在线' 
        END;
END$$

/* 创建自动清理旧记录的事件 */
CREATE EVENT `delete_old_records_event`
ON SCHEDULE EVERY 30 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    /* 声明变量 */
    DECLARE clean_date DATETIME;
    DECLARE deleted_count INT DEFAULT 0;
    DECLARE total_deleted INT DEFAULT 0;
    
    /* 设置清理日期（15天前） */
    SET clean_date = DATE_SUB(CURRENT_DATE(), INTERVAL 15 DAY);
    
    /* 清理网络恢复日志 */
    DELETE FROM `fa_network_recovery_log` 
    WHERE `recovery_time` < clean_date;
    SET deleted_count = ROW_COUNT();
    SET total_deleted = total_deleted + deleted_count;
    
    /* 清理网络中断日志 */
    DELETE FROM `fa_network_disruption_log` 
    WHERE `offline_time` < clean_date;
    SET deleted_count = ROW_COUNT();
    SET total_deleted = total_deleted + deleted_count;
    
    /* 清理网络健康分数记录 */
    DELETE FROM `fa_network_health_score` 
    WHERE `create_time` < clean_date;
    SET deleted_count = ROW_COUNT();
    SET total_deleted = total_deleted + deleted_count;
    
    /* 清理节点Ping记录 */
    DELETE FROM `fa_node_ping_log` 
    WHERE `create_time` < clean_date;
    SET deleted_count = ROW_COUNT();
    SET total_deleted = total_deleted + deleted_count;
    
    /* 清理节点丢包率记录 */
    DELETE FROM `fa_node_packet_loss` 
    WHERE `create_time` < clean_date;
    SET deleted_count = ROW_COUNT();
    SET total_deleted = total_deleted + deleted_count;
    
    /* 清理节点流量记录 */
    DELETE FROM `fa_node_traffic` 
    WHERE `create_time` < clean_date;
    SET deleted_count = ROW_COUNT();
    SET total_deleted = total_deleted + deleted_count;
    
    /* 记录清理操作 */
    INSERT INTO `fa_maintenance_log` (
        `operation`, 
        `details`, 
        `execute_time`, 
        `status`
    ) VALUES (
        '自动清理旧记录',
        CONCAT('已清理', DATEDIFF(CURRENT_DATE(), clean_date), '天前的记录，共删除', total_deleted, '条数据'),
        NOW(),
        1
    );
END$$

DELIMITER ;

/* 恢复事件调度器状态（如果之前是关闭的） */
SET GLOBAL event_scheduler = @event_scheduler_status;

/* 开启外键约束检查 */
SET FOREIGN_KEY_CHECKS = 1;