define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts', '../tincui/singlenwdata.mock'], function ($, undefined, Backend, Table, Form, echarts, MockHandler) {

    var Controller = {
        index: function () {
            // 是否使用模拟数据 (开发/测试阶段设为 true, 生产环境设为 false)
          /*  var USE_MOCK_DATA = true;
            
            // 初始化模拟数据处理器
            if (USE_MOCK_DATA) {
                MockHandler.init(true);
            }*/
            
            // 初始化变量
            var charts = {
                responseTimeChart: null,
                healthScoreChart: null,
                healthScoreTrendChart: null,
                disruptionTrendChart: null,
                trafficChart: null,
                recoveryTrendChart: null
            };
            var selectedServerId = null;
            var selectedNetworkId = null;
            
            // 初始化事件处理
            initEventHandlers();
            
            // 加载服务器列表
            loadServers();
            
            /**
             * 初始化事件处理器
             */
            function initEventHandlers() {
                // 服务器选择改变事件
                $(document).on('change', '#server-select', function() {
                    var server_name = $(this).val();
                    selectedServerId = server_name;
                    
                    // 重置网络选择器
                    $('#network-select').empty().append('<option value="">请先选择服务器</option>').prop('disabled', true);
                    $('#confirm-btn').prop('disabled', true);
                    
                    if (server_name) {
                        // 加载该服务器下的网络列表
                        loadNetworks(server_name);
                    }
                });
                
                // 网络选择改变事件
                $(document).on('change', '#network-select', function() {
                    selectedNetworkId = $(this).val();
                    $('#confirm-btn').prop('disabled', !selectedNetworkId);
                });
                
                // 确认按钮点击事件
                $(document).on('click', '#confirm-btn', function() {
                    if (selectedServerId && selectedNetworkId) {
                        // 显示加载指示器，隐藏其他内容
                        $('#selection-screen').hide();
                        $('#loading-indicator').show();
                        
                        // 加载网络数据
                        loadNetworkData(selectedServerId,selectedNetworkId);
                    }
                });
                
                // 返回按钮点击事件
                $(document).on('click', '#return-btn', function() {
                    // 隐藏仪表板，显示选择界面
                    $('#dashboard-screen').hide();
                    $('#selection-screen').show();
                });
                
                // 刷新按钮点击事件
                $(document).on('click', '#refresh-btn', function() {
                    if (selectedNetworkId) {
                        // 显示加载指示器，隐藏仪表板
                        $('#dashboard-screen').hide();
                        $('#loading-indicator').show();
                        
                        // 重新加载网络数据
                        loadNetworkData(selectedServerId,selectedNetworkId);
                    }
                });
                
                // 窗口大小改变时调整图表大小
                $(window).resize(function() {
                    if (charts.responseTimeChart) charts.responseTimeChart.resize();
                    if (charts.healthScoreChart) charts.healthScoreChart.resize();
                    if (charts.healthScoreTrendChart) charts.healthScoreTrendChart.resize();
                    if (charts.disruptionTrendChart) charts.disruptionTrendChart.resize();
                    if (charts.trafficChart) charts.trafficChart.resize();
                    if (charts.recoveryTrendChart) charts.recoveryTrendChart.resize();
                });

                // Ping测试按钮点击事件
                $(document).on('click', '.ping-test-btn', function() {
                    var $btn = $(this);
                    var type = $btn.data('type');
                    var $card = $btn.closest('.dashboard-card');
                    var $timeSpan = $card.find('.update-time .time');
                    var url='tincui/singlenwdata/';
                    
                    // 禁用按钮并显示加载状态
                    $btn.prop('disabled', true);
                    $btn.html('<i class="fa fa-spinner fa-spin"></i> 测试中...');
                    
                    switch(type){
                        case 'response':
                            url += 'GetCurResTime';
                            break;
                        case 'health':
                            url += 'GetCurHealScore';
                            break;
                        case 'traffic':
                            url += 'GetCurTraffic';
                            break;
                    }
                    $.ajax({
                        url: url,
                        type: 'GET',
                        data: {server_name:selectedServerId,net_name:selectedNetworkId},
                        dataType: 'json',
                        success: function(res){
                            var res_data=JSON.parse(res);
                            if(res_data.code == 0){
                                // 更新数据显示
                                switch(type) {
                                    case 'response':
                                        $('#avg-response-time').text(res_data.response + 'ms');
                                        console.log(document.getElementById('response-time-chart'));
                                        updateResCircle(charts, res_data.response);
                                        break;
                                    case 'health':
                                        $('#health-score').text(res_data.response);
                                        console.log(document.getElementById('health-score-chart'));
                                        updateHealCircle(charts, res_data.response);
                                        break;
                                    case 'traffic':
                                        $('#upload-speed').text(JSON.parse(res_data.response).upload + 'Mbps');
                                        $('#download-speed').text(JSON.parse(res_data.response).download + 'Mbps');
                                        break;
                                }
                            }else{
                                Toastr.error(res_data.response || '获取数据失败');
                            }
                        },
                        error: function(){
                            Toastr.error('服务器连接失败，请检查网络');
                        }
                    });
                    
                    // 更新最后更新时间
                    var now = new Date();
                    var timeStr = now.getFullYear() + '-' + 
                                padZero(now.getMonth() + 1) + '-' + 
                                padZero(now.getDate()) + ' ' + 
                                padZero(now.getHours()) + ':' + 
                                padZero(now.getMinutes()) + ':' + 
                                padZero(now.getSeconds());
                    $timeSpan.text(timeStr);
                    
                    // 恢复按钮状态
                    $btn.prop('disabled', false);
                    $btn.html('<i class="fa fa-refresh"></i> Ping测试');
            
                    // 显示成功提示
                    Toastr.success('测试完成');
                });
                
                // 辅助函数：数字补零
                function padZero(num) {
                    return num < 10 ? '0' + num : num;
                }
            }
            
            /**
             * 加载服务器列表
             */
            function loadServers() {
                $.ajax({
                    url: 'tincui/singlenwdata/GetServers',
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        if (res.code === 1) {
                            var $select = $('#server-select');
                            $select.empty();
                            $select.append('<option value="">请选择服务器</option>');
                            $.each(res.data, function(i, server) {
                                $select.append('<option value="' + server.server_name + '">' + server.server_name + '</option>');
                            });
                        } else {
                            Toastr.error(res.msg || '加载服务器列表失败');
                        }
                    },
                    error: function() {
                        Toastr.error('服务器连接失败，请检查网络');
                    }
                });
            }
            
            /**
             * 加载网络列表
             */
            function loadNetworks(server_name) {
                $.ajax({
                    url: 'tincui/singlenwdata/GetNetworks',
                    type: 'GET',
                    data: { server_name: server_name },
                    dataType: 'json',
                    success: function(res) {
                        if (res.code === 1) {
                        
                            var $select = $('#network-select');
                            $select.empty();
                            
                            if (res.data.length === 0) {
                                $select.append('<option value="">此服务器下无内网</option>');
                                $select.prop('disabled', true);
                            } else {
                                $select.append('<option value="">请选择内网</option>');
                                
                                $.each(res.data, function(i, network) {
                                    $select.append('<option value="' + network.net_name + '">' + network.net_name + '</option>');
                                });
                                
                                // 启用选择框
                                $select.prop('disabled', false);
                            }
                        } else {
                            Toastr.error(res.msg || '加载内网列表失败');
                        }
                    },
                    error: function() {
                        Toastr.error('服务器连接失败，请检查网络');
                    }
                });
            }
            
            /**
             * 加载网络数据
             */
            function loadNetworkData(server_name,net_name) {
                $.ajax({
                    url: 'tincui/singlenwdata/GetNetworkData',
                    type: 'GET',
                    data: { server_name: server_name, net_name: net_name },
                    dataType: 'json',
                    success: function(res) {
                    
                        // 隐藏加载指示器
                        $('#loading-indicator').hide();
                        
                        if (res.code === 1) {
                            // 更新页面内容
                            updateDashboard(res.data);
                            
                            // 显示仪表板
                            $('#dashboard-screen').show();
                        } else {
                            // 显示错误，返回选择界面
                            Toastr.error(res.msg || '加载网络数据失败');
                            $('#selection-screen').show();
                        }
                    },
                    error: function() {
                        // 隐藏加载指示器，显示错误
                        $('#loading-indicator').hide();
                        $('#selection-screen').show();
                        Toastr.error('服务器连接失败，请检查网络');
                    }
                });
            }
            
            /**
             * 更新仪表板内容
             */
            function updateDashboard(data) {
                // 更新网络基本信息
                $('#current-server').text(data.server_name);
                $('#current-network').text(data.net_name);
                
                // 更新网络状态标签
                updateStatusBadge(data.status);
                
                // 更新卡片数据
                $('#avg-response-time').text(data.response_time);
                $('#health-score').text(data.health_score);
                $('#upload-speed').text(data.traffic.upload);
                $('#download-speed').text(data.traffic.download);
                $('#avg-recovery-time').text(data.avg_recovery_time);
                
                // 初始化并更新图表
                initCharts();
                updateCharts(charts, data);
                updateResCircle(charts, data.response_time);
                updateHealCircle(charts, data.health_score);
                updateTrafficCircle(charts, data.traffic.upload, data.traffic.download, data.max_bandwidth);
                updateRecoveryTrend(charts, data.recovery_trend);
            }
            
            /**
             * 更新状态标签
             */
            function updateStatusBadge(status) {
                var $badge = $('#network-status-badge');
                
                if (status === '在线') {
                    $badge.text('在线').removeClass('badge-danger badge-warning').addClass('badge-success');
                } else if (status === '离线') {
                    $badge.text('离线').removeClass('badge-success badge-warning').addClass('badge-danger');
                } else {
                    $badge.text(status).removeClass('badge-success badge-danger').addClass('badge-warning');
                }
            }
            
            /**
             * 初始化图表
             */
            function initCharts() {
                console.log('开始初始化图表...');
                
                // 响应时间圆环图
                var responseTimeChartDom = document.getElementById('response-time-chart');
                console.log('响应时间图表容器:', responseTimeChartDom);
                
                if (!charts.responseTimeChart && responseTimeChartDom) {
                    console.log('初始化响应时间图表...');
                    charts.responseTimeChart = echarts.init(responseTimeChartDom);
                    var responseTimeOption = {
                        series: [{
                            type: 'pie',
                            radius: ['70%', '90%'],
                            startAngle: 90,
                            label: {
                                show: false
                            },
                            data: [
                                {
                                    value: 50,
                                    itemStyle: {
                                        color: '#67C23A'
                                    }
                                },
                                {
                                    value: 50,
                                    itemStyle: {
                                        color: '#E6EBF8'
                                    }
                                }
                            ]
                        }]
                    };
                    console.log('设置响应时间图表配置...');
                    charts.responseTimeChart.setOption(responseTimeOption);
                }
                
                // 健康分数圆环图
                var healthScoreChartDom = document.getElementById('health-score-chart');
                console.log('健康分数图表容器:', healthScoreChartDom);
                
                if (!charts.healthScoreChart && healthScoreChartDom) {
                    console.log('初始化健康分数图表...');
                    charts.healthScoreChart = echarts.init(healthScoreChartDom);
                    var healthScoreOption = {
                        series: [{
                            type: 'pie',
                            radius: ['70%', '90%'],
                            startAngle: 90,
                            label: {
                                show: false
                            },
                            data: [
                                {
                                    value: 50,
                                    itemStyle: {
                                        color: '#67C23A'
                                    }
                                },
                                {
                                    value: 50,
                                    itemStyle: {
                                        color: '#E6EBF8'
                                    }
                                }
                            ]
                        }]
                    };
                    console.log('设置健康分数图表配置...');
                    charts.healthScoreChart.setOption(healthScoreOption);
                }
                
                // 流量圆环图
                var trafficChartDom = document.getElementById('traffic-chart');
                if (!charts.trafficChart && trafficChartDom) {
                    console.log('初始化流量图表...');
                    charts.trafficChart = echarts.init(trafficChartDom);
                    var trafficOption = {
                        series: [{
                            type: 'pie',
                            radius: ['70%', '90%'],
                            startAngle: 90,
                            label: {
                                show: false
                            },
                            data: [
                                {
                                    value: 50,
                                    itemStyle: {
                                        color: '#67C23A'
                                    }
                                },
                                {
                                    value: 50,
                                    itemStyle: {
                                        color: '#E6EBF8'
                                    }
                                }
                            ]
                        }]
                    };
                    charts.trafficChart.setOption(trafficOption);
                }
                
                // 恢复时间趋势图
                var recoveryTrendChartDom = document.getElementById('recovery-trend-chart');
                if (!charts.recoveryTrendChart && recoveryTrendChartDom) {
                    console.log('初始化恢复时间趋势图...');
                    charts.recoveryTrendChart = echarts.init(recoveryTrendChartDom);
                    var recoveryTrendOption = {
                        tooltip: {
                            trigger: 'axis',
                            formatter: function(params) {
                                var data = params[0].data;
                                return '故障时间: ' + data.startTime + '<br/>' + 
                                       '恢复用时: ' + data.duration + ' 分钟';
                            }
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '15%',
                            top: '10%',
                            containLabel: true
                        },
                        xAxis: {
                            type: 'category',
                            data: [],
                            axisLabel: {
                                interval: 0,
                                rotate: 45,
                                formatter: function(value) {
                                    // 更安全的日期格式处理
                                    var parts = value.split(' ');
                                    var datePart = parts[0] ? parts[0].substring(5) : '--';  // 从第5字符开始获取 MM-DD
                                    var timePart = parts[1] ? parts[1].substring(0, 5) : 'N/A'; // 截取前5个字符 HH:mm
                                    return datePart + '\n' + timePart;
                                }
                            }
                        },
                        yAxis: {
                            type: 'value',
                            name: '恢复时间(分钟)',
                            nameTextStyle: {
                                padding: [0, 0, 0, 30]
                            }
                        },
                        series: [{
                            type: 'bar',
                            data: [],
                            itemStyle: {
                                color: function(params) {
                                    // 根据恢复时间设置不同的颜色
                                    var duration = params.data.duration;
                                    return duration > 30 ? '#F56C6C' : 
                                           duration > 10 ? '#E6A23C' : '#67C23A';
                                }
                            },
                            barWidth: '60%',
                            label: {
                                show: true,
                                position: 'top',
                                formatter: '{c}分钟'
                            }
                        }]
                    };
                    charts.recoveryTrendChart.setOption(recoveryTrendOption);
                }
                
                // 健康分数趋势图
                if (!charts.healthScoreTrendChart) {
                    charts.healthScoreTrendChart = echarts.init(document.getElementById('health-score-trend-chart'));
                }
                
                // 网络中断趋势图
                if (!charts.disruptionTrendChart) {
                    charts.disruptionTrendChart = echarts.init(document.getElementById('disruption-trend-chart'));
                }
                
                console.log('图表初始化完成');
            }
            
            /**
             * 更新图表数据
             */
            function updateResCircle(charts, value) {
                console.log('更新响应时间图表:', value);
                var responseTimeValue = parseFloat(value) || 0;
                var MaxResponseTime = 500;
                var responseTimePercent = (MaxResponseTime-responseTimeValue)/MaxResponseTime * 100;
                if (charts.responseTimeChart) {
                    var color = responseTimePercent > 80 ? '#67C23A' : 
                               responseTimePercent > 50 ? '#E6A23C' : '#F56C6C';
                    console.log('响应时间百分比:', responseTimePercent, '颜色:', color);
                    charts.responseTimeChart.setOption({
                        series: [{
                            data: [
                                {
                                    value: responseTimePercent,
                                    itemStyle: {
                                        color: color
                                    }
                                },
                                {
                                    value: 100 - responseTimePercent,
                                    itemStyle: {
                                        color: '#E6EBF8'
                                    }
                                }
                            ]
                        }]
                    });
                }
            }

            function updateHealCircle(charts, value) {
                console.log('更新健康分数图表:', value);
                var healthScoreValue = parseFloat(value) || 0;
                if (charts.healthScoreChart) {
                    var color = healthScoreValue < 60 ? '#F56C6C' : 
                               healthScoreValue < 80 ? '#E6A23C' : '#67C23A';
                    console.log('健康分数:', healthScoreValue, '颜色:', color);
                    charts.healthScoreChart.setOption({
                        series: [{
                            data: [
                                {
                                    value: healthScoreValue,
                                    itemStyle: {
                                        color: color
                                    }
                                },
                                {
                                    value: 100 - healthScoreValue,
                                    itemStyle: {
                                        color: '#E6EBF8'
                                    }
                                }
                            ]
                        }]
                    });
                }
            }

            function updateTrafficCircle(charts, upload, download, maxBandwidth) {
                // 确保所有输入都是数字类型
                upload = parseFloat(upload) || 0;
                download = parseFloat(download) || 0;
                maxBandwidth = parseFloat(maxBandwidth) || 1000; // 默认最大带宽1000Mbps
                
                var totalTraffic = upload + download;
                var trafficPercent = (totalTraffic / maxBandwidth) * 100;
                
                if (charts.trafficChart) {
                    var color = trafficPercent > 80 ? '#F56C6C' : 
                               trafficPercent > 50 ? '#E6A23C' : '#67C23A';
                    charts.trafficChart.setOption({
                        series: [{
                            data: [
                                {
                                    value: trafficPercent,
                                    itemStyle: {
                                        color: color
                                    }
                                },
                                {
                                    value: 100 - trafficPercent,
                                    itemStyle: {
                                        color: '#E6EBF8'
                                    }
                                }
                            ]
                        }]
                    });
                    
                    // 更新总流量显示，确保是数字类型
                    $('#total-traffic').text(Number(totalTraffic).toFixed(1));
                    
                    // 更新上传下载速度显示
                    $('#upload-speed').text(upload.toFixed(1) + ' Mbps');
                    $('#download-speed').text(download.toFixed(1) + ' Mbps');
                }
            }

            function updateRecoveryTrend(charts, recoveryData) {
                if (charts.recoveryTrendChart) {
                    var dates = [];
                    var durations = [];
                    
                    // 确保数据按时间排序（使用新的date字段）
                    recoveryData.sort(function(a, b) {
                        return new Date(a.date) - new Date(b.date);
                    });
                    
                    recoveryData.forEach(function(item) {
                        dates.push(item.date);  // 使用date字段
                        durations.push({
                            value: item.duration,
                            date: item.date,    // 添加date字段到数据项
                            duration: item.duration
                        });
                    });
                    
                    charts.recoveryTrendChart.setOption({
                        xAxis: {
                            data: dates
                        },
                        series: [{
                            data: durations.map(function(d) {
                                // 保持数据格式兼容性
                                return {
                                    value: d.duration,
                                    startTime: d.date,  // 保持原字段用于tooltip
                                    duration: d.duration
                                };
                            })
                        }]
                    });
                }
            }

            function updateCharts(charts, data) {
                // 更新健康分数趋势图
                var healthScoreTrendOption = {
                    tooltip: {
                        trigger: 'axis'
                    },
                    xAxis: {
                        type: 'category',
                        data: data.health_score_trend.dates
                    },
                    yAxis: {
                        type: 'value',
                        min: 0,
                        max: 100
                    },
                    series: [{
                        data: data.health_score_trend.scores,
                        type: 'line',
                        smooth: true
                    }]
                };
                charts.healthScoreTrendChart.setOption(healthScoreTrendOption);
                
                // 更新网络中断趋势图
                var disruptionTrendOption = {
                    tooltip: {
                        trigger: 'axis'
                    },
                    xAxis: {
                        type: 'category',
                        data: data.disruption_trend.dates
                    },
                    yAxis: {
                        type: 'value'
                    },
                    series: [{
                        data: data.disruption_trend.counts,
                        type: 'line',
                        smooth: true
                    }]
                };
                charts.disruptionTrendChart.setOption(disruptionTrendOption);
            }
        }
    };
    return Controller;
});