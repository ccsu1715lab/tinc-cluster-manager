define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts', 'echarts-theme'], function ($, undefined, Backend, Table, Form, Echarts) {
    var GetOnlinenodedata_url = 'tincui/Requestprocess/GetOnlinenodedata';
    var GetnetnameByServer_url = 'tincui/Requestprocess/GetnetnameByServername';
    var Internal_Service_Error = "Internal Service Error：params cannot be null";
    var Updatenodeonlinestatus_url = 'tincui/Requestprocess/updatenodeonlinestatus';
    var RquestError = "fail to request url：";
    var Controller = {
        index: function () {
            //初始化图标的横坐标             
            var hourline = ['0:00','1:00','2:00','3:00','4:00','5:00','6:00','7:00','8:00','9:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00','23:00'];
            //定时器
            var timer = null;
            var timer2 = null;
            //全局变量
            var Nodeonlinedata=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];

            //获取指定内网的在线节点数据
            function GetonlineNodedata(servername,netname)
            {
                if(servername==null||netname==null)
                {
                    console.log(Internal_Service_Error);
                }
                $.ajax({
                    url: GetOnlinenodedata_url,
                    type: 'post',
                    data: {servername,netname},
                    datatype: 'text',
                    success:function(data)
                    {
                        console.log("servername："+servername);
                        console.log("netname："+netname);
                        console.log(data);
                        Nodeonlinedata = data;

                    },
                    error()
                    {
                        console.log(RquestError+GetOnlinenodedata_url);                    
                    }
                })
            }

            //启动定时器
            function StartTimer()
            {
                if(!timer)
                {
                    timer=setInterval(UpdateNodeonlineData,3000);
                }
            }

            //更新节点在线信息
            function Startimer2()
            {
                if(!timer2)
                {
                    timer2=setInterval(UpdateNodeonlinestatus,3000);
                }

            }

            //关闭定时器    
            function StopTimer()
            {
                 if(timer)
                 {
                    clearInterval(timer);
                    timer=null;
                 }
            }

            function StopTimer2()
            {
                if(timer2)
                {
                    clearInterval(timer2);
                    timer2=null;
                }
            }
            
            //生成图表
            function generate_mychar()
            {
                var myChart = Echarts.init(document.getElementById('chart'));
                // 配置图表选项和数据
                var option = {
                title: {
                text: '今日节点上线状况',
                textStyle: {
                    color: '#fff'
                }
    
                },
                tooltip: {},
                xAxis: {
                    axisLabel: {
                        textStyle: {
                            color: '#fff'
                        }
                    },
                data: hourline,
             
                },
                yAxis: {
                  axisLabel: {
                    textStyle:{
                        color: '#fff'
                    }
                  }
                },
                series: [{
                name: '上线节点数',
                type: 'line',
                data: Nodeonlinedata
                }]
                };
                myChart.setOption(option);    
            }

            //定时事务1
            function UpdateNodeonlineData()
            {
                //获取在线节点数据
                var servername = $("#c-server_name").val();
                var netname = $("#c-net_name").val();
                if(servername==null||netname==null)
                {
                    console.log("Internal Server Error:params cannot be null");
                }
                GetonlineNodedata(servername,netname);
                console.log("nowdata："+Nodeonlinedata); 
                //利用新数据生成图标
                generate_mychar();   

            }

            //定时事务2，自动更新在线节点状况
            function UpdateNodeonlinestatus()
            {
                $.ajax(
                    {
                        url:Updatenodeonlinestatus_url,
                        type:'post',
                        data:null,
                        data_type:'text',
                        success:function(){
                            console.log("success to request to"+Updatenodeonlinestatus_url)
                        },
                        error()
                        {
                            console.log("fail to request to"+Updatenodeonlinestatus_url);
                        }
                        
                    
                    })
            }

            //根据服务器名自动填充内网
            $(document).ready(function(){
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
                            select.options.length=2;
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
            })

            //初始定时启动按钮            
            $(document).ready(function(){
                $("#confirm").click(function(){
                    var servername = $("#c-server_name").val();
                    var netname = $("#c-net_name").val();
                    if(servername=='init'||(servername=="init"&&netname=="init")||(netname=='init'&&servername!='all'))
                    {
                        alert("非法的查询");
                    }
                    else{
                        //停止前面的定时器
                        StopTimer();
                        //开启新的定时器
                        StartTimer();
                    }
        


                })
            });

            //启动定时器2
           // Startimer2();
                        
           
        
        },
       
    };
    return Controller;
});
