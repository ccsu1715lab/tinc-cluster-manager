#!/bin/bash

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 显示欢迎信息
echo -e "${GREEN}欢迎使用一键部署工具${NC}"
echo "此工具将帮助您将系统部署到远程服务器"

#检查本地是否安装了必要的软件
check_local_commands(){
    local commands=("ssh" "ssh-keygen" "ssh-copy-id")
    for cmd in "${commands[@]}"; do
        if ! command -v $cmd &> /dev/null; then
            echo -e "${RED}错误: 需要安装 $cmd 命令${NC}"
            exit 1
        fi
    done
}

config_ssh_key(){
    #配置SSH无密码登录
    ssh_key_dir="$HOME/.ssh"
    ssh_pubkey="${ssh_key_dir}/id_rsa.pub"
    if [ -f "$ssh_pubkey" ]; then
        echo -e "${GREEN}SSH 公钥已存在${NC}"
        echo -e "${YELLOW}是否覆盖？(y/n)${NC}"
        read -p "输入: " answer
        if [ "$answer" == "y" ]; then
            ssh-keygen -t rsa -b 4096 -C "your_email@example.com" -f "$ssh_key_dir/id_rsa" -N ""
            echo -e "${GREEN}SSH 公钥已生成${NC}"
        else
            echo -e "${RED}操作已取消${NC}"
            exit 1
        fi
    else
        echo "SSH 公钥不存在，正在生成..."
        if ssh-keygen -t rsa -b 4096 -C "your_email@example.com" -f "$ssh_key_dir/id_rsa" -N ""; then
            echo -e "${GREEN}SSH 公钥已生成${NC}"
        else
            echo -e "${RED}SSH 公钥生成失败${NC}"
            exit 1
        fi
    fi

    #将公钥上传到远程服务器
    echo -e "${YELLOW}正在将公钥上传到远程服务器...${NC}"
    if ssh-copy-id -p $SERVER_PORT $SERVER_USER@$SERVER_IP; then
        echo -e "${GREEN}公钥上传成功${NC}"
    else
        echo -e "${RED}公钥上传失败${NC}"
        exit 1
    fi
    #测试SSH连接
    if ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP "echo 'SSH 连接测试成功'"; then
        echo -e "${GREEN}SSH 连接测试成功${NC}"
    else
        echo -e "${RED}连接测试失败，请检查配置${NC}"
        exit 1
    fi

}
config_ssh_connection(){
    # 测试 SSH 连接
    #输入SSH地址
    read -p "远程服务器ip:" SERVER_IP
    #输入SSH端口默认为22
    read -p "远程服务器端口:" SERVER_PORT
    SERVER_PORT=${SERVER_PORT:-22}
    #输入SSH用户名
    read -p "远程服务器用户名:" SERVER_USER
    #测试连接  
    echo -e "${YELLOW}测试 SSH 连接...${NC}"
    if ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP "echo 'SSH 连接测试成功'"; then
        echo -e "${GREEN}SSH 连接测试成功${NC}"
        echo -e "配置SSH无密码登录"
       # config_ssh_key
    else
        # 关键修改：不跳过严格检查，捕获真实的密钥变更警告
        ssh_error=$(ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP "echo 'SSH 连接测试成功'" 2>&1)
        # 检查是否包含主机密钥变更的核心警告（更准确的关键词）
        if grep -q "REMOTE HOST IDENTIFICATION HAS CHANGED" <<< "$ssh_error"; then
            echo -e "${YELLOW}检测到远程主机密钥变更，尝试清理本地旧记录...${NC}"
            # 自动删除冲突的主机密钥记录
            ssh-keygen -f "$HOME/.ssh/known_hosts" -R "$SERVER_IP"
            # 重新测试连接
            if ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP "echo 'SSH 连接测试成功'"; then
                echo -e "${GREEN}密钥冲突已解决，连接测试成功${NC}"
                config_ssh_key
            else
                echo -e "${RED}连接测试失败，请检查网络或账号配置${NC}"
                exit 1
            fi
        else
            echo -e "${RED}连接测试失败，请检查配置${NC}"
            exit 1
        fi
    fi
}

#检查服务器是否安装了必要的软件
check_server_commands(){
    local commands=("docker")
    for cmd in "${commands[@]}"; do
        if ! ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP "command -v $cmd &> /dev/null"; then
            echo -e "${RED}错误: 需要安装 $cmd 命令${NC}"
            exit 1
        fi
    done
}

#部署项目,dokcer镜像下载板
deploy_download(){
    WEB_PORT=81
    image_add=registry.cn-heyuan.aliyuncs.com/fast_deploy/ubuntu-20.04:latest
    # 检查镜像是否存在（修正：通过输出是否为空判断）
    if [ -n "$(ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP "docker images -q $image_add")" ]; then
        echo -e "${GREEN}镜像已存在，跳过下载${NC}"
    else
        echo -e "${YELLOW}镜像不存在，开始下载...${NC}"
        ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP "docker pull $image_add"
        if [ $? -ne 0 ]; then
            echo -e "${RED}下载镜像失败${NC}"
            exit 1
        fi 
    fi
    echo -e "${GREEN}镜像下载完成，准备创建容器${NC}"
    # 创建容器并检查是否成功
    ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP "docker run -itd -p $WEB_PORT:80 --name my_container $image_add"
    if [ $? -ne 0 ]; then
        echo -e "${RED}创建容器失败${NC}"
        exit 1
    else
        # 容器创建成功后，执行重启
        echo -e "${YELLOW}容器创建成功，正在重启...${NC}"
        ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP "docker restart my_container"
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}容器重启成功${NC}"
        else
            echo -e "${RED}容器重启失败${NC}"
            exit 1
        fi
    fi
    echo -e "${GREEN}容器创建并重启完成，部署完成${NC}"
}

#检查项目部署情况
check_deploy(){
    echo -e "${YELLOW}检查项目部署情况...${NC}"
    # 检查端口是否开放（使用 nc 替代 telnet，非交互模式）
    if nc -z -w 5 $SERVER_IP $WEB_PORT; then
        echo -e "${GREEN}端口 $WEB_PORT 开放${NC}"
    else
        echo -e "${RED}端口 $WEB_PORT 未开放${NC}"
        exit 1
    fi
    url="http://$SERVER_IP:$WEB_PORT/install.php"
    # 检查 URL 返回状态码（仅当 HTTP 状态码为 200 时判定成功）
    http_code=$(curl -s -o /dev/null -w "%{http_code}" "$url")
    if [ "$http_code" -eq 200 ]; then
        echo -e "${GREEN}项目部署成功${NC}"
        echo -e "${YELLOW}请访问:${NC} $url"
    else
        echo -e "${RED}项目部署失败（HTTP 状态码: $http_code）${NC}"
        exit 1
    fi
}

main(){
    echo -e "${YELLOW}检查是否安装了必要的软件...${NC}"
    check_local_commands
    echo -e "${YELLOW}正在配置ssh连接...${NC}"
    config_ssh_connection
    echo -e "${YELLOW}检查服务器是否安装了必要的软件...${NC}"
    check_server_commands
    echo -e "${YELLOW}开始部署...${NC}"
    deploy_download
    echo -e "${YELLOW}检查项目部署情况...${NC}"
    check_deploy
}
main