define(['jquery', 'bootstrap', 'backend', 'table', 'form'],function($,undefined,Backend,Table,Form){  
    //初始化参数配置
    var set_waiting_url = 'tincui/Requestprocess/set_waiting';
    var GetnetnameByServer_url = 'tincui/Requestprocess/GetnetnameByServername';
    var GetnetipBynetname_url = 'tincui/Requestprocess/GetSegmentByNet';
    var desc_url = 'tincui/nodemanagement/desc';
    var add_url = 'tincui/nodemanagement/add';
    var index_url = 'tincui/nodemanagement/index';
    var edit_url = 'tincui/nodemanagement/edit';
    var del_url = 'tincui/nodemanagement/del';
    var IsipRepeat_url = 'tincui/Requestprocess/IsipRepeat';
    var IsnodenameRepeat_url = 'tincui/Requestprocess/IsnodenameRepeat';
    var sidmatchpass_url = 'tincui/Requestprocess/sidmatchpass';
    var IsupdateSuccess_url = 'tincui/Requestprocess/IsupdateSuccess';
    var EventQuery_url = 'tincui/Requestprocess/EventQuery';
    var Controller={
        index: function(){
            // ... existing code ...

// 自动触发刷新按钮点击事件
function autoTriggerRefresh() {
    // 获取带有btn-refresh类名的元素
    const refreshButton = document.querySelector('.btn-refresh');
    
    // 如果找到了按钮元素
    if (refreshButton) {
        // 设置定时器，每10秒触发一次点击事件
        setInterval(() => {
            refreshButton.click();
        }, 10000); // 10000毫秒 = 10秒
    } else {
        console.warn('未找到刷新按钮元素');
    }
}
autoTriggerRefresh();

// 页面加载完成后启动自动刷新
document.addEventListener('DOMContentLoaded', () => {
    autoTriggerRefresh();
});

// ... existing code ...
            var EventQueryTimer=null;  
            //事件查询定时器回调函数
            function TimerCall_EventQuery()
            {
                $.ajax({
                    url:EventQuery_url,
                    type:'post',
                    data_tpye:'json',
                    success:function(data)
                    {
                        ProcessEvent(data);
                        //console.log("data："+data);
                    },
                    error:function()
                    {
                        console.log("error in TimerCall_EventQuery：fail to request"+EventQuery_url);
                    }
                })
                
            }
        
            //启动事件查询器
            function StartEventQueryTimer()
            {
                if(EventQueryTimer==null)
                EventQueryTimer = setInterval(TimerCall_EventQuery,2000);
            }
            //关闭事件查询器
            function StopEventQueryTimer()
            {
                if(EventQueryTimer)
                {
                    clearInterval(EventQueryTimer);
                    EventQueryTimer==null;
                }
            }
            //事件对象的处理
            function ProcessEvent($events)
            {
                if($events.length==0)
                {
                    console.log("暂无事件");
                }else{
                    for(let x in $events)
                    {
                        console.log(x,$events[x]);
                        if($events[x]["type"]!="status")
                        {
                            alert($events[x]["details"]);
                        }
                        
                        
                        
                    }
                    $(".fa-refresh").trigger("click");
                }

            }
        
            StartEventQueryTimer();
            //初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: index_url,
                    add_url: add_url,
                    del_url: del_url,
                    edit_url:edit_url,
                    table: 'tincui/nodemanagement',
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
                        {field: 'sid',title: '设备ID'},
                        {field: 'server_name',title: '服务器'},
                        {
                            field: 'net_name',title: '所属内网',
                            formatter:function(value,row,index){
                                return '<a href="netmanagement/index?net_name='+ value +'" target="_blank">'+ value +'</a>'
                            },
                        },
                        {field: 'node_name',title: '节点名称'},
                        
                        {field: 'node_ip',title: '内网ip'},
                        {field: 'esbtime',title: '创建时间'},
                        {field: 'status',title: '节点状态'},
                        {field: 'config_state',title: '配置状态'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                                                                                                                        buttons:[
                                                                                                                        
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

        },
        add: function(){
            //内网自动填充
            $(document).ready(function()
            {
                $("#c-server_name").change(function()
                {
                    $.ajax(
                    {
                        url:GetnetnameByServer_url,
                        type:'post',
                        data:{servername:$("#c-server_name").val()},
                        data_type:'text',
                        success:function(data){
                            console.log(data);                    
                            const select = document.getElementById("c-net_name");
                            select.options.length=1;
                            var option;
                         for(var i=0;i<data.length;i++)
                         {
                           option = document.createElement("option");
                           option.text=data[i];
                           option.value=data[i];
                           select.appendChild(option);
                         }
                        },
                        error()
                        {
                            console.log("fail to request to"+GetnetnameByServer_url);
                        }
                        
                    
                    })
                });
                //自动填充
                $("#c-net_name").change(function()
                {
                    $.ajax({
                    url:GetnetipBynetname_url,
                    type:'post',
                    data:{servername:$("#c-server_name").val(),netname:$("#c-net_name").val()},
                    data_type:'text',
                    success:function(data){
                        if(data==null)
                        {
                            console.log("error：Net"+netname+"has not seg!");
                        }
                        var selet = document.getElementById("c-node_ip");                       
                        selet.value = data;
                        console.log(data);
                    }
                  
                           });
                
                
                }); 
            }); 
             Controller.api.bindevent();
        },
        edit: function(){
            var timer=null;
            var ids;
            function set_waiting(){
                $.ajax({
                    url:set_waiting_url,
                   type:'post',
                   data_tpye:'text',
                   data:{ids:ids},
                   success:function(data){
                    if(data==1){
                        console.log("以还原");
                    }else{
                        console.log("未还原");
                    }
                   },
                   error:function(){
                    console.log("服务器请求错误");
                   } 
                })
            }
            function timerCallback(){
             $.ajax({
                 url:IsupdateSuccess_url,
                type:'post',
                data_tpye:'text',
                data:{ids:ids},
                success:function(data){
                 if(data=="yes"){
                     console.log("yes");
                     alert("配置完成");
                     stopTimer();
                     set_waiting();
                     enableform();
                   
                 }else if(data=="no"){
                    console.log("配置失败");
                    alert("配置失败");
                    stopTimer();
                    set_waiting();
                 }
                 else if(data=="waiting"){
                     console.log("waiting");
                 }
                 else{
                    console.log("Internal Service Error in"+IsipRepeat_url);
                    alert("配置失败："+"Internal Service Error in"+IsipRepeat_url);
                    stopTimer();
                    set_waiting();
                 }
                },
                error:function(){
                 console.log("服务器请求错误");
                } 
             })
           //  console.log("fda");
            }

 
            //启动定时器
            function startTimer(){
             if(!timer){
                 timer=setInterval(timerCallback,3000);
             }
            }
           //停止定时器
            function stopTimer(){
             if(timer){
                 clearInterval(timer);
                 timer=null;
             }
            }
           //异步提交数据
            function submitdata(){                
                var form = document.getElementById("edit-form");
                var formdata=new FormData(form);
                var obj = new Object();
                for(var pair of formdata.entries()){
                    if(pair[0]!="row[type]"){
                        obj[pair[0]]=pair[1];
                    }
                }
               obj= JSON.stringify(obj);                
                $.ajax({
                    url:edit_url,
                    type:'post',
                    data:{params:obj,ids:ids},
                    data_type:'text',
                    success:function(data){

                        if(data==true)
                        console.log("数据修改成功");
                        else 
                        console.log("数据修改失败");
                       
                    },error(){
                        console.log("fail to edit");
                    }
                })
            }

            //禁止表单
            function disableform()
            {
                var form = document.getElementById("edit-form");
                var SubmitButton = document.querySelector(".btn-success");

                form.style.pointerEvents = "none";
                form.style.opacity = "0.5";
                SubmitButton.disabled = true;
            }

            //开放表单
            function enableform()
            {
                var form = document.getElementById("edit-form");
                var SubmitButton = document.querySelector("btn-success");

                form.style.pointerEvents = "auto";
                form.style.opacity = "1";
                SubmitButton.disabled = false;
            }
 
           $(document).ready(function(){
             /*$(".btn-success-edit").click(function(){
                 var event = window.event;event.preventDefault();
                 ids = Fast.api.query('ids');
                 //异步提交表单数据
                 submitdata();
                 alert("配置中，请耐性等待");
                 disableform();
                 startTimer();
                
             });*/

             //输入框隐藏
             
             $(document).on("click","input[name='row[type]']",function(){
                $(".tf-updatepassword").addClass("hidden");
                $(".tf-updatenodename").addClass("hidden");
                $(".tf-updatenodeip").addClass("hidden");
                $(".tf-" + $(this).val()).removeClass("hidden");
             })

             $("#edit-form").submit(function(){
                $("#edit-form").validator({
                    ignore: ':hidden'
                });
            })


         }); 
 
             Controller.api.bindevent();
         },
        del: function(){
            Controller.api.bindevent();
        },

        api:{
            bindevent: function(){            
                //提示信息的显示与隐藏      
                let i = 0;//默认隐藏
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
                    
                });
                
              
                $.validator.config({
                    rules:{
                        checkIPv4: function()
                        {
                            var ip=$("#c-node_ip").val();
                            var arr=ip.split(".");
                            if(arr.length!=4)return '无效ip';
                            for(let i of arr){
                                if(Object.is(Number(i),NaN)||Number(i)>255||Number(i)<0||i[0]=='0'&&i.length!=1){
                                    return '无效ip';
                                }
                            }
                            return true;

                        },
                        sidmatchpass: function()
                        {
                            var rowpass=$("#rowpass").val();
                            var sid=$("#sid").val();
                           return $.ajax({
                            url: sidmatchpass_url,
                            type: 'post',
                            data: {rowpass:rowpass,sid:sid},
                            datatype: 'json'
                           })
                        },
                        IsnodeRepeat: function()
                        {
                            var nodename=null;
                            var servername = null;
                            var netname=null;
                            
                            nodename=$("#c-node_name").val();
                            servername=$("#c-server_name").val();                         
                            netname=$("#c-net_name").val(); 
                             if(netname==null)
                            {
                                netname=$("#netname").val();
                            }
                            if(servername==null)
                            {
                                servername=$("#servername").val();
                            }
                           return $.ajax(
                               {
                               url:IsnodenameRepeat_url,
                               type: 'post',
                               data:{nodename:nodename,servername:servername,netname:netname},
                               datatype:'json'
                                });
                        },
                        IsipRepeat: function()
                        {
                            var temp = $("#c-server_name").val();
                            
                            var servername=(temp==null)?$("#servername").val():temp
                            var nodeip=$("#c-node_ip").val();
                            if(servername==null||nodeip==null)
                            {
                                return 'Internal Server error: params cannot be null';
                            }
                            return $.ajax({
                                url:IsipRepeat_url,
                                type: 'post',
                                data:{nodeip:nodeip,servername:servername},
                                datatype:'json'
                            });
                        },
                        netname_rule: function()
                        {
                            var netname = $("#c-net_name").val();
                            if(netname=="init")
                            {
                                return "请选择内网";
                            }
                            else{
                                return true;
                            }
                            
                        },
                        servername_rule: function()
                        {
                            var temp = $("#c-server_name").val();
                            var servername = (temp==null)?$("#servername"):temp;
                            if(servername == "init")
                            {
                                return "请选择接入服务器";
                            }
                            return true;
                        }
                    }

                });
                
                Form.api.bindevent($("form[role=form]"));

            }
        }
        
        }

    return Controller;
});