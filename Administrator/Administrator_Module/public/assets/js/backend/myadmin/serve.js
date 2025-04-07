define(['jquery', 'bootstrap', 'backend', 'table', 'form'],function($, undefined, Backend, Table, Form){

    var Controller={
        index: function(){
            //初始化表盒参数设置
            Table.api.init({
                extend: {
                    index_url: 'myadmin/serve/index',
                    add_url: 'myadmin/serve/add',
                    del_url: 'myadmin/serve/del',
                    edit_url: 'myadmin/serve/edit',
                    table: 'myadmin/serve',
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
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                        buttons:[
                                    {
                                        name:'check',

                                        text:'查看',
                                        title:'查看',
                                        classname: 'btn btn-xs btn-primary btn-dialog',
                                        icon:'fa fa-check',
                                        url:'myadmin/serve/check',
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
            Controller.api.bindevent();
        },
        del: function(){
            Controller.api.bindevent();
        },
        edit: function(){
            Controller.api.bindevent();
        },
        check: function(){
            var flag=0;
            var angle;
            var angle1=0;
            var ids=$("#ids").val();
            var cpuleft_progress = document.getElementById('cpuleft-progress');
            var cpuright_progress = document.getElementById('cpuright-progress');
            var memoryleft_progress = document.getElementById('memoryleft-progress');
            var memoryright_progress = document.getElementById('memoryright-progress');
            var connbax = document.getElementById('rotateBoxconn');
            var daemonbax = document.getElementById('rotateBoxdaemon');
            function circle_chart_cpu(angle){
                if(angle>180){
                    cpuleft_progress.style.transition = 'none';
                    cpuleft_progress.style.transform = 'rotate(180deg)';
                    cpuleft_progress.style.transition = 'transform 0s';
                    cpuright_progress.style.transition = 'none';
                    cpuright_progress.style.transform = 'rotate(' + (angle-180) + 'deg)';
                    cpuright_progress.style.transition = 'transform 0.5s';
                }else{
                    cpuright_progress.style.transition = 'none';
                    cpuright_progress.style.transform = 'rotate(0deg)';
                    cpuright_progress.style.transition = 'transform 0s';
                    cpuleft_progress.style.transition = 'none';
                    cpuleft_progress.style.transform = 'rotate(' + angle + 'deg)';
                    cpuleft_progress.style.transition = 'transform 0.5s'; 
                }
                
                  
            };

            function circle_char_memory(angle){
                if(angle>180){
                    memoryleft_progress.style.transition = 'none';
                    memoryleft_progress.style.transform = 'rotate(180deg)';
                    memoryleft_progress.style.transition = 'transform 0s';
                    memoryright_progress.style.transition = 'none';
                    memoryright_progress.style.transform = 'rotate(' + (angle-180) + 'deg)';
                    memoryright_progress.style.transition = 'transform 0.5s';
                }else{
                    memoryright_progress.style.transition = 'none';
                    memoryright_progress.style.transform = 'rotate(0deg)';
                    memoryright_progress.style.transition = 'transform 0s';
                    memoryleft_progress.style.transition = 'none';
                    memoryleft_progress.style.transform = 'rotate(' + angle + 'deg)';
                    memoryleft_progress.style.transition = 'transform 0.5s'; 
                }
            };
             
            function connrotatebox(angle){

                connbax.style.transform = 'rotate(' + angle + 'deg)';
                connbax.style.transition = 'transform 0.5s'; 
            };

            function daemonrotatebox(angle){

                daemonbax.style.transform = 'rotate(' + angle + 'deg)';
                daemonbax.style.transition = 'transform 0.5s'; 
            };

            function Timerevent()
            {
                $.ajax({
                    url:'myadmin/Serve/get_serverstatus',
                    type:'post',
                    data:{ids:ids},
                    data_type:'text',
                    success:function(data){
                        //遍历更新
                        var connstatus=data['conn_status'];
                        var element_connstatus = document.getElementById("connstatus");
                        var element_cpurate = document.getElementById("cpurate");
                        var element_memoryrate = document.getElementById("memoryrate");
                        var element_daemonstatus = document.getElementById("daemonstatus");
                        var oldconnstatus=element_connstatus.innerHTML;
                        
                        if(connstatus!='normal'&&oldconnstatus!="UNREGONIZED"){
                            element_connstatus.innerHTML = "UNREGONIZED";
                            element_cpurate.innerHTML = 'UNREGONIZED';
                            element_memoryrate.innerHTML = 'UNREGONIZED';
                            element_daemonstatus.innerHTML = 'UNREGONIZED';
                            //改变字体颜色和大小
                            element_cpurate.setAttribute("style","color:red;font-size:1px; left: 50%;top:34%;");
                            element_connstatus.setAttribute("style","color:red;left: -20%;top: -21%;font-size: 23px;");
                            element_daemonstatus.setAttribute("style","color:red;left: 11%;top: 337%;font-size: 23px;");
                            element_memoryrate.setAttribute("style","color:red;font-size:23px;top:95%;left:25%");
                            circle_chart_cpu(0);
                            circle_char_memory(0);
                            flag=0;
                        }else if(connstatus=="normal"){
                            if(flag==0){
                                //设置字体
                                element_connstatus.setAttribute("style","left:82%;top:-12%;color:green;font-size:23px;font-weight:500;");
                                element_cpurate.setAttribute("style","position: absolute; top: 33%;left:52%;color:green;font-size:20px;font-weight:500;");
                                element_memoryrate.setAttribute("style","position: absolute; top: 82%;left:104%;color:green;font-size:38px;font-weight:500;");
                                element_daemonstatus.setAttribute("style","left:119%;top:316%;color:green;font-size:23px;font-weight:500;");
                    
                                flag=1;
                            }
                            //connstatus
                            element_connstatus.innerHTML = connstatus;
                            angle1+=45;
                            if(angle1>360)angle1=45;
                            connrotatebox(angle1%360);
                            //cpurate
                            element_cpurate.innerHTML = data['cpu_rate']+"%";
                            //计算angle
                            angle=360*(data['cpu_rate']/50);
                            circle_chart_cpu(angle);
                            //memoryrate
                            element_memoryrate.innerHTML = data['memory_rate']+"%";
                            angle=360*(data['memory_rate']/50);
                            circle_char_memory(angle);
                            //daemonstatus
                            element_daemonstatus.innerHTML = data['daemon_status'];
                            daemonrotatebox(angle1%360);
                        }
                        
                       
                    },error(){
                        console.log("fail to request to get_serverstatus");
                    }
                })
            };
            function startTimer()
            {
                setInterval(Timerevent,500);
            }
            startTimer();

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
