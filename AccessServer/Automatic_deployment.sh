#!/bin/bash

# 获取当前脚本所在目录
SCRIPT_DIR=$(dirname "$(readlink -f "$0")")

# 定义要创建的service文件路径
SERVICE_FILE="/etc/systemd/system/tinc_cluster_Daemons.service"

# 检查是否以root用户运行
if [ "$(id -u)" -ne 0 ]; then
    echo "请使用root用户运行此脚本！"
    exit 1
fi

# 检查service文件是否已存在
if [ -f "$SERVICE_FILE" ]; then
    read -p "服务文件已存在，是否覆盖？(y/n): " overwrite
    if [[ ! "$overwrite" =~ ^[Yy]$ ]]; then
        echo "操作已取消。"
        exit 0
    fi
fi

# 创建service文件
cat > "$SERVICE_FILE" <<EOF
[Unit]
Description=tinc_cluster_Daemons Service                                              
[Service]
Type=simple
ExecStart=/bin/bash $SCRIPT_DIR/daemonsSoft/tinc_cluster_Daemons.sh
ExecStop=/bin/bash $SCRIPT_DIR/daemonsSoft/tinc_cluster_Daemons_quit.sh
StandardOutput=syslog
StandardError=inherit
[Install]
WantedBy=multi-user.target
EOF

# 设置文件权限
chmod 644 "$SERVICE_FILE"

echo "Service文件已创建：$SERVICE_FILE"
echo "你可以使用以下命令来启用和管理此服务："
echo "systemctl daemon-reload"
echo "systemctl enable tinc_cluster_Daemons"
echo "systemctl start tinc_cluster_Daemons"
