define(['jquery', 'bootstrap', 'backend', 'table', 'form'],function($, undefined, Backend, Table, Form){

    var Controller={
        index: function(){
            //初始化表盒参数设置
            Table.api.init({
                extend: {
                    index_url: 'logmanage/logoperations/index',
                    table: 'logmanage/logoperations',
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
                        {field: 'type',title: '类型'},
                        {field: 'result',title: '结果'},
                        {field: 'details',title: '详情'},
                        {field: 'occurrence_time', title: '发生时间'},
                    ]
                ]
            });

            //为表格绑定事件
            Table.api.bindevent(table);
        },

        api:{
            bindevent: function(){
                Form.api.bindevent($("form[role=form]"));
            }
        }

    };

    return Controller;
});
