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
    local commands=("ssh" "scp" "rsync" "docker","ssh-copy-id")
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

    # 配置SSH无密码登录（新增逻辑）
    echo -e "\n${YELLOW}正在配置SSH无密码登录...${NC}"
    local ssh_key_dir="$HOME/.ssh"
    local ssh_pub_key="$ssh_key_dir/id_rsa.pub"
    
    # 生成SSH密钥对（若不存在）
    if [ ! -f "$ssh_pub_key" ]; then
        echo -e "${YELLOW}生成SSH密钥对...${NC}"
        ssh-keygen -t rsa -f "$ssh_key_dir/id_rsa" -N "" -q  # 无密码生成
    fi

    # 上传公钥到远程服务器（仅需输入一次密码）
    echo -e "${YELLOW}上传公钥到服务器（需输入一次密码）...${NC}"
    ssh-copy-id -p $SSH_PORT -i "$ssh_pub_key" "$SSH_USER@$SERVER_IP"
    if [ $? -ne 0 ]; then
        echo -e "${RED}公钥上传失败，请检查密码或手动配置密钥认证${NC}"
        exit 1
    fi
    echo -e "${GREEN}SSH无密码登录配置完成${NC}"

    # 检查SSH连接（后续命令无需密码）
    echo -e "\n${YELLOW}正在测试SSH连接...${NC}"
    if ! ssh -p $SSH_PORT $SSH_USER@$SERVER_IP "echo 'SSH连接成功'" &> /dev/null; then
        echo -e "${RED}SSH连接失败，请检查配置${NC}"
        exit 1
    fi
    echo -e "${GREEN}SSH连接成功${NC}"
}

#安装Docker
install_docker() {
    echo -e "\n${YELLOW}正在安装Docker...${NC}"
    # 执行安装命令
    ssh -p $SSH_PORT $SSH_USER@$SERVER_IP "apt-get update && apt-get install -y docker.io"
    # 检查安装是否成功
    if [ $? -ne 0 ]; then
        echo -e "${RED}安装Docker失败${NC}"
        exit 1
    fi
    echo -e "${GREEN}Docker安装完成${NC}"
}
# 配置远程服务器
configure_server() {
    echo -e "\n${YELLOW}正在配置远程服务器...${NC}"
    
    # 检查服务器是否安装了docker（修正拼写错误：dokcerinstalled → dockerinstalled）
    dockerinstalled=$(ssh -p $SSH_PORT $SSH_USER@$SERVER_IP "docker --version")
    if [ -z "$dockerinstalled" ]; then
        echo -e "${RED}服务器未安装docker${NC}"
        install_docker  # 需确保已定义install_docker函数
    fi
    # 启动Docker服务
    ssh -p $SSH_PORT $SSH_USER@$SERVER_IP "systemctl start docker"
    # 检查Docker服务是否启动
    if [ $? -ne 0 ]; then
        echo -e "${RED}启动Docker服务失败${NC}"
        exit 1
    fi
    # 远程服务器下载docker镜像（修改部分）
    images_add=registry.cn-heyuan.aliyuncs.com/fast_deploy/ubuntu-20.04:1.0
    
    # 检查镜像是否已存在
    echo -e "${YELLOW}正在检查镜像是否存在...${NC}"
    image_exists=$(ssh -p $SSH_PORT $SSH_USER@$SERVER_IP "docker images -q $images_add")
    if [ -z "$image_exists" ]; then
        echo -e "${YELLOW}镜像不存在，开始下载...${NC}"
        # 执行下载命令
        ssh -p $SSH_PORT $SSH_USER@$SERVER_IP "docker pull $images_add"
        # 检查下载是否成功
        if [ $? -ne 0 ]; then
            echo -e "${RED}下载镜像失败${NC}"
            exit 1
        fi
        echo -e "${GREEN}镜像下载完成${NC}"
    else
        echo -e "${GREEN}镜像已存在，跳过下载${NC}"
    fi
    # 创建容器（示例：后台运行，映射80端口，命名为my_container）
    echo -e "${YELLOW}正在创建容器...${NC}"
    ssh -p $SSH_PORT $SSH_USER@$SERVER_IP "docker run -d -p 80:80 --name my_container $images_add"
    # 检查容器是否创建成功
    if [ $? -ne 0 ]; then
        echo -e "${RED}创建容器失败${NC}"
        exit 1
    fi
    echo -e "${GREEN}容器创建成功${NC}"

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
    configure_server
    cleanup
    
    echo -e "\n${GREEN}部署完成！${NC}"
    echo -e "系统已成功部署到 ${YELLOW}$SERVER_IP${NC}"
    echo -e "请访问: ${YELLOW}http://$SERVER_IP${NC}"
}

# 执行主函数
main