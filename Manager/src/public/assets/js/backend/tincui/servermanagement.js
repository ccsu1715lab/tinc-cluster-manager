define(['jquery', 'bootstrap', 'backend', 'table', 'form'],function($, undefined, Backend, Table, Form){

    var Controller={
        index: function(){
            //初始化表盒参数设置
            Table.api.init({
                extend: {
                    index_url: 'tincui/servermanagement/index',
                    add_url: 'tincui/servermanagement/add',
                    del_url: 'tincui/servermanagement/del',
                    edit_url: 'tincui/servermanagement/edit',
                    table: 'tincui/servermanagement',
                }

            });

            //从后台获取数据以初始化表格
            var table=$("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('id'), operate: false},
                        {field: 'server_name', title:'服务器名称'},
                        {field: 'server_ip', title:'服务器ip'},
                        {field: 'net_total', title:'内网数量'},
                        {field: 'status', title: '状态'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });
            //为表格绑定事件
            Table.api.bindevent(table);
        },

        add: function(){
            Controller.api.bindevent();
        },
        del: function(){
            Controller.api.bindevent();
        },
        edit: function(){
            Controller.api.bindevent();
        },

        api:{
            bindevent: function(){
                Form.api.bindevent($("form[role=form]"));
            }
        }

    };

    return Controller;
});
