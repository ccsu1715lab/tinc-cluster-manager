#!/usr/bin/env python3
import click
import re
from Tincdvs import Tincdvs

TincdvsTool=Tincdvs()
@click.group()
def cli():
    """这是一个命令行工具"""
    pass
@cli.command()
@click.argument("network_name")
@click.option("--pub_ip", "-P", required=True, help="公网IP地址")
@click.option("--pri_ip", "-I", required=True, help="私网IP地址")
@click.option("--port", "-p", required=True, type=int, help="端口号（整数）")
def generate_tincs(network_name, pub_ip, pri_ip, port):
    """生成tinc服务配置
    示例：./test1.py generate-tincs my_vpn -P 1.1.1.1 -I 192.168.1.1 -p 65535
    """
    # 添加IP格式验证
    if not re.match(r"\d+\.\d+\.\d+\.\d+", pub_ip):
        raise click.BadParameter("公网IP格式错误")
    
    if network_name and pub_ip and pri_ip and port:
        TincdvsTool.generate_tincs(network_name,pub_ip,pri_ip,port)
    else:
        print("generate_tincs:缺少必要参数")
 
@cli.command()
@click.option("--network_name","-n",help="网络名称")   
@click.option("--al","-a",help="展示所有tinc服务",is_flag=True)
def display_tincs(network_name,al):
    """展示单个tinc服务"""
    if al:
        TincdvsTool.show_all_tincs()
    elif network_name:
        TincdvsTool.show_single_tincs(network_name)
    else:
        print("display_single_tinc:缺少必要参数")
    

@cli.command()
@click.option("--network_name","-n",help="网络名称") 
def delete_tincs(network_name):
    """删除tinc服务"""
    if network_name:
        TincdvsTool.delete_tincs(network_name)
    else:
        print("delete_tinc:缺少必要参数")
    
@cli.command()
@click.option("--network_name","-n",help="网络名称") 
def assign_thread(network_name):
    """分配线程"""
    if network_name:
        TincdvsTool.assign_thread(network_name)
    else:
        print("assign_thread:缺少必要参数")
    
@cli.command()
@click.option("--thread_id","-id",help="线程id")
def delete_thread(thread_id):
    """删除线程"""
    if thread_id:
        TincdvsTool.delete_thread(thread_id)
    else:
        print("delete_thread:缺少必要参数")
 
@cli.command()
@click.option("thread_id","-id",help="线程id")
@click.option("--al","-a",help="展示所有线程信息",is_flag=True)  
def display_thread(thread_id,al):
    """展示线程"""
    if al:
        TincdvsTool.show_all_threads()
    elif thread_id:
        TincdvsTool.show_single_thread(thread_id)
    else:
        print("display_thread:缺少必要参数")
        
if __name__=="__main__":
    cli()
    