define(['jquery', 'bootstrap', 'backend'], function ($, undefined, Backend) {
    var Controller = {
        index: function () {
            // Initialize charts
            var responseTimeChart = initMiniTrendChart('response-time-trend');
            var healthScoreMiniChart = initMiniTrendChart('health-score-mini-trend');
            var recoveryTimeChart = initMiniTrendChart('recovery-time-trend');
            var healthScoreChart = initHealthScoreChart();
            var disruptionTrendChart = initDisruptionTrendChart();

            // Load mock server data
            loadMockServers();

            // Server selection change event
            $('#server-select').on('change', function() {
                var serverId = $(this).val();
                if (serverId) {
                    // Enable network dropdown and load networks for the selected server
                    $('#network-select').prop('disabled', false);
                    loadMockNetworks(serverId);
                } else {
                    // Disable network dropdown if no server is selected
                    $('#network-select').prop('disabled', true);
                    $('#network-select').html('<option value="">请先选择服务器</option>');
                    $('#confirm-btn').prop('disabled', true);
                }
            });

            // Network selection change event
            $('#network-select').on('change', function() {
                var networkId = $(this).val();
                // Enable confirm button only if a network is selected
                $('#confirm-btn').prop('disabled', !networkId);
            });

            // Confirm button click event
            $('#confirm-btn').on('click', function() {
                var serverId = $('#server-select').val();
                var networkId = $('#network-select').val();
                var networkName = $('#network-select option:selected').text();
                
                if (serverId && networkId) {
                    // Show loading indicator
                    $('#dashboard-content').show();
                    $('#loading-indicator').show();
                    
                    // Fetch and display network statistics (using mock data for now)
                    setTimeout(function() {
                        loadMockNetworkStats(serverId, networkId, networkName);
                        $('#loading-indicator').hide();
                    }, 800); // Simulate loading delay
                }
            });

            // Function to initialize mini trend charts in the dashboard cards
            function initMiniTrendChart(elementId) {
                var chart = echarts.init(document.getElementById(elementId));
                var option = {
                    grid: {
                        top: 5,
                        right: 5,
                        bottom: 5,
                        left: 5
                    },
                    xAxis: {
                        type: 'category',
                        show: false,
                        data: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7']
                    },
                    yAxis: {
                        type: 'value',
                        show: false,
                        min: 'dataMin'
                    },
                    series: [{
                        data: [0, 0, 0, 0, 0, 0, 0],
                        type: 'line',
                        showSymbol: false,
                        lineStyle: {
                            width: 2,
                            color: '#409EFF'
                        },
                        areaStyle: {
                            color: {
                                type: 'linear',
                                x: 0,
                                y: 0,
                                x2: 0,
                                y2: 1,
                                colorStops: [{
                                    offset: 0, color: 'rgba(64, 158, 255, 0.3)'
                                }, {
                                    offset: 1, color: 'rgba(64, 158, 255, 0.1)'
                                }]
                            }
                        }
                    }]
                };
                chart.setOption(option);
                return chart;
            }

            // Function to initialize health score chart
            function initHealthScoreChart() {
                var chart = echarts.init(document.getElementById('health-score-chart'));
                var option = {
                    tooltip: {
                        trigger: 'axis',
                        formatter: '{b}: {c}'
                    },
                    grid: {
                        top: 40,
                        right: 20,
                        bottom: 40,
                        left: 50
                    },
                    xAxis: {
                        type: 'category',
                        data: getLast7Days(),
                        axisLabel: {
                            rotate: 0,
                            formatter: function(value) {
                                // Format to MM-DD
                                return value.substring(5);
                            }
                        }
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
                        name: '健康分数',
                        type: 'bar',
                        data: [0, 0, 0, 0, 0, 0, 0],
                        itemStyle: {
                            color: function(params) {
                                // Color based on score value
                                var score = params.value;
                                if (score >= 85) return '#67C23A'; // Good: Green
                                if (score >= 60) return '#E6A23C'; // Warning: Orange
                                return '#F56C6C'; // Bad: Red
                            }
                        },
                        barWidth: '40%'
                    }]
                };
                chart.setOption(option);
                return chart;
            }

            // Function to initialize disruption trend chart
            function initDisruptionTrendChart() {
                var chart = echarts.init(document.getElementById('disruption-trend-chart'));
                var option = {
                    tooltip: {
                        trigger: 'axis',
                        formatter: '{b}: {c} 次中断'
                    },
                    grid: {
                        top: 40,
                        right: 20,
                        bottom: 40,
                        left: 50
                    },
                    xAxis: {
                        type: 'category',
                        data: getLast7Days(),
                        axisLabel: {
                            rotate: 0,
                            formatter: function(value) {
                                // Format to MM-DD
                                return value.substring(5);
                            }
                        }
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
                        data: [0, 0, 0, 0, 0, 0, 0],
                        smooth: true,
                        symbol: 'circle',
                        symbolSize: 8,
                        lineStyle: {
                            width: 3,
                            color: '#F56C6C'
                        },
                        itemStyle: {
                            color: '#F56C6C'
                        }
                    }]
                };
                chart.setOption(option);
                return chart;
            }

            // Load mock data for servers dropdown
            function loadMockServers() {
                var mockServers = [
                    { id: 1, name: '服务器A（上海）' },
                    { id: 2, name: '服务器B（北京）' },
                    { id: 3, name: '服务器C（广州）' },
                    { id: 4, name: '服务器D（成都）' }
                ];
                
                var serverOptions = '<option value="">请选择服务器</option>';
                $.each(mockServers, function(index, server) {
                    serverOptions += '<option value="' + server.id + '">' + server.name + '</option>';
                });
                
                $('#server-select').html(serverOptions);
            }

            // Load mock networks for selected server
            function loadMockNetworks(serverId) {
                var mockNetworks = {
                    1: [
                        { id: 101, name: '研发部内网' },
                        { id: 102, name: '行政部内网' },
                        { id: 103, name: '财务部内网' }
                    ],
                    2: [
                        { id: 201, name: '销售部内网' },
                        { id: 202, name: '市场部内网' }
                    ],
                    3: [
                        { id: 301, name: '生产部内网' },
                        { id: 302, name: '质控部内网' },
                        { id: 303, name: '仓储部内网' }
                    ],
                    4: [
                        { id: 401, name: '技术支持内网' },
                        { id: 402, name: '客服部内网' }
                    ]
                };
                
                var networks = mockNetworks[serverId] || [];
                var networkOptions = '<option value="">请选择内网</option>';
                
                $.each(networks, function(index, network) {
                    networkOptions += '<option value="' + network.id + '">' + network.name + '</option>';
                });
                
                $('#network-select').html(networkOptions);
            }

            // Load mock network statistics
            function loadMockNetworkStats(serverId, networkId, networkName) {
                // Generate some random data for demonstration
                var mockData = generateMockData(serverId, networkId);
                
                // Update dashboard cards
                updateResponseTimeCard(mockData.avg_response_time, mockData.response_time_trend);
                updateHealthScoreCard(mockData.health_score, mockData.health_score_trend);
                updateTrafficCard(mockData.traffic.upload, mockData.traffic.download);
                updateRecoveryTimeCard(mockData.avg_recovery_time, mockData.recovery_time_trend);
                
                // Update charts
                updateHealthScoreChart(healthScoreChart, mockData.health_score_trend);
                updateDisruptionTrendChart(disruptionTrendChart, mockData.disruption_trend);
                
                // Update charts size to fit containers
                window.addEventListener('resize', function() {
                    responseTimeChart.resize();
                    healthScoreMiniChart.resize();
                    recoveryTimeChart.resize();
                    healthScoreChart.resize();
                    disruptionTrendChart.resize();
                });
            }

            // Generate mock data for network statistics
            function generateMockData(serverId, networkId) {
                // Generate random data but make it somewhat consistent based on serverId and networkId
                var seed = parseInt(serverId.toString() + networkId.toString());
                var rand = function(min, max) {
                    var rnd = Math.sin(seed++) * 10000;
                    rnd = rnd - Math.floor(rnd);
                    return Math.floor(rnd * (max - min + 1) + min);
                };
                
                // Response time trend (last 7 days)
                var responseTimes = [];
                for (var i = 0; i < 7; i++) {
                    responseTimes.push(rand(20, 80));
                }
                
                // Health score trend (last 7 days)
                var healthScores = [];
                for (var i = 0; i < 7; i++) {
                    healthScores.push(rand(60, 95));
                }
                
                // Recovery time trend (last 7 days)
                var recoveryTimes = [];
                for (var i = 0; i < 7; i++) {
                    recoveryTimes.push(rand(5, 35));
                }
                
                // Disruption trend (last 7 days)
                var disruptions = [];
                for (var i = 0; i < 7; i++) {
                    disruptions.push(rand(0, 5));
                }
                
                return {
                    avg_response_time: Math.round(responseTimes.reduce((a, b) => a + b, 0) / responseTimes.length),
                    response_time_trend: responseTimes,
                    health_score: Math.round(healthScores.reduce((a, b) => a + b, 0) / healthScores.length),
                    health_score_trend: healthScores,
                    traffic: {
                        upload: (rand(10, 50) / 10).toFixed(1),
                        download: (rand(30, 80) / 10).toFixed(1)
                    },
                    avg_recovery_time: Math.round(recoveryTimes.reduce((a, b) => a + b, 0) / recoveryTimes.length),
                    recovery_time_trend: recoveryTimes,
                    disruption_trend: disruptions
                };
            }

            // Update response time card with data
            function updateResponseTimeCard(avgTime, trend) {
                $('#avg-response-time').html(avgTime + '<span style="font-size:18px;">ms</span>');
                responseTimeChart.setOption({
                    series: [{
                        data: trend
                    }]
                });
            }

            // Update health score card with data
            function updateHealthScoreCard(score, trend) {
                $('#health-score').text(score);
                healthScoreMiniChart.setOption({
                    series: [{
                        data: trend
                    }]
                });
            }

            // Update traffic card with data
            function updateTrafficCard(upload, download) {
                var total = (parseFloat(upload) + parseFloat(download)).toFixed(1);
                $('#total-traffic').html(total + '<span style="font-size:18px;">MB/s</span>');
                $('#upload-traffic').text(upload + ' MB/s');
                $('#download-traffic').text(download + ' MB/s');
            }

            // Update recovery time card with data
            function updateRecoveryTimeCard(avgTime, trend) {
                $('#avg-recovery-time').html(avgTime + '<span style="font-size:18px;">分钟</span>');
                recoveryTimeChart.setOption({
                    series: [{
                        data: trend
                    }]
                });
            }

            // Update health score chart with data
            function updateHealthScoreChart(chart, data) {
                chart.setOption({
                    series: [{
                        data: data
                    }]
                });
            }

            // Update disruption trend chart with data
            function updateDisruptionTrendChart(chart, data) {
                chart.setOption({
                    series: [{
                        data: data
                    }]
                });
            }

            // Get formatted dates for the last 7 days
            function getLast7Days() {
                var result = [];
                for (var i = 6; i >= 0; i--) {
                    var d = new Date();
                    d.setDate(d.getDate() - i);
                    result.push(formatDate(d));
                }
                return result;
            }

            // Format date to YYYY-MM-DD
            function formatDate(date) {
                var year = date.getFullYear();
                var month = ('0' + (date.getMonth() + 1)).slice(-2);
                var day = ('0' + date.getDate()).slice(-2);
                return year + '-' + month + '-' + day;
            }
        }
    };
    return Controller;
}); 