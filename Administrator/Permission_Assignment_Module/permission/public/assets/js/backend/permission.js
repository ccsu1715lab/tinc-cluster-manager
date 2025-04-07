define(['jquery', 'bootstrap', 'backend', 'table', 'form'],function($, undefined, Backend, Table, Form){

    var Controller={
        index: function(){
            //初始化表盒参数设置
            Table.api.init({
                extend: {
                    index_url: 'permission/index',
                    add_url: 'permission/add',
                    table: 'permission',
                }

            });

            //从后台获取数据以初始化表格
            var table=$("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id',title: __('id'), operate: false},//operate: false可以将id从普通搜索中移除
                        {field: 'username',title: '用户名'},
                        {field: 'nickname',title: '昵称'},
                        {field: 'groups_text', title: __('Group'), operate:false, formatter: Table.api.formatter.label},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        add: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});