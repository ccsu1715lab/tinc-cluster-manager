<?php
namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Exception;

class CleanOldRecords extends Command
{
    protected function configure()
    {
        $this->setName('clean:old_records')
            ->setDescription('Clean records older than 15 days');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $output->writeln("开始清理15天前的记录...");
            
            // 计算15天前的日期
            $date = date('Y-m-d H:i:s', strtotime('-15 days'));
            
            // 清理网络恢复日志
            $count1 = Db::table('fa_network_recovery_log')
                ->where('recovery_time', '<', $date)
                ->delete();
            $output->writeln("已从网络恢复日志中删除 {$count1} 条记录");
            
            // 清理操作日志
            $count2 = Db::table('fa_log_operations')
                ->where('occurrence_time', '<', $date)
                ->delete();
            $output->writeln("已从操作日志中删除 {$count2} 条记录");
            
            // 清理节点日志
            $count3 = Db::table('fa_log_nodes')
                ->where('create_time', '<', $date)
                ->delete();
            $output->writeln("已从节点日志中删除 {$count3} 条记录");
            
            // 记录维护操作
            Db::table('fa_maintenance_log')->insert([
                'operation' => '清理旧记录',
                'details' => "已删除15天前的记录，共 " . ($count1 + $count2 + $count3) . " 条",
                'execute_time' => date('Y-m-d H:i:s')
            ]);
            
            $output->writeln("清理完成！");
            return 0;
        } catch (Exception $e) {
            $output->writeln("<error>清理失败: " . $e->getMessage() . "</error>");
            return 1;
        }
    }
} 