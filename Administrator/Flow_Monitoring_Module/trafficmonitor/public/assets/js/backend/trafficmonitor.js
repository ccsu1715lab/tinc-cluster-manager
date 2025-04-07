define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数
            Table.api.init({
                extend: {
                    index_url: 'trafficmonitor/index', // 后端接口
                    table: 'traffic_monitor',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        {field: 'state', checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'hostname', title: __('主机名称')},
                        {field: 'host_ip', title: __('主机ip')},
                        {field: 'interface_name', title: __('网络接口名称')},
                        {field: 'sent_speed', title: __('流量发送速率（B/s）')},
                        {field: 'recv_speed', title: __('流量接收速率（B/s）')},
                        {field: 'record_time', title: __('记录时间')}
                    ]
                ],
            });

            // 绑定表格事件
            Table.api.bindevent(table);

            //给刷新数据按钮绑定事件
            $('#refresh-btn').on('click', function () {
                // 触发表格刷新
                table.bootstrapTable('refresh');
                // 可选：显示加载提示
                Table.api.refreshLoading(table);
            });
        },

        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});