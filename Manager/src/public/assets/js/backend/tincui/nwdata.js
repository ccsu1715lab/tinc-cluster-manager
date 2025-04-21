define(['jquery', 'bootstrap', 'backend', 'table', 'form'],function($,undefined,Backend,Table,Form){
    var Controller={
        index: function(){
            function initProgressCircle(elementId, percent, color) {
                var chart = echarts.init(document.getElementById(elementId));
                var option = {
                    series: [{
                        type: 'gauge',
                        startAngle: 90,
                        endAngle: -270,
                        radius: '100%',
                        pointer: {
                            show: false
                        },
                        progress: {
                            show: true,
                            overlap: false,
                            roundCap: true,
                            clip: false,
                            itemStyle: {
                                color: color
                            }
                        },
                        axisLine: {
                            lineStyle: {
                                width: 6,
                                color: [[1, '#f0f0f0']]
                            }
                        },
                        splitLine: {
                            show: false
                        },
                        axisTick: {
                            show: false
                        },
                        axisLabel: {
                            show: false
                        },
                        data: [{
                            value: percent,
                            name: '',
                            title: {
                                show: false
                            },
                            detail: {
                                show: false
                            }
                        }],
                        title: {
                            show: false
                        },
                        detail: {
                            show: false
                        },
                        animation: true
                    }]
                };
                chart.setOption(option);
                return chart;
            }
            
            // 初始化网络健康分数趋势图
            function initHealthChart() {
                var chart = echarts.init(document.getElementById('health-chart'));
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
                        min: 80,
                        max: 100
                    },
                    series: [{
                        name: '健康分数',
                        type: 'line',
                        smooth: true,
                        lineStyle: {
                            width: 3,
                            color: '#6236ff'
                        },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                {
                                    offset: 0,
                                    color: 'rgba(98, 54, 255, 0.3)'
                                },
                                {
                                    offset: 1,
                                    color: 'rgba(98, 54, 255, 0.1)'
                                }
                            ])
                        },
                        data: [92, 95, 89, 94, 96, 91, 93]
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
                        min: 0,
                        max: 6
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
            var totalCircle = initProgressCircle('total-circle', 90, '#6236ff');
            var onlineCircle = initProgressCircle('online-circle', 92, '#4caf50');
            var rateCircle = initProgressCircle('rate-circle', 91.5, '#2196f3');
            var recoveryCircle = initProgressCircle('recovery-circle', 65, '#ff9800');
            var healthChart = initHealthChart();
            var disruptionChart = initDisruptionChart();
            
            // 响应式调整
            window.addEventListener('resize', function() {
                totalCircle.resize();
                onlineCircle.resize();
                rateCircle.resize();
                recoveryCircle.resize();
                healthChart.resize();
                disruptionChart.resize();
            });
            
            // 加载实际数据（这里只是模拟，实际项目中应从后端获取）
            function loadNetworkStats() {
                $.ajax({
                    url: 'tincui/nwdata/index',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        // 更新卡片数据
                        updateCardData(data);
                        
                        // 更新健康分数图表
                        updateHealthChart(data);
                        
                        // 更新中断图表
                        updateDisruptionChart(data);
                    },
                    error: function(xhr) {
                        console.error('加载数据失败', xhr);
                    }
                });
            }
            
            // 更新卡片数据
            function updateCardData(data) {
                // 更新总网络数量
                $('.stat-card:eq(0) .value').text(data.total_networks);
                
                // 更新在线网络数量
                $('.stat-card:eq(1) .value').text(data.online_networks);
                
                // 更新网络在线率
                $('.stat-card:eq(2) .value').text(data.online_rate + '%');
                
                // 更新平均故障恢复时间
                $('.stat-card:eq(3) .value').html(data.avg_recovery_time + '<span style="font-size:18px;">分钟</span>');
                
                // 更新环形进度条
                totalCircle.setOption({
                    series: [{
                        data: [{ value: Math.min(data.total_networks/2, 100) }]
                    }]
                });
                
                onlineCircle.setOption({
                    series: [{
                        data: [{ value: Math.min(data.online_networks/2, 100) }]
                    }]
                });
                
                rateCircle.setOption({
                    series: [{
                        data: [{ value: data.online_rate }]
                    }]
                });
                
                recoveryCircle.setOption({
                    series: [{
                        data: [{ value: Math.max(0, 100 - data.avg_recovery_time * 5) }]
                    }]
                });
            }
            
            // 更新健康分数图表
            function updateHealthChart(data) {
                healthChart.setOption({
                    series: [{
                        data: data.health_scores
                    }]
                });
                
                // 更新健康分数统计
                $('.chart-card:eq(0) .info-item:eq(0) .info-value').text(data.health_max);
                $('.chart-card:eq(0) .info-item:eq(1) .info-value').text(data.health_min);
                $('.chart-card:eq(0) .info-item:eq(2) .info-value').text(data.health_avg);
            }
            
            // 更新中断图表
            function updateDisruptionChart(data) {
                disruptionChart.setOption({
                    series: [{
                        data: data.disruptions
                    }]
                });
                
                // 更新中断统计
                $('.chart-card:eq(1) .info-item:eq(0) .info-value').text(data.disruption_max);
                $('.chart-card:eq(1) .info-item:eq(1) .info-value').text(data.disruption_min);
                $('.chart-card:eq(1) .info-item:eq(2) .info-value').text(data.disruption_freq);
            }
            
            // 每30秒刷新一次数据
            loadNetworkStats();
            setInterval(loadNetworkStats, 3000);

        }
    };

    return Controller;
});