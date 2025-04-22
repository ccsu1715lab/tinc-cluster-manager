define(['jquery', 'bootstrap', 'backend', 'table', 'form'],function($,undefined,Backend,Table,Form){
    var Controller={
        index: function(){
            // 初始化网络在线率圆环图
            function initOnlineRateChart() {
                var chart = echarts.init(document.getElementById('online-rate-chart'));
                var option = {
                    series: [{
                        type: 'pie',
                        radius: ['60%', '80%'],
                        avoidLabelOverlap: false,
                        label: {
                            show: true,
                            position: 'center',
                            formatter: '{c}%',
                            fontSize: 28,
                            fontWeight: 'bold',
                            color: '#2196f3',
                            lineHeight: 36
                        },
                        emphasis: {
                            label: {
                                show: true,
                                fontSize: 30,
                                fontWeight: 'bold'
                            }
                        },
                        data: [{
                            value: 91.5,
                            name: '在线率',
                            itemStyle: {
                                color: '#2196f3'
                            }
                        }, {
                            value: 8.5,
                            name: '离线率',
                            itemStyle: {
                                color: '#f0f0f0'
                            }
                        }]
                    }]
                };
                chart.setOption(option);
                return chart;
            }
            
            // 初始化响应时间趋势图
            function initResponseTimeChart() {
                var chart = echarts.init(document.getElementById('response-time-chart'));
                var option = {
                    grid: {
                        left: '0',
                        right: '0',
                        bottom: '0',
                        top: '10',
                        containLabel: false
                    },
                    xAxis: {
                        type: 'category',
                        boundaryGap: false,
                        show: false,
                        data: ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
                    },
                    yAxis: {
                        type: 'value',
                        show: false
                    },
                    series: [{
                        name: '响应时间',
                        type: 'line',
                        smooth: true,
                        symbol: 'none',
                        sampling: 'average',
                        itemStyle: {
                            color: '#6236ff'
                        },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                                offset: 0,
                                color: 'rgba(98, 54, 255, 0.3)'
                            }, {
                                offset: 1,
                                color: 'rgba(98, 54, 255, 0.1)'
                            }])
                        },
                        data: [45, 42, 38, 43, 40, 47, 43]
                    }]
                };
                chart.setOption(option);
                return chart;
            }
            
            // 初始化健康分数仪表盘
            function initHealthScoreGauge() {
                var chart = echarts.init(document.getElementById('health-score-gauge'));
                var option = {
                    grid: {
                        left: '0',
                        right: '0',
                        bottom: '0',
                        top: '0',
                        containLabel: false
                    },
                    series: [{
                        type: 'gauge',
                        radius: '100%',
                        startAngle: 180,
                        endAngle: 0,
                        min: 0,
                        max: 100,
                        progress: {
                            show: true,
                            width: 8
                        },
                        axisLine: {
                            lineStyle: {
                                width: 8,
                                color: [
                                    [0.6, '#f44336'],
                                    [0.8, '#ff9800'],
                                    [1, '#4caf50']
                                ]
                            }
                        },
                        axisTick: {
                            show: false
                        },
                        splitLine: {
                            show: false
                        },
                        axisLabel: {
                            show: false
                        },
                        anchor: {
                            show: false
                        },
                        pointer: {
                            show: false
                        },
                        detail: {
                            show: false
                        },
                        data: [{
                            value: 92.8
                        }]
                    }]
                };
                chart.setOption(option);
                return chart;
            }
            
            // 初始化故障恢复时间趋势图
            function initRecoveryTimeChart() {
                var chart = echarts.init(document.getElementById('recovery-time-chart'));
                var option = {
                    grid: {
                        left: '0',
                        right: '0',
                        bottom: '0',
                        top: '10',
                        containLabel: false
                    },
                    xAxis: {
                        type: 'category',
                        boundaryGap: false,
                        show: false,
                        data: ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
                    },
                    yAxis: {
                        type: 'value',
                        show: false
                    },
                    series: [{
                        name: '恢复时间',
                        type: 'line',
                        smooth: true,
                        symbol: 'none',
                        sampling: 'average',
                        itemStyle: {
                            color: '#ff9800'
                        },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                                offset: 0,
                                color: 'rgba(255, 152, 0, 0.3)'
                            }, {
                                offset: 1,
                                color: 'rgba(255, 152, 0, 0.1)'
                            }])
                        },
                        data: [7, 10, 8, 6, 9, 12, 7]
                    }]
                };
                chart.setOption(option);
                return chart;
            }
            
            // 初始化网络中断次数趋势图
            function initDisruptionChart() {
                var chart = echarts.init(document.getElementById('disruption-chart'));
                var option = {
                    tooltip: {
                        trigger: 'axis'
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        top: '3%',
                        containLabel: true
                    },
                    xAxis: {
                        type: 'category',
                        boundaryGap: false,
                        data: ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
                    },
                    yAxis: {
                        type: 'value',
                        min: 0
                    },
                    series: [{
                        name: '中断次数',
                        type: 'line',
                        smooth: true,
                        symbol: 'circle',
                        symbolSize: 8,
                        lineStyle: {
                            width: 3,
                            color: '#2196f3'
                        },
                        itemStyle: {
                            color: '#2196f3'
                        },
                        data: [3, 1, 5, 2, 0, 1, 2]
                    }]
                };
                chart.setOption(option);
                return chart;
            }
            
            // 初始化所有图表
            var onlineRateChart = initOnlineRateChart();
            var responseTimeChart = initResponseTimeChart();
            var healthScoreGauge = initHealthScoreGauge();
            var recoveryTimeChart = initRecoveryTimeChart();
            var disruptionChart = initDisruptionChart();
            
            // 响应式调整
            window.addEventListener('resize', function() {
                onlineRateChart.resize();
                responseTimeChart.resize();
                healthScoreGauge.resize();
                recoveryTimeChart.resize();
                disruptionChart.resize();
            });
            
            // 健康分数等级评估
            function getHealthRating(score) {
                if (score >= 90) {
                    return {text: '健康状态优秀', class: 'badge-success'};
                } else if (score >= 80) {
                    return {text: '健康状态良好', class: 'badge-success'};
                } else if (score >= 70) {
                    return {text: '健康状态一般', class: 'badge-warning'};
                } else if (score >= 60) {
                    return {text: '健康状态较差', class: 'badge-warning'};
                } else {
                    return {text: '健康状态危险', class: 'badge-danger'};
                }
            }
            
            // 加载实际数据
            function loadNetworkStats() {
                $.ajax({
                    url: 'tincui/nwdata/index',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        // 更新网络状态卡片
                        updateNetworkStatusCard(data.network_status);
                        
                        // 更新响应时间卡片
                        updateResponseTimeCard(data.avg_response_time);
                        
                        // 更新健康分数卡片
                        updateHealthScoreCard(data.health_score);
                        
                        // 更新故障恢复时间卡片
                        updateRecoveryTimeCard(data.avg_recovery_time,data.recovery_time_trend);
                        
                        // 更新中断趋势图
                        updateDisruptionChart(data.disruption_trend);
                    },
                    error: function(xhr) {
                        console.error('加载数据失败', xhr);
                    }
                });
            }
            
            // 更新网络状态卡片
            function updateNetworkStatusCard(data) {
                // 添加调试代码，确认接收到的是在线率
                
                $('#total-networks').text(data.total_networks);
                $('#online-networks').text(data.online_networks);
                
                // 计算在线率并显示
                var onlineRate = data.online_rate;
                var offlineRate = 100 - onlineRate;
                
                // 更新圆环图
                onlineRateChart.setOption({
                    series: [{
                        data: [{
                            value: onlineRate,
                            name: '在线率',
                            itemStyle: {
                                color: '#2196f3'
                            }
                        }, {
                            value: offlineRate,
                            name: '离线率',
                            itemStyle: {
                                color: '#f0f0f0'
                            }
                        }],
                        label: {
                            show: true,
                            position: 'center',
                            formatter: onlineRate + '%',
                            fontSize: 28,
                            fontWeight: 'bold',
                            color: '#2196f3',
                            lineHeight: 36
                        },
                    }]
                });
                
                // 添加额外显示
                var rateText = onlineRate.toFixed(1) + '%';
                $('#online-rate-text').text(rateText);
            }
            
            // 更新响应时间卡片
            function updateResponseTimeCard(avgResponseTime) {
                $('#avg-response-time').html(avgResponseTime + '<span style="font-size:18px;">ms</span>');
                
                // 这里可以添加模拟的响应时间趋势数据
                // 实际项目中可以从后端获取详细数据
                var trendData = [
                    Math.round(avgResponseTime * 0.9),
                    Math.round(avgResponseTime * 1.1),
                    Math.round(avgResponseTime * 0.95),
                    Math.round(avgResponseTime),
                    Math.round(avgResponseTime * 1.05),
                    Math.round(avgResponseTime * 0.9),
                    Math.round(avgResponseTime * 1.0)
                ];
                
                responseTimeChart.setOption({
                    series: [{
                        data: trendData
                    }]
                });
            }
            
            // 更新健康分数卡片
            function updateHealthScoreCard(healthScore) {
                $('#health-score').text(healthScore);
                
                // 更新健康评级
                var rating = getHealthRating(healthScore);
                $('.health-rating .rating-text').text(rating.text).removeClass().addClass('rating-text ' + rating.class);
                
                // 更新仪表盘
                healthScoreGauge.setOption({
                    series: [{
                        data: [{
                            value: healthScore
                        }]
                    }]
                });
            }
            
            // 更新故障恢复时间卡片
            function updateRecoveryTimeCard(avgRecoveryTime,RecoveryTimeTrend) {
                $('#avg-recovery-time').html(avgRecoveryTime + '<span style="font-size:18px;">分钟</span>');
                
                // 这里可以添加模拟的恢复时间趋势数据
                // 实际项目中可以从后端获取详细数据
                console.log(RecoveryTimeTrend);
                var trendData = [
                    Math.round(RecoveryTimeTrend[0]),
                    Math.round(RecoveryTimeTrend[1]),
                    Math.round(RecoveryTimeTrend[2]),
                    Math.round(RecoveryTimeTrend[3]),
                    Math.round(RecoveryTimeTrend[4]),
                    Math.round(RecoveryTimeTrend[5]),
                    Math.round(RecoveryTimeTrend[6])
                ];
                
                recoveryTimeChart.setOption({
                    series: [{
                        data: trendData
                    }]
                });
            }
            
            // 更新中断图表
            function updateDisruptionChart(data) {
                // 获取过去7天的日期标签
                var dateLabels = [];
                for (var i = 6; i >= 0; i--) {
                    var date = new Date();
                    date.setDate(date.getDate() - i);
                    dateLabels.push((date.getMonth() + 1) + '/' + date.getDate());
                }
                
                disruptionChart.setOption({
                    xAxis: {
                        data: dateLabels
                    },
                    series: [{
                        data: data.disruptions
                    }]
                });
                
                // 更新中断统计信息
                $('#disruption-max').text(data.max);
                $('#disruption-min').text(data.min);
                $('#disruption-freq').text(data.freq);
            }
            
            // 首次加载数据
            loadNetworkStats();
            
            // 每60秒自动刷新一次
            setInterval(loadNetworkStats, 60000);
        }
    };
    return Controller;
});