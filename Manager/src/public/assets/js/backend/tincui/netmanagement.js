define(['jquery', 'bootstrap', 'backend', 'table', 'form'],function($,undefined,Backend,Table,Form){
    //初始化请求地址
    var Getsegport_url = 'tincui/Requestprocess/GetNetsegmentPortByServername';
    var IsNetRepeat_url = 'tincui/Requestprocess/IsNetRepeat';
    var check_url = 'tincui/Netmanagement/check';
    var desc_url = 'tincui/Netmanagement/desc';
    var Controller={
        
        index: function(){
            
            
            //初始化表格参数配置

            Table.api.init({
                extend: {
                    index_url: 'tincui/netmanagement/index',
                    add_url: 'tincui/netmanagement/add',
                    del_url: 'tincui/netmanagement/del',
                    table: 'tincui/netmanagement',
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
                        {field: 'username',title: '用户'},
                        {field: 'server_name',title:'接入服务器'},
                        {field: 'net_name',title: '内网名称'},
                        {field: 'esbtime',title: '创建时间'},
                        {field: 'net_segment',title: '网段'},
                        {field: 'node_cnt',title: '节点数量'},
                        {field: 'status',title: '内网状态'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                                                         buttons:[
                                                           {
                                                             name:'check',
                                                             text:'查看',
                                                             title:'查看',
                                                             classname: 'btn btn-xs btn-primary btn-dialog',
                                                             icon:'fa fa-check',
                                                             url:check_url,
                                                             visble:function(row){
                                                                if(row.status == 0){
                                                                    return true;
                                                                }
                                                                else{
                                                                    return false;
                                                                }
                                                             },
                                                             refresh:true
                                                            },
                                                            {
                                                                name:'description',
                                                                text:'备注',
                                                                title:'备注',
                                                                classname: 'btn btn-xs btn-primary btn-dialog',
                                                                icon:'fa fa-description',
                                                                url:desc_url,
                                                                visble:function(row){
                                                                   if(row.status == 0){
                                                                       return true;
                                                                   }
                                                                   else{
                                                                       return false;
                                                                   }
                                                                },
                                                                refresh:true
                                                               }
                                                         ]
                        }
                        
                    ]
                ]
            });
            //为表格绑定事件
            Table.api.bindevent(table);
            //自定义参数名实现自动筛选
          /*  const searchParams =new URLSearchParams(window.location.search);
            const netname = searchParams.get('netname');
            const netstate = searchParams.get('state');
            if(netname){
                var search = $("input[name='net_name']");
                search.val(netname);
                var searchform = $(".form-commonsearch");
                searchform.submit();
            }
            else if(netstate){
                var search = $("input[name='state']");
                search.val(netstate);
                var searchform = $(".form-commonsearch");
                searchform.submit();

            }*/
        },
        add: function(){           
            //根据接入服务器来获取内网和端口
            function GetNetsegmentPortByServername()
            {
                    $.ajax({
                        url:Getsegport_url,
                        type:'post',
                        data:{servername:$("#c-server_name").val()},
                        data_type:'text',
                        success:function(data){
                           console.log("success to request to tincui/Requestprocess/GetNetsegmentPortByServername");
                           createoption(data);
                        },
                        error:function(data){
                            console.log("fail to request to tincui/Requestprocess/GetNetsegmentPortByServername");
                            return null;
                        }
                
                    })
            }

            function createoption(data){
                 if(data!=null){
                    //添加网段
                            console.log(data);
                            const select_seg=document.getElementById("c-net_segment");
                            select_seg.options.length=1;
                            var option;
                            for(var i=0;i<data.seg.length;i++){
                                option=document.createElement("option");
                                option.text=data.seg[i];
                                option.value=data.seg[i];
                                select_seg.appendChild(option);
                            }
                    //添加端口
                            const select_port=document.getElementById("c-port");
                            select_port.options.length=1;
                            for(var i=0;i<data.port.length;i++){
                                option=document.createElement("option");
                                option.text=data.port[i];
                                option.value=data.port[i];
                                select_port.appendChild(option);
                            }
                 }
            }

           //获取服务器上的网段和端口
            $(document).ready(function(){
                $("#c-server_name").change(function(){
                      GetNetsegmentPortByServername();
                })
            })
             Controller.api.bindevent();
        },
        del: function(){
            Controller.api.bindevent();
        },
        check: function(){
            var table=$("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                escape: false,
                pagination: false,
                commonSearch: false,
                autoRefresh:true,
                autoRefreshInterval:5,
            });
            //为表格绑定事件
            Table.api.bindevent(table);

        },

        api:{
            bindevent: function()
            {  
                //提示信息的显示与隐藏      
              /*  let i = 0;//默认隐藏
                $(".btn-info").on('click',function(){
                    if(i==0)
                    {
                        $("#info").show();
                        i=1;
                    }
                    else
                    {
                        $("#info").hide();
                        i=0
                    }
                    
                });*/

                $.validator.config({
                    rules:{
                        checkIPv4seg: function(){
                            var ip=$("#c-net_segment").val();
                            var arr=ip.split(".");
                            if(arr.length!=3)return '无效ip';
                            for(let i of arr){
                                if(Object.is(Number(i),NaN)||Number(i)>255||Number(i)<0||i[0]=='0'&&i.length!=1){
                                    return '无效ip';
                                }
                            }
                            return true;

                        },
                        isnetnamerepeate:function()
                        {
                            var netname=$("#c-net_name").val();
                            var servername=$("#c-server_name").val();
                            return $.ajax({
                                url:IsNetRepeat_url,
                                type: 'post',
                                data:{netname:netname,servername:servername},
                                datatype:'json'
                            })
                        },
                        servername_rule: function()
                        {
                            var temp = $("#c-server_name").val();
                            var servername = (temp==null)?$("#servername"):temp;
                            if(servername == "下拉选择")
                            {
                                return "Invalid servername";
                            }
                            return true;
                        },
                        port_rule: function()
                        {
                            var port = $("#c-port").val();
                            if(isNaN(port))
                            {
                                return "Invalid port";
                            }
                            return true;
                        },
                    }

                });
                
                Form.api.bindevent($("form[role=form]"));

            }
        }
        
        }

    return Controller;
});