/**
 * 模拟数据处理器 - 用于前端测试
 * 
 * 此文件提供模拟的后端数据响应，用于前端开发和测试阶段
 * 在实际生产环境中，这些模拟数据会被真实的后端API响应替代
 */
define(['jquery'], function ($) {
    var MockHandler = {
        /**
         * 初始化模拟数据处理
         * 
         * @param {boolean} useMock 是否使用模拟数据
         */
        init: function(useMock) {
            if (!useMock) return;
            
            // 拦截AJAX请求并返回模拟数据
            this.setupAjaxInterceptor();
        },
        
        /**
         * 设置AJAX请求拦截器
         */
        setupAjaxInterceptor: function() {
            // 保存原始的jQuery ajax方法
            var originalAjax = $.ajax;
            
            // 重写ajax方法以拦截请求
            $.ajax = function(options) {
                var url = options.url || '';
                
                // 根据URL路径返回对应的模拟数据
                if (url.indexOf('singlenwdata/getServers') > -1) {
                    MockHandler.mockResponse(options, MockHandler.getServersData());
                    return;
                }
                
                if (url.indexOf('singlenwdata/getNetworks') > -1) {
                    var serverId = options.data && options.data.server_name;
                    MockHandler.mockResponse(options, MockHandler.getNetworksData(serverId));
                    return;
                }
                
                // 如果不是模拟的URL，则使用原始的ajax方法
                return originalAjax.apply(this, arguments);
            };
        },
        
        /**
         * 模拟AJAX响应
         * 
         * @param {Object} options AJAX选项
         * @param {Object} data 响应数据
         */
        mockResponse: function(options, data) {
            // 模拟网络延迟
            setTimeout(function() {
                if (options.success) {
                    options.success(data);
                }
            }, 500);
        },
        
        /**
         * 获取服务器列表模拟数据
         * 
         * @returns {Object} 模拟的服务器列表响应
         */
        getServersData: function() {
            return {
                code: 1,
                msg: '获取成功',
                data: [
                    { id: 1, server_name: '上海服务器A' },
                    { id: 2, server_name: '北京服务器B' },
                    { id: 3, server_name: '广州服务器C' },
                    { id: 4, server_name: '成都服务器D' }
                ]
            };
        },
        
        /**
         * 获取网络列表模拟数据
         * 
         * @param {number} serverId 服务器ID
         * @returns {Object} 模拟的网络列表响应
         */
        getNetworksData: function(server_name) {
            var networksByServer = {
                '上海服务器A': [
                    { id: 101, net_name: '研发部内网' },
                    { id: 102, net_name: '行政部内网' },
                    { id: 103, net_name: '财务部内网' }
                ],
                '北京服务器B': [
                    { id: 201, net_name: '销售部内网' },
                    { id: 202, net_name: '市场部内网' }
                ],
                '广州服务器C': [
                    { id: 301, net_name: '生产部内网' },
                    { id: 302, net_name: '质控部内网' }
                ],
                '成都服务器D': [
                    { id: 401, net_name: '客服部内网' },
                    { id: 402, net_name: '技术支持内网' }
                ]
            };
            
            return {
                code: 1,
                msg: '获取成功',
                data: networksByServer[server_name] || []
            };
        },
        
        /**
         * 获取网络数据模拟数据
         * 
         * @param {number} networkId 网络ID
         * @returns {Object} 模拟的网络数据响应
         */
        getNetworkData: function(server_name,net_name) {
            // 使用networkId作为随机种子，使得相同的网络每次生成的数据相似
            var seed = parseInt(net_name);
            var rand = function(min, max) {
                var rnd = Math.sin(seed++) * 10000;
                rnd = rnd - Math.floor(rnd);
                return Math.floor(rnd * (max - min + 1) + min);
            };
            
            // 生成服务器和网络信息
            var serverMap = {
                1: '上海服务器A',
                2: '北京服务器B',
                3: '广州服务器C',
                4: '成都服务器D'
            };
            
            var networkInfo = {
                101: { serverId: 1, name: '研发部内网' },
                102: { serverId: 1, name: '行政部内网' },
                103: { serverId: 1, name: '财务部内网' },
                201: { serverId: 2, name: '销售部内网' },
                202: { serverId: 2, name: '市场部内网' },
                301: { serverId: 3, name: '生产部内网' },
                302: { serverId: 3, name: '质控部内网' },
                401: { serverId: 4, name: '客服部内网' },
                402: { serverId: 4, name: '技术支持内网' }
            };
            
            var networkData = networkInfo[networkId];
            if (!networkData) {
                return {
                    code: 0,
                    msg: '网络不存在',
                    data: []
                };
            }
            
            // 生成过去7天的日期
            var dates = [];
            for (var i = 6; i >= 0; i--) {
                var date = new Date();
                date.setDate(date.getDate() - i);
                dates.push((date.getMonth() + 1) + '/' + date.getDate());
            }
            
            // 健康评分数据
            var healthScores = [];
            for (var i = 0; i < 7; i++) {
                healthScores.push(rand(50, 100));
            }
            
            // 中断次数数据
            var disruptionCounts = [];
            for (var i = 0; i < 7; i++) {
                disruptionCounts.push(rand(0, 5));
            }
            
            // 网络状态（大部分情况为正常）
            var statusOptions = ['正常运行中', '离线', '不稳定'];
            var statusWeights = [0.8, 0.1, 0.1]; // 权重，大部分为正常
            var statusRand = Math.random();
            var status = statusOptions[0];
            
            if (statusRand > statusWeights[0]) {
                if (statusRand > statusWeights[0] + statusWeights[1]) {
                    status = statusOptions[2];
                } else {
                    status = statusOptions[1];
                }
            }
            
            return {
                code: 1,
                msg: '获取成功',
                data: {
                    net_name: networkData.net_name,
                    server_name: serverMap[networkData.server_name],
                    status: status,
                    avg_response_time: rand(5, 100), // 毫秒
                    health_score: rand(60, 95), // 0-100
                    avg_recovery_time: rand(3, 30), // 分钟
                    traffic: {
                        upload: rand(50, 200) + ' Mbps',
                        download: rand(100, 500) + ' Mbps'
                    },
                    health_score_trend: {
                        dates: dates,
                        scores: healthScores
                    },
                    disruption_trend: {
                        dates: dates,
                        counts: disruptionCounts
                    }
                }
            };
        }
    };
    
    return MockHandler;
}); 