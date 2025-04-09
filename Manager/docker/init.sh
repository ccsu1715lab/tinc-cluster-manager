#!/bin/bash


remote_mysql()
{
    echo -n "please set your root password for mysql："
    read pass
    mysql -u root -p$pass -e "alter user 'root'@'localhost' identified with caching_sha2_password by '$pass';"
}

main()
{
    install_dir="/var/www/fastadmin/public/install.php"
    username="root"
    data="/data.sql"
    remote_mysql
    echo "请进入浏览器安装fastadmin，然后键入导入数据表，此脚本为一次性脚本,导入成功后将会自动删除"
    echo "dump_mysql------导入数据表"
    echo "exit------退出"
    echo " "
    flag="false"
    while true
        do
            echo -n "@kk#"
            read cmd
            if [ "$cmd" = "dump_mysql" ];then
                echo "=======TABLE DUMP======"
                   if test -e $install_dir;then
                        echo "你还没有安装fastadmin，请先安装"
                    else
                        echo -n "INPUT dbname for fastadmin："
                        read dbname
                        if test -e $data;then
                            mysql -u $username -p $dbname < $data  
                            flag="true"
                            echo "completely"
                            break
                        else
                            echo "Error:$data:no such file"
                            break
                        fi
                    fi
            elif [ "$cmd" = "exit" ];then
                break;              
            else
                echo "请安装fastadmin然后导入数据表"
            fi

        done
    if [ $flag = "true" ];then
        rm $data
        rm "$0"
    fi
}

main