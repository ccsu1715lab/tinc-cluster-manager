
CREATE TRIGGER `del` AFTER DELETE ON `fa_node` FOR EACH ROW 
BEGIN
    DELETE FROM fa_node_backup WHERE sid = old.sid;  -- 触发器逻辑（内部用 ; 不冲突）
END;

CREATE TRIGGER `infobackup` AFTER INSERT ON `fa_node` FOR EACH ROW 
BEGIN
    INSERT INTO fa_node_backup (node_name, node_ip, id_foreign, sid) 
    VALUES (new.node_name, new.node_ip, new.id, new.sid);
END;

CREATE TRIGGER `tr_net_status_offline_update_downtime` 
BEFORE UPDATE ON `fa_net`
FOR EACH ROW
BEGIN
    IF OLD.`status` = '在线' AND NEW.`status` = '离线' THEN
        SET NEW.offline_time = NOW();
    END IF;
    IF OLD.`status` = '离线' AND NEW.`status` = '在线' THEN
        SET NEW.online_time = NOW();
    END IF;
END;

CREATE TRIGGER `tr_net_status_offline` 
AFTER UPDATE ON `fa_net`
FOR EACH ROW
BEGIN
    IF OLD.`status` = '在线' AND NEW.`status` = '离线' THEN
        INSERT INTO `fa_network_disruption_log` (
            `server_name`, 
            `net_name`, 
            `offline_time`,
            `recovery_time`,
            `create_time`
        ) VALUES (
            NEW.server_name,
            NEW.net_name,
            NOW(),          
            NEW.online_time,       
            NOW()
        );
    END IF;
END;


CREATE TRIGGER `tr_net_status_recovery` 
AFTER UPDATE ON `fa_net`
FOR EACH ROW
BEGIN
    IF OLD.`status` = '离线' AND NEW.`status` = '在线' THEN
     
        SET @duration = TIMESTAMPDIFF(MINUTE, NEW.offline_time, NOW());
        

        IF @duration < 0 THEN
            SET @duration = 0;
        ELSEIF @duration > 24*60 THEN  
            SET @duration = 24*60;
        END IF;
        
  
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
            NEW.offline_time,  
            NOW(),          
            @duration,
            NOW()
        );
        
        UPDATE `fa_network_disruption_log`
        SET `recovery_time` = NOW()
        WHERE `server_name` = NEW.server_name
        AND `net_name` = NEW.net_name
        AND `recovery_time` IS NULL
        ORDER BY `id` DESC
        LIMIT 1;
    END IF;
END;


CREATE TRIGGER network_status_change_trigger
AFTER UPDATE ON fa_net
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO fa_network_status_log (server_name, net_name, status, change_time)
        VALUES (NEW.server_name, NEW.net_name, NEW.status, NOW());
    END IF;
END;

CREATE EVENT `update_node_status`
ON SCHEDULE EVERY 10 SECOND
ON COMPLETION PRESERVE
DO 
BEGIN
    UPDATE fa_node 
    SET status = 
        CASE 
            WHEN TIMESTAMPDIFF(SECOND, STR_TO_DATE(updatetime, '%Y-%m-%d %H:%i:%s'), NOW()) > 10 
            THEN '连接断开' 
            ELSE '连接正常' 
        END; 
END;  


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
END;  

CREATE EVENT `delete_old_records_event`
ON SCHEDULE EVERY 30 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
 
    DECLARE clean_date DATETIME;
    DECLARE deleted_count INT DEFAULT 0;
    DECLARE total_deleted INT DEFAULT 0;
    

    SET clean_date = DATE_SUB(CURRENT_DATE(), INTERVAL 15 DAY);
    DELETE FROM `fa_network_recovery_log` 
    WHERE `recovery_time` < clean_date;
    SET deleted_count = ROW_COUNT();
    SET total_deleted = total_deleted + deleted_count;
 
    DELETE FROM `fa_network_disruption_log` 
    WHERE `offline_time` < clean_date;
    SET deleted_count = ROW_COUNT();
    SET total_deleted = total_deleted + deleted_count;
  
    DELETE FROM `fa_network_health_score` 
    WHERE `create_time` < clean_date;
    SET deleted_count = ROW_COUNT();
    SET total_deleted = total_deleted + deleted_count;

    DELETE FROM `fa_node_ping_log` 
    WHERE `create_time` < clean_date;
    SET deleted_count = ROW_COUNT();
    SET total_deleted = total_deleted + deleted_count;
    
    DELETE FROM `fa_node_traffic` 
    WHERE `create_time` < clean_date;
    SET deleted_count = ROW_COUNT();
    SET total_deleted = total_deleted + deleted_count;
    
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
END;  
SET FOREIGN_KEY_CHECKS = 1;