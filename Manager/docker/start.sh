#!/bin/bash
#fastadmin服务开启脚本
start_service()
{
    service apache2 start 
    service mysql start 
}

check_install()
{
    install_url="localhost/install.php"
    response=$(curl -s -o /dev/null -w "%{http_code}" $install_url)
    if [ $response -eq 200 ];then
        echo "fastadmin部署成功，如果还未安装fastadmin,请用浏览器访问http://SERVERIP:your_port/install.php安装fastadmin"
    fi
}

main()
{
    
    start_service 
    check_install

 
}

main



