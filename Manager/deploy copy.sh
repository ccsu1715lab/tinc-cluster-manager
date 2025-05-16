#!/bin/bash

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 显示欢迎信息
echo -e "${GREEN}欢迎使用一键部署工具${NC}"
echo "此工具将帮助您将系统部署到远程服务器"

# 检查必要的命令是否存在
check_commands() {
    local commands=("ssh" "scp" "rsync" "docker" "docker-compose")
    for cmd in "${commands[@]}"; do
        if ! command -v $cmd &> /dev/null; then
            echo -e "${RED}错误: 需要安装 $cmd 命令${NC}"
            exit 1
        fi
    done
}

# 收集配置信息
collect_config() {
    echo -e "\n${YELLOW}请输入部署配置信息:${NC}"
    
    read -p "远程服务器IP地址: " SERVER_IP
    read -p "SSH用户名: " SSH_USER
    read -p "SSH端口 (默认22): " SSH_PORT
    SSH_PORT=${SSH_PORT:-22}
    
    # 检查SSH连接
    echo -e "\n${YELLOW}正在测试SSH连接...${NC}"
    if ! ssh -p $SSH_PORT $SSH_USER@$SERVER_IP "echo 'SSH连接成功'" &> /dev/null; then
        echo -e "${RED}SSH连接失败，请检查配置${NC}"
        exit 1
    fi
    echo -e "${GREEN}SSH连接成功${NC}"
}

# 准备部署包
prepare_deployment() {
    echo -e "\n${YELLOW}正在准备部署包...${NC}"
    
    # 创建临时目录
    DEPLOY_DIR="deploy_temp"
    mkdir -p $DEPLOY_DIR
    
    # 复制必要的文件
    cp -r src $DEPLOY_DIR/
    cp -r public $DEPLOY_DIR/
    cp -r application $DEPLOY_DIR/
    cp Dockerfile $DEPLOY_DIR/
    cp docker-compose.yml $DEPLOY_DIR/
    
    # 创建环境配置文件
    cat > $DEPLOY_DIR/.env << EOF
DB_HOST=db
DB_NAME=manager
DB_USER=manager
DB_PASSWORD=manager_password
MYSQL_ROOT_PASSWORD=root_password
EOF
    
    echo -e "${GREEN}部署包准备完成${NC}"
}

# 部署到远程服务器
deploy_to_server() {
    echo -e "\n${YELLOW}正在部署到远程服务器...${NC}"
    
    # 创建远程目录
    REMOTE_DIR="/opt/manager"
    ssh -p $SSH_PORT $SSH_USER@$SERVER_IP "mkdir -p $REMOTE_DIR"
    
    # 同步文件
    rsync -avz -e "ssh -p $SSH_PORT" $DEPLOY_DIR/ $SSH_USER@$SERVER_IP:$REMOTE_DIR/
    
    echo -e "${GREEN}文件部署完成${NC}"
}

# 配置远程服务器
configure_server() {
    echo -e "\n${YELLOW}正在配置远程服务器...${NC}"
    
    # 安装Docker和Docker Compose
    ssh -p $SSH_PORT $SSH_USER@$SERVER_IP << 'EOF'
        # 安装Docker
        if ! command -v docker &> /dev/null; then
            curl -fsSL https://get.docker.com -o get-docker.sh
            sudo sh get-docker.sh
            sudo usermod -aG docker $USER
        fi
        
        # 安装Docker Compose
        if ! command -v docker-compose &> /dev/null; then
            sudo curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
            sudo chmod +x /usr/local/bin/docker-compose
        fi
EOF
    
    # 启动Docker服务
    ssh -p $SSH_PORT $SSH_USER@$SERVER_IP "cd $REMOTE_DIR && docker-compose up -d"
    
    echo -e "${GREEN}服务器配置完成${NC}"
}

# 清理临时文件
cleanup() {
    echo -e "\n${YELLOW}正在清理临时文件...${NC}"
    rm -rf $DEPLOY_DIR
    echo -e "${GREEN}清理完成${NC}"
}

# 主函数
main() {
    check_commands
    collect_config
    prepare_deployment
    deploy_to_server
    configure_server
    cleanup
    
    echo -e "\n${GREEN}部署完成！${NC}"
    echo -e "系统已成功部署到 ${YELLOW}$SERVER_IP${NC}"
    echo -e "请访问: ${YELLOW}http://$SERVER_IP${NC}"
}

# 执行主函数
main 