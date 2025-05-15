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
                nodeOnlineChart: null
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
                    if (charts.nodeOnlineChart) charts.nodeOnlineChart.resize();
                    if (charts.healthScoreChart) charts.healthScoreChart.resize();
                    if (charts.healthScoreTrendChart) charts.healthScoreTrendChart.resize();
                    if (charts.disruptionTrendChart) charts.disruptionTrendChart.resize();
                    if (charts.trafficChart) charts.trafficChart.resize();
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
                        case 'node-online':
                            url += 'GetNodeonlineRate';
                            $.ajax({
                                url: url,
                                type: 'GET',
                                data: {server_name:selectedServerId,net_name:selectedNetworkId},
                                dataType: 'json',
                                success: function(res){
                                    var res_data = res;
                                    if(res_data.code === 1){
                                        // 提取数据
                                        var onlineNodes = res_data.data.onlinenode || 0;
                                        var totalNodes = res_data.data.cntnode || 0;
                                        
                                        // 计算离线节点和在线率
                                        var offlineNodes = totalNodes - onlineNodes;
                                        var onlineRate = totalNodes > 0 ? Math.round((onlineNodes / totalNodes) * 100) : 0;
                                        
                                        // 更新节点在线率统计
                                        $('#online-nodes').text(onlineNodes);
                                        $('#offline-nodes').text(offlineNodes);
                                        $('#total-nodes').text(totalNodes);
                                        $('#node-online-rate').text(onlineRate + '%');
                                        $('#node-online-rate-value').text(onlineRate + '%');
                                        
                                        // 更新图表
                                        updateNodeOnlineChart(charts, onlineNodes, totalNodes);
                                    } else {
                                        Toastr.error(res_data.msg || '获取数据失败');
                                    }
                                },
                                error: function(){
                                    Toastr.error('服务器连接失败，请检查网络');
                                }
                            });
                            break;
                        case 'response':
                            url += 'GetCurResTime';
                            $.ajax({
                                url: url,
                                type: 'GET',
                                data: {server_name:selectedServerId,net_name:selectedNetworkId},
                                dataType: 'json',
                                success: function(res){
                                    var res_data = JSON.parse(res);
                                    if(res_data.code == 0){
                                        // 先获取当前显示的响应时间作为上次响应时间
                                        var currentResponseTime = $('#avg-response-time').text();
                                        var lastResponseTime = 0;
                                        
                                        // 如果当前已有响应时间，解析数值部分
                                        if (currentResponseTime && currentResponseTime !== '--') {
                                            // 提取数字部分
                                            lastResponseTime = parseFloat(currentResponseTime.replace(/[^0-9.]/g, ''));
                                        }
                                        
                                        // 获取新的响应时间
                                        var responseTimeValue = parseFloat(res_data.response) || 0;
                                        
                                        // 更新到上次响应时间显示
                                        $('#last-response-time').text(lastResponseTime + ' ms');
                                        
                                        // 设置上次响应时间颜色
                                        var lastResponseColor = lastResponseTime < 50 ? '#67C23A' : 
                                                             lastResponseTime < 100 ? '#E6A23C' : '#F56C6C';
                                        $('#last-response-time').css('color', lastResponseColor);
                                        
                                        // 更新本次响应时间
                                        $('#avg-response-time').text(responseTimeValue + ' ms');
                                        
                                        // 设置当前响应时间颜色
                                        var currentResponseColor = responseTimeValue < 50 ? '#67C23A' : 
                                                              responseTimeValue < 100 ? '#E6A23C' : '#F56C6C';
                                        $('#avg-response-time').css('color', currentResponseColor);
                                        
                                        // 设置最大延迟
                                        var maxDelay = 200;
                                        $('#max-delay').text(maxDelay + ' ms');
                                        
                                        // 计算响应时间得分
                                        var responseTimePercent = (maxDelay - responseTimeValue) / maxDelay * 100;
                                        responseTimePercent = Math.max(0, Math.min(100, responseTimePercent));
                                        var responseScore = Math.round(responseTimePercent);
                                        
                                        // 更新响应时间得分
                                        $('#response-score').text(responseScore);
                                        $('#response-time-score').text(responseScore);
                                        
                                        // 设置得分颜色
                                        var color = responseTimePercent > 80 ? '#67C23A' : 
                                                   responseTimePercent > 50 ? '#E6A23C' : '#F56C6C';
                                        $('#response-score').css('color', color);
                                        
                                        // 更新圆环图表
                                        updateResCircle(charts, responseTimeValue, lastResponseTime);
                                    } else {
                                        Toastr.error(res_data.response || '获取数据失败');
                                    }
                                },
                                error: function(){
                                    Toastr.error('服务器连接失败，请检查网络');
                                }
                            });
                            break;
                        case 'health':
                            url += 'GetCurHealScore';
                            break;
                        case 'traffic':
                            url += 'GetCurTraffic';
                            break;
                    }
                    
                    // 如果是非node-online和response类型的请求
                    if (type !== 'node-online' && type !== 'response') {
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
                                        case 'health':
                                            $('#health-score').text(res_data.response);
                                            console.log(document.getElementById('health-score-chart'));
                                            
                                            // 获取其他相关得分
                                            var trafficScore = res_data.traffic_score || 0;
                                            var responseScore = res_data.response_score || 0;
                                            var onlineScore = res_data.online_score || 0;
                                            
                                            // 如果API没有返回详细得分，尝试计算模拟得分
                                            if (!res_data.traffic_score) {
                                                // 流量得分(40%)、响应时间得分(40%)、在线得分(20%)
                                                var healthScore = parseFloat(res_data.response);
                                                // 根据总分反推各项得分（模拟）
                                                onlineScore = 100; // 假设在线
                                                // 假设流量和响应时间得分相同
                                                var remaining = healthScore - (onlineScore * 0.2);
                                                trafficScore = responseScore = Math.round(remaining / 0.8);
                                            }
                                            
                                            // 更新各项得分
                                            $('#traffic-score-value').text(trafficScore);
                                            $('#response-score-value').text(responseScore);
                                            $('#online-score-value').text(onlineScore);
                                            $('#total-health-score').text(res_data.response);
                                            
                                            updateHealCircle(charts, res_data.response, trafficScore, responseScore, onlineScore);
                                            break;
                                        case 'traffic':
                                            var trafficData = JSON.parse(res_data.response);
                                            $('#upload-speed').text(trafficData.upload + ' Mbps');
                                            $('#download-speed').text(trafficData.download + ' Mbps');
                                            
                                            // 计算总流量
                                            var totalTraffic = parseFloat(trafficData.upload) + parseFloat(trafficData.download);
                                            $('#total-traffic').text(totalTraffic.toFixed(1) + ' Mbps');
                                            
                                            // 更新最大带宽
                                            var maxBandwidth = parseFloat(trafficData.MaxRate) || 1000;
                                            $('#max-bandwidth').text(maxBandwidth.toFixed(0) + ' Mbps');
                                            
                                            // 更新图表和流量得分
                                            updateTrafficCircle(charts, trafficData.upload, trafficData.download, trafficData.MaxRate);
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
                    }
                    
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
                    $btn.html('<i class="fa fa-refresh"></i> ' + (type === 'response' ? 'Ping测试' : '测试'));
            
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
                $('#loading-indicator').show();
                console.log('正在加载网络数据...');
                
                $.ajax({
                    url: 'tincui/singlenwdata/GetNetworkData',
                    type: 'GET',
                    data: { server_name: server_name, net_name: net_name },
                    dataType: 'json',
                    success: function(res) {
                        // 隐藏加载指示器
                        $('#loading-indicator').hide();
                        
                        if (res.code === 1) {
                            console.log('网络数据加载成功:', res.data);
                            
                            // 初始化图表
                            initCharts();
                            
                            // 更新页面内容
                            updateDashboard(res.data);
                            
                            // 显示仪表板
                            $('#dashboard-screen').show();
                        } else {
                            // 显示错误，返回选择界面
                            console.error('加载网络数据失败:', res.msg);
                            Toastr.error(res.msg || '加载网络数据失败');
                            $('#selection-screen').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        // 隐藏加载指示器，显示错误
                        $('#loading-indicator').hide();
                        $('#selection-screen').show();
                        console.error('服务器连接失败:', xhr, status, error);
                        Toastr.error('服务器连接失败，请检查网络');
                    }
                });
            }
            
            /**
             * 更新仪表板内容
             */
            function updateDashboard(data) {
                console.log('更新仪表板内容...');
               
                // 更新网络基本信息
                $('#current-server').text(data.server_name);
                $('#current-network').text(data.net_name);
                
                // 更新网络状态标签
                updateStatusBadge(data.status);
                
                // 更新节点在线率指标 (默认数据)
                var onlineNodes = data.online_nodes || 0;
                var totalNodes = data.total_nodes || 0;
                
                // 计算离线节点数和在线率
                var offlineNodes = totalNodes - onlineNodes;
                var onlineRate = totalNodes > 0 ? Math.round((onlineNodes / totalNodes) * 100) : 0;
                
                // 更新节点在线率卡片数据
                $('#online-nodes').text(onlineNodes);
                $('#offline-nodes').text(offlineNodes);
                $('#total-nodes').text(totalNodes);
                $('#node-online-rate').text(onlineRate + '%');
                $('#node-online-rate-value').text(onlineRate + '%');
                
                // 设置在线率颜色
                var onlineRateColor = onlineRate >= 80 ? '#67C23A' :
                                    onlineRate >= 50 ? '#E6A23C' : '#F56C6C';
                $('#node-online-rate').css('color', onlineRateColor);
                
                // 更新响应时间指标
                var responseTime = parseFloat(data.response_time) || 0;
                var maxResponseTime = 200; // 最大响应时间（ms）
                
                // 计算响应时间得分
                var responseTimePercent = (maxResponseTime - responseTime) / maxResponseTime * 100;
                responseTimePercent = Math.max(0, Math.min(100, responseTimePercent));
                var responseScore = Math.round(responseTimePercent);
                
                // 设置响应时间颜色
                var responseColor = responseScore >= 80 ? '#67C23A' :
                                   responseScore >= 50 ? '#E6A23C' : '#F56C6C';
                
                // 设置当前响应时间颜色
                var currentResponseColor = responseTime < 50 ? '#67C23A' : 
                                        responseTime < 100 ? '#E6A23C' : '#F56C6C';
                
                // 更新响应时间卡片数据
                $('#avg-response-time').text(responseTime + ' ms');
                $('#avg-response-time').css('color', currentResponseColor);
                $('#last-response-time').text('0 ms'); // 初始无上次响应时间
                $('#max-delay').text(maxResponseTime + ' ms');
                $('#response-score').text(responseScore);
                $('#response-score').css('color', responseColor);
                $('#response-time-score').text(responseScore);
                
                // 更新健康分数指标
                var healthScore = parseFloat(data.health_score) || 0;
                var trafficScore = parseFloat(data.traffic_score) || 0;
                var responseScore = parseFloat(data.response_score) || 0;
                var onlineScore = parseFloat(data.online_score) || 0;
                
                // 设置健康分数颜色
                var healthColor = healthScore < 60 ? '#F56C6C' : 
                               healthScore < 80 ? '#E6A23C' : '#67C23A';
                
                $('#health-score').text(healthScore);
                $('#health-score').css('color', healthColor);
                $('#traffic-score-value').text(trafficScore);
                $('#response-score-value').text(responseScore);
                $('#online-score-value').text(onlineScore);
                $('#total-health-score').text(healthScore);
                
                // 更新流量卡片数据
                $('#upload-speed').text(data.traffic.upload);
                $('#download-speed').text(data.traffic.download);
                $('#total-traffic').text((parseFloat(data.traffic.upload) + parseFloat(data.traffic.download)).toFixed(1) + ' Mbps');
                $('#max-bandwidth').text(data.max_bandwidth);
                
                // 计算并显示流量得分
                var totalTraffic = parseFloat(data.traffic.upload) + parseFloat(data.traffic.download);
                var maxBandwidth = parseFloat(data.max_bandwidth) || 1000;
                var trafficPercent = (totalTraffic / maxBandwidth) * 100;
                var trafficScore = Math.round(100 - trafficPercent);
                trafficScore = Math.max(0, Math.min(100, trafficScore)); // 确保在0-100之间
                $('#traffic-score').text(trafficScore);
                
                // 设置得分颜色
                var scoreColor = trafficScore < 20 ? '#F56C6C' :
                                 trafficScore < 50 ? '#E6A23C' : '#67C23A';
                $('#traffic-score').css('color', scoreColor);
                
                // 确保趋势图数据格式正确
                console.log('处理趋势图数据...');
                
                // 检查健康分数趋势数据
                if (!data.health_score_trend || !data.health_score_trend.dates || !data.health_score_trend.scores) {
                    console.warn('健康分数趋势数据格式不正确，使用默认数据');
                    data.health_score_trend = {
                        dates: ['无数据'],
                        scores: [0]
                    };
                }
                
                // 检查网络中断趋势数据
                if (!data.disruption_trend || !data.disruption_trend.dates || !data.disruption_trend.counts) {
                    console.warn('网络中断趋势数据格式不正确，使用默认数据');
                    data.disruption_trend = {
                        dates: ['无数据'],
                        counts: [0]
                    };
                }
                
                // 更新图表
                updateNodeOnlineChart(charts, onlineNodes, totalNodes);
                updateHealCircle(charts, data.health_score, data.traffic_score, data.response_score, data.online_score);
                updateTrafficCircle(charts, data.traffic.upload, data.traffic.download, data.max_bandwidth);
                updateResCircle(charts, data.response_time, 0); // 初始无上次响应时间
                
                // 更新趋势图
                setTimeout(function() {
                    console.log('延迟更新趋势图...');
                    updateCharts(charts, data);
                }, 500); // 延迟500毫秒更新趋势图，确保DOM已经完全加载
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
                                        color: '#67C23A'  // 绿色，表示正常
                                    }
                                },
                                {
                                    value: 50,
                                    itemStyle: {
                                        color: '#E6EBF8'  // 灰色
                                    }
                                }
                            ]
                        }]
                    };
                    console.log('设置响应时间图表配置...');
                    charts.responseTimeChart.setOption(responseTimeOption);
                }
                
                // 节点在线率圆环图
                var nodeOnlineChartDom = document.getElementById('node-online-chart');
                console.log('节点在线率图表容器:', nodeOnlineChartDom);
                
                if (!charts.nodeOnlineChart && nodeOnlineChartDom) {
                    console.log('初始化节点在线率图表...');
                    charts.nodeOnlineChart = echarts.init(nodeOnlineChartDom);
                    var nodeOnlineOption = {
                        series: [{
                            type: 'pie',
                            radius: ['70%', '90%'],
                            startAngle: 90,
                            label: {
                                show: false
                            },
                            data: [
                                {
                                    value: 60,
                                    itemStyle: {
                                        color: '#67C23A'  // 绿色，表示在线
                                    }
                                },
                                {
                                    value: 40,
                                    itemStyle: {
                                        color: '#E6EBF8'  // 灰色
                                    }
                                }
                            ]
                        }]
                    };
                    console.log('设置节点在线率图表配置...');
                    charts.nodeOnlineChart.setOption(nodeOnlineOption);
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
                
                // 健康分数趋势图
                var healthScoreTrendChartDom = document.getElementById('health-score-trend-chart');
                if (!charts.healthScoreTrendChart && healthScoreTrendChartDom) {
                    console.log('初始化健康分数趋势图...');
                    charts.healthScoreTrendChart = echarts.init(healthScoreTrendChartDom);
                    // 设置一个默认配置，实际数据会在updateCharts中更新
                    var healthScoreTrendOption = {
                        tooltip: {
                            trigger: 'axis'
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '3%',
                            containLabel: true
                        },
                        xAxis: {
                            type: 'category',
                            data: ['加载中...']
                        },
                        yAxis: {
                            type: 'value',
                            min: 0,
                            max: 100
                        },
                        series: [{
                            data: [0],
                            type: 'bar',
                            barWidth: '60%'
                        }]
                    };
                    charts.healthScoreTrendChart.setOption(healthScoreTrendOption);
                }
                
                // 网络中断趋势图
                var disruptionTrendChartDom = document.getElementById('disruption-trend-chart');
                if (!charts.disruptionTrendChart && disruptionTrendChartDom) {
                    console.log('初始化网络中断趋势图...');
                    charts.disruptionTrendChart = echarts.init(disruptionTrendChartDom);
                    // 设置一个默认配置，实际数据会在updateCharts中更新
                    var disruptionTrendOption = {
                        tooltip: {
                            trigger: 'axis'
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '3%',
                            containLabel: true
                        },
                        xAxis: {
                            type: 'category',
                            data: ['加载中...']
                        },
                        yAxis: {
                            type: 'value'
                        },
                        series: [{
                            data: [0],
                            type: 'line',
                            smooth: true
                        }]
                    };
                    charts.disruptionTrendChart.setOption(disruptionTrendOption);
                }
                
                console.log('图表初始化完成');
            }
            
            /**
             * 更新图表数据
             */
            function updateResCircle(charts, value, lastValue) {
                console.log('更新响应时间图表:', value);
                // 确保所有输入都是数字类型
                var responseTimeValue = parseFloat(value) || 0;
                var lastResponseTimeValue = parseFloat(lastValue) || responseTimeValue;
                var MaxResponseTime = 200; // 最大延迟时间
                
                // 计算响应时间得分 (1-响应时间/最大响应时间)*100
                var responseTimePercent = (MaxResponseTime-responseTimeValue)/MaxResponseTime * 100;
                responseTimePercent = Math.max(0, Math.min(100, responseTimePercent)); // 确保在0-100之间
                
                if (charts.responseTimeChart) {
                    // 根据响应时间的百分比确定颜色
                    var color = responseTimePercent > 80 ? '#67C23A' : 
                               responseTimePercent > 50 ? '#E6A23C' : '#F56C6C';
                    console.log('响应时间百分比:', responseTimePercent, '颜色:', color);
                    
                    // 更新圆环图表
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
                    
                    // 更新响应时间数据
                    $('#avg-response-time').text(responseTimeValue + ' ms');
                    $('#last-response-time').text(lastResponseTimeValue + ' ms');
                    $('#max-delay').text(MaxResponseTime + ' ms');
                    
                    // 更新响应时间得分
                    var responseScore = Math.round(responseTimePercent);
                    $('#response-score').text(responseScore);
                    $('#response-time-score').text(responseScore);
                    
                    // 设置得分颜色
                    $('#response-score').css('color', color);
                    
                    // 更新DOM上各项数据的显示颜色
                    var currentResponseColor = responseTimeValue < 50 ? '#67C23A' : 
                                              responseTimeValue < 100 ? '#E6A23C' : '#F56C6C';
                    $('#avg-response-time').css('color', currentResponseColor);
                    
                    // 设置上次响应时间颜色
                    var lastResponseColor = lastResponseTimeValue < 50 ? '#67C23A' : 
                                          lastResponseTimeValue < 100 ? '#E6A23C' : '#F56C6C';
                    $('#last-response-time').css('color', lastResponseColor);
                }
            }

            function updateHealCircle(charts, value, trafficScore, responseScore, onlineScore) {
                console.log('更新健康分数图表:', value);
                // 确保所有参数都是数字
                var healthScoreValue = parseFloat(value) || 0;
                trafficScore = parseFloat(trafficScore) || 0;
                responseScore = parseFloat(responseScore) || 0;
                onlineScore = parseFloat(onlineScore) || 0;
                
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
                    
                    // 更新健康分数值
                    $('#health-score').text(healthScoreValue);
                    $('#health-score').css('color', color);
                    
                    // 更新各个得分
                    $('#traffic-score-value').text(trafficScore);
                    $('#response-score-value').text(responseScore);
                    $('#online-score-value').text(onlineScore);
                    $('#total-health-score').text(healthScoreValue);
                }
            }

            function updateTrafficCircle(charts, upload, download, maxBandwidth) {
                // 确保所有输入都是数字类型
                upload = parseFloat(upload) || 0;
                download = parseFloat(download) || 0;
                maxBandwidth = parseFloat(maxBandwidth) || 1000; // 默认最大带宽1000Mbps
                
                var totalTraffic = upload + download;
                var trafficPercent = (totalTraffic / maxBandwidth) * 100;
                // 计算流量得分（满分100分），当总速率越接近最大带宽，得分越低
                var trafficScore = Math.round(100 - trafficPercent);
                // 确保得分在0-100之间
                trafficScore = Math.max(0, Math.min(100, trafficScore));
                
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
                    $('#total-traffic').text(Number(totalTraffic).toFixed(1) + ' Mbps');
                    
                    // 更新上传下载速度显示
                    $('#upload-speed').text(upload.toFixed(1) + ' Mbps');
                    $('#download-speed').text(download.toFixed(1) + ' Mbps');
                    
                    // 更新最大带宽显示
                    $('#max-bandwidth').text(maxBandwidth.toFixed(0) + ' Mbps');
                    
                    // 更新流量得分显示
                    $('#traffic-score').text(trafficScore);
                    // 设置得分颜色
                    var scoreColor = trafficScore < 20 ? '#F56C6C' :
                                     trafficScore < 50 ? '#E6A23C' : '#67C23A';
                    $('#traffic-score').css('color', scoreColor);
                }
            }

            function updateCharts(charts, data) {
                console.log('开始更新趋势图表...');
                console.log('健康分数趋势数据:', data.health_score_trend);
                console.log('网络中断趋势数据:', data.disruption_trend);
                
                // 确保两个图表实例存在
                if (!charts.healthScoreTrendChart) {
                    console.log('初始化健康分数趋势图...');
                    var healthScoreTrendDom = document.getElementById('health-score-trend-chart');
                    if (healthScoreTrendDom) {
                        charts.healthScoreTrendChart = echarts.init(healthScoreTrendDom);
                    } else {
                        console.error('健康分数趋势图DOM元素不存在!');
                    }
                }
                
                if (!charts.disruptionTrendChart) {
                    console.log('初始化网络中断趋势图...');
                    var disruptionTrendDom = document.getElementById('disruption-trend-chart');
                    if (disruptionTrendDom) {
                        charts.disruptionTrendChart = echarts.init(disruptionTrendDom);
                    } else {
                        console.error('网络中断趋势图DOM元素不存在!');
                    }
                }
                
                // 确保数据格式正确
                if (!data.health_score_trend || !data.health_score_trend.dates || !data.health_score_trend.scores) {
                    console.error('健康分数趋势数据格式不正确:', data.health_score_trend);
                    // 创建默认数据
                    data.health_score_trend = {
                        dates: ['无数据'],
                        scores: [0]
                    };
                }
                
                if (!data.disruption_trend || !data.disruption_trend.dates || !data.disruption_trend.counts) {
                    console.error('网络中断趋势数据格式不正确:', data.disruption_trend);
                    // 创建默认数据
                    data.disruption_trend = {
                        dates: ['无数据'],
                        counts: [0]
                    };
                }
                
                // 更新健康分数趋势图
                if (charts.healthScoreTrendChart) {
                    var healthScoreTrendOption = {
                        tooltip: {
                            trigger: 'axis',
                            formatter: function(params) {
                                return params[0].name + '<br/>' + 
                                       '健康分数: ' + params[0].value;
                            }
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '3%',
                            top: '30px',
                            containLabel: true
                        },
                        xAxis: {
                            type: 'category',
                            data: data.health_score_trend.dates
                        },
                        yAxis: {
                            type: 'value',
                            min: 0,
                            max: 100,
                            name: '分数',
                            nameTextStyle: {
                                padding: [0, 0, 0, 30]
                            }
                        },
                        series: [{
                            name: '健康分数',
                            data: data.health_score_trend.scores,
                            type: 'bar',
                            barWidth: '60%',
                            itemStyle: {
                                color: function(params) {
                                    // 根据健康分数设置柱状图颜色
                                    var value = params.value;
                                    if (value >= 80) {
                                        return '#67C23A'; // 绿色
                                    } else if (value >= 60) {
                                        return '#E6A23C'; // 黄色
                                    } else {
                                        return '#F56C6C'; // 红色
                                    }
                                }
                            },
                            label: {
                                show: true,
                                position: 'top',
                                formatter: '{c}'
                            }
                        }]
                    };
                    console.log('设置健康分数趋势图配置...');
                    charts.healthScoreTrendChart.setOption(healthScoreTrendOption, true);
                    charts.healthScoreTrendChart.resize();
                }
                
                // 更新网络中断趋势图
                if (charts.disruptionTrendChart) {
                    var disruptionTrendOption = {
                        tooltip: {
                            trigger: 'axis',
                            formatter: function(params) {
                                return params[0].name + '<br/>' + 
                                       '中断次数: ' + params[0].value;
                            }
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '3%',
                            top: '30px',
                            containLabel: true
                        },
                        xAxis: {
                            type: 'category',
                            data: data.disruption_trend.dates
                        },
                        yAxis: {
                            type: 'value',
                            name: '次数',
                            nameTextStyle: {
                                padding: [0, 0, 0, 30]
                            }
                        },
                        series: [{
                            name: '中断次数',
                            data: data.disruption_trend.counts,
                            type: 'line',
                            smooth: true,
                            lineStyle: {
                                width: 3,
                                color: '#409EFF'
                            },
                            symbolSize: 8,
                            itemStyle: {
                                color: '#409EFF'
                            },
                            areaStyle: {
                                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                    { offset: 0, color: 'rgba(64, 158, 255, 0.7)' },
                                    { offset: 1, color: 'rgba(64, 158, 255, 0.1)' }
                                ])
                            }
                        }]
                    };
                    console.log('设置网络中断趋势图配置...');
                    charts.disruptionTrendChart.setOption(disruptionTrendOption, true);
                    charts.disruptionTrendChart.resize();
                }
                
                console.log('趋势图表更新完成');
            }

            function updateNodeOnlineChart(charts, onlineNodes, totalNodes) {
                // 确保输入是数字
                onlineNodes = parseInt(onlineNodes) || 0;
                totalNodes = parseInt(totalNodes) || 0; // 避免除以0
                
                // 计算在线率百分比
                var onlineRate = totalNodes==0?0:Math.round((onlineNodes / totalNodes) * 100);
                var offlineNodes = totalNodes - onlineNodes;
                
                // 确定颜色
                var color = onlineRate >= 80 ? '#67C23A' :  // 绿色
                           onlineRate >= 50 ? '#E6A23C' :  // 黄色
                           '#F56C6C';                      // 红色
                
                if (charts.nodeOnlineChart) {
                    charts.nodeOnlineChart.setOption({
                        series: [{
                            data: [
                                {
                                    value: onlineRate,
                                    itemStyle: {
                                        color: color
                                    }
                                },
                                {
                                    value: 100 - onlineRate,
                                    itemStyle: {
                                        color: '#E6EBF8'
                                    }
                                }
                            ]
                        }]
                    });
                    
                    // 更新显示数据
                    $('#online-nodes').text(onlineNodes);
                    $('#offline-nodes').text(offlineNodes);
                    $('#total-nodes').text(totalNodes);
                    $('#node-online-rate').text(onlineRate + '%');
                    $('#node-online-rate-value').text(onlineRate + '%');
                    
                    // 设置在线率文字颜色
                    $('#node-online-rate').css('color', color);
                }
            }
        }
    };
    return Controller;
});