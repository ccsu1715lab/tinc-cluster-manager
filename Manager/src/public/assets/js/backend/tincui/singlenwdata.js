define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts', '../tincui/singlenwdata.mock'], function ($, undefined, Backend, Table, Form, echarts, MockHandler) {

    var Controller = {
        index: function () {
            // 是否使用模拟数据 (开发/测试阶段设为 true, 生产环境设为 false)
            var USE_MOCK_DATA = true;
            
            // 初始化模拟数据处理器
            if (USE_MOCK_DATA) {
                MockHandler.init(true);
            }
            
            // 初始化变量
            var healthScoreChart = null;
            var disruptionTrendChart = null;
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
                        loadNetworkData(selectedNetworkId);
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
                        loadNetworkData(selectedNetworkId);
                    }
                });
                
                // 窗口大小改变时调整图表大小
                $(window).resize(function() {
                    if (healthScoreChart) healthScoreChart.resize();
                    if (disruptionTrendChart) disruptionTrendChart.resize();
                });
            }
            
            /**
             * 加载服务器列表
             */
            function loadServers() {
                $.ajax({
                    url: 'tincui/singlenwdata/getServers',
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        if (res.code === 1) {
                            var $select = $('#server-select');
                            $select.empty();
                            $select.append('<option value="">请选择服务器</option>');
                            console.log(res);
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
                    url: 'tincui/singlenwdata/getNetworks',
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
                                    $select.append('<option value="' + network.net_name + '">' + network.name + '</option>');
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
            function loadNetworkData(networkId) {
                $.ajax({
                    url: 'tincui/singlenwdata/getNetworkData',
                    type: 'GET',
                    data: { network_id: networkId },
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
                $('#avg-response-time').text(data.avg_response_time);
                $('#health-score').text(data.health_score);
                $('#upload-speed').text(data.traffic.upload);
                $('#download-speed').text(data.traffic.download);
                $('#avg-recovery-time').text(data.avg_recovery_time);
                
                // 初始化并更新图表
                initCharts();
                updateCharts(data);
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
                if (!healthScoreChart) {
                    healthScoreChart = echarts.init(document.getElementById('health-score-chart'));
                }
                
                if (!disruptionTrendChart) {
                    disruptionTrendChart = echarts.init(document.getElementById('disruption-trend-chart'));
                }
            }
            
            /**
             * 更新图表数据
             */
            function updateCharts(data) {
                // 健康评分趋势图（柱状图）
                var healthScoreOption = {
                    tooltip: {
                        trigger: 'axis',
                        formatter: '{b}: {c}'
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
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
                        axisLabel: {
                            formatter: '{value}'
                        }
                    },
                    series: [{
                        name: '健康评分',
                        type: 'bar',
                        data: data.health_score_trend.scores,
                        itemStyle: {
                            color: function(params) {
                                // 根据分数设置颜色
                                var score = params.value;
                                if (score >= 85) return '#67C23A'; // 绿色（好）
                                else if (score >= 60) return '#E6A23C'; // 橙色（一般）
                                else return '#F56C6C'; // 红色（差）
                            }
                        },
                        barWidth: '50%'
                    }]
                };
                
                // 网络中断趋势图（折线图）
                var disruptionTrendOption = {
                    tooltip: {
                        trigger: 'axis',
                        formatter: '{b}: {c} 次'
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
                    xAxis: {
                        type: 'category',
                        data: data.disruption_trend.dates
                    },
                    yAxis: {
                        type: 'value',
                        minInterval: 1,
                        axisLabel: {
                            formatter: '{value} 次'
                        }
                    },
                    series: [{
                        name: '中断次数',
                        type: 'line',
                        data: data.disruption_trend.counts,
                        smooth: true,
                        symbol: 'circle',
                        symbolSize: 8,
                        itemStyle: {
                            color: '#F56C6C'
                        },
                        lineStyle: {
                            width: 3,
                            color: '#F56C6C'
                        },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: 'rgba(245, 108, 108, 0.3)' },
                                { offset: 1, color: 'rgba(245, 108, 108, 0.1)' }
                            ])
                        }
                    }]
                };
                
                // 应用图表配置
                healthScoreChart.setOption(healthScoreOption);
                disruptionTrendChart.setOption(disruptionTrendOption);
            }
        }
    };
    return Controller;
});