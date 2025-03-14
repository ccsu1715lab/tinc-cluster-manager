// 页面初始化逻辑
document.addEventListener('DOMContentLoaded', function() {
    // 初始化WebSocket连接
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const ws = new WebSocket(protocol + '//' + window.location.host + '/ws');

    // 实时更新节点状态
    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        updateNodeStatus(data);
    };

    // 定时获取基础数据
    setInterval(fetchBasicStats, 5000);
    fetchBasicStats(); // 立即获取首次数据
});

// 获取基础统计数据
async function fetchBasicStats() {
    try {
        const response = await fetch('/api/stats');
        const stats = await response.json();
        
        document.getElementById('runningTunnels').textContent = stats.activeConnections;
        document.getElementById('totalTraffic').textContent = 
            `${(stats.totalTraffic / 1024 / 1024).toFixed(2)} GB`;
    } catch (error) {
        console.error('获取统计信息失败:', error);
    }
}

// 更新节点状态函数
function updateNodeStatus(nodes) {
    const statusContainer = document.createElement('div');
    statusContainer.className = 'node-status-container';

    nodes.forEach(node => {
        const statusItem = document.createElement('div');
        statusItem.className = `status-item ${node.online ? 'online' : 'offline'}`;
        statusItem.innerHTML = `
            <span class="node-name">${node.name}</span>
            <span class="node-ip">${node.ip}</span>
            <span class="traffic">↑${node.upload}KB/s ↓${node.download}KB/s</span>
        `;
        statusContainer.appendChild(statusItem);
    });

    // 更新DOM
    const oldContainer = document.querySelector('.node-status-container');
    if (oldContainer) {
        oldContainer.replaceWith(statusContainer);
    } else {
        document.querySelector('.dashboard').appendChild(statusContainer);
    }
}