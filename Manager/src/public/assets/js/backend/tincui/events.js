define(['jquery', 'bootstrap', 'backend', 'table', 'form'],function($,undefined,Backend,Table,Form){
    //初始化请求地址
    var Controller={
        
        index: function(){
            //自动刷新           
            setInterval(function () 
            {
                $(".fa-refresh").trigger("click");
            }, 60000);        
           
            
            
            //初始化表格参数配置

            Table.api.init({
                extend: {
                    index_url: 'tincui/events/index',
                    del_url: 'tincui/events/del',
                    table: 'tincui/events',
                }
            });

            //初始化表格
            var table=$("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pagination: true,
                commonSearch: true,
                autoRefresh:true,
                autoRefreshInterval:5,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id',title: 'ids'},
                        {field: 'username',title: '管理员'},
                        {field: 'type',title:'类型'},
                        {field: 'result',title:'结果'},
                        {field: 'details',title: '详情'},
                        {field: 'time',title: '产生时间'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,                                                       
                        }
                       
                    ]
                ]
            });
            //为表格绑定事件
            Table.api.bindevent(table);

        },

        del: function(){
            Controller.api.bindevent();
        },

        api:{
            bindevent: function()
            {  
                
                Form.api.bindevent($("form[role=form]"));

            }
        }
        
        }

    return Controller;
});