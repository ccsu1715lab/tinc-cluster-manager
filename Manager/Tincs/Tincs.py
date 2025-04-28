##Tincs类
import ipaddress
import os
import shutil
import platform
import subprocess   
from modle import get_tincd_pid_by_network
import time

class Tincs:
    TINC_PATH='/etc/tinc'
    network_name=''
    pub_ip=''
    pri_ip=''
    port=-1
    net_id=''
    network_address=''
    netmask=''
    status=''
    create_time=''
    Conf_content=''
    Tincup_content=''
    Tincdown_content=''
    Main_content=''
    
    def __init__(self,network_name,pub_ip,pri_ip,port,create_time):
        self.network_name=network_name
        self.pub_ip=pub_ip
        self.pri_ip=pri_ip
        self.network_address=self.ip_to_network(pri_ip)['network_address']
        self.netmask=self.ip_to_network(pri_ip)['netmask']
        self.port=port
        self.create_time=create_time
        self.Conf_content=f"""Name = main
Device = /dev/net/tun
Port = {self.port}"""
        self.Tincup_content=f"""ip link set $INTERFACE up
ip addr add {self.pri_ip}/32 dev $INTERFACE
ip route add {self.network_address}/24 dev $INTERFACE"""
        self.Tincdown_content=f"""ip route del {self.network_address}/24 dev $INTERFACE
ip addr del {self.pri_ip}/32 dev $INTERFACE
ip link set $INTERFACE down"""
        self.Main_content=f"""Address = {self.pub_ip}
Subnet = 0.0.0.0/0
Port = {self.port}"""
        
    def ip_to_network(self,ip_address):
        try:
            # 创建IPv4网络对象，使用24位掩码
            network = ipaddress.IPv4Network(f"{ip_address}/24", strict=False)
            # 获取网络地址
            network_address = network.network_address
            # 获取网络掩码
            netmask = network.netmask
            return {
                'network_address': str(network_address),
                'netmask': str(netmask),
                'network': str(network)
            }
        except ValueError as e:
            print(f"错误: {e}")
            return None

    def get_networkname(self):
        return self.network_name
    
    def get_pub_ip(self):
        return self.pub_ip
    
    def get_pri_ip(self):
        return self.pri_ip
    
    def get_port(self):
        return self.port    
    
    def get_net_id(self):
        return self.net_id
    
    def get_status(self):
        try:
            # 检查 systemd 服务状态
            systemd_status = subprocess.run(
                ['systemctl', 'is-active', f'tinc@{self.network_name}.service'],
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True
            )
            
            # 获取 systemd 服务的详细信息
            systemd_info = subprocess.run(
                ['systemctl', 'show', f'tinc@{self.network_name}.service'],
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True
            )
            
            # 检查进程状态
            process_status = subprocess.run(
                f"ps -ef | grep 'tincd -n {self.network_name}' | grep -v grep",
                shell=True,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True
            )
            
            # 获取进程的详细信息
            if process_status.returncode == 0:
                process_pid = process_status.stdout.split()[1]
                process_info = subprocess.run(
                    f"ps -p {process_pid} -o pid,stat,etime,cmd",
                    shell=True,
                    stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE,
                    text=True
                )
                print(f"进程信息: {process_info.stdout}")
            
            # 判断服务状态
            if systemd_status.returncode == 0 and systemd_status.stdout.strip() == 'active':
                if process_status.returncode == 0:
                    # 检查进程是否真的在运行
                    if 'Z' in process_info.stdout:  # Z 表示僵尸进程
                        return "进程异常（僵尸进程）"
                    elif 'D' in process_info.stdout:  # D 表示不可中断的睡眠状态
                        return "进程异常（不可中断）"
                    else:
                        return "运行中"
                else:
                    # 检查 systemd 服务的详细信息
                    if 'ExecMainStatus=0' in systemd_info.stdout:
                        return "systemd 服务异常（进程不存在）"
                    else:
                        return f"systemd 服务异常（错误码: {systemd_info.stdout.split('ExecMainStatus=')[1].split()[0]}）"
            elif systemd_status.returncode == 0 and systemd_status.stdout.strip() == 'inactive':
                return "已停止"
            elif systemd_status.returncode == 0 and systemd_status.stdout.strip() == 'failed':
                return "启动失败"
            else:
                # 如果 systemd 服务不存在，检查进程状态
                if process_status.returncode == 0:
                    return "运行中（非 systemd 管理）"
                else:
                    return "未运行"
        except Exception as e:
            print(f"获取服务状态时出错: {str(e)}")
            return "状态未知"
    
    def get_create_time(self):
        return self.create_time
    
    ##创建内网文件目录
    def create_net_dir(self):
        print(f"正在创建内网文件目录:{self.network_name}")
        os.makedirs(self.TINC_PATH+'/'+self.network_name)
        os.makedirs(self.TINC_PATH+'/'+self.network_name+'/hosts')
        
        # 创建配置文件并设置权限
        conf_files = {
            "tinc.conf": self.Conf_content,
            "tinc-down": self.Tincdown_content,
            "tinc-up": self.Tincup_content
        }
        
        for filename, content in conf_files.items():
            file_path = os.path.join(self.TINC_PATH, self.network_name, filename)
            with open(file_path, 'w') as f:
                f.write(content)
            # 设置文件权限为644 (rw-r--r--)
            os.chmod(file_path, 0o777)
        
        # 创建hosts/main文件并设置权限
        main_file = os.path.join(self.TINC_PATH, self.network_name, 'hosts', 'main')
        with open(main_file, 'w') as f:
            f.write(self.Main_content)
        # 设置文件权限为644 (rw-r--r--)
        os.chmod(main_file, 0o777)
        
        # 设置目录权限为755 (rwxr-xr-x)
        os.chmod(os.path.join(self.TINC_PATH, self.network_name), 0o777)
        os.chmod(os.path.join(self.TINC_PATH, self.network_name, 'hosts'), 0o777)
        
    ##删除内网文件目录
    def delete_net_dir(self):
        if os.path.exists(self.TINC_PATH+'/'+self.network_name):
            print(f"正在删除内网文件目录:{self.network_name}")
            shutil.rmtree(self.TINC_PATH+'/'+self.network_name)
        else:
            print(f"内网文件目录:{self.network_name}不存在")
        
    ##设置开机自启服务
    def set_auto_start(self):
        print(f"正在设置开机自启服务:{self.network_name}")
        try:
            # 创建 systemd 服务文件
            service_content = f"""[Unit]
Description=Tinc VPN Service for {self.network_name}
After=network.target

[Service]
Type=simple
ExecStart=/usr/sbin/tincd -n {self.network_name} -D
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
"""
            # 写入服务文件
            service_file = f"/etc/systemd/system/tinc@{self.network_name}.service"
            with open(service_file, 'w') as f:
                f.write(service_content)
            
            # 设置文件权限
            os.chmod(service_file, 0o644)
            
            # 重新加载 systemd
            subprocess.run(['systemctl', 'daemon-reload'], check=True)
            
            # 启用服务
            subprocess.run(['systemctl', 'enable', f'tinc@{self.network_name}.service'], check=True)
            
            print(f"成功设置开机自启服务: {self.network_name}")
            return True
        except Exception as e:
            print(f"设置开机自启服务失败: {str(e)}")
            return False
        
    ##删除开机自启服务
    def delete_auto_start(self):
        print(f"正在删除开机自启服务:{self.network_name}")
        try:
            # 停止服务
            subprocess.run(['systemctl', 'stop', f'tinc@{self.network_name}.service'], check=False)
            
            # 禁用服务
            subprocess.run(['systemctl', 'disable', f'tinc@{self.network_name}.service'], check=False)
            
            # 删除服务文件
            service_file = f"/etc/systemd/system/tinc@{self.network_name}.service"
            if os.path.exists(service_file):
                os.remove(service_file)
            
            # 重新加载 systemd
            subprocess.run(['systemctl', 'daemon-reload'], check=True)
            
            print(f"成功删除开机自启服务: {self.network_name}")
            return True
        except Exception as e:
            print(f"删除开机自启服务失败: {str(e)}")
            return False

    def ping_host(self,host, count=4, timeout=5):
        # 根据操作系统选择ping命令
        print(f"正在ping主机:{host}")
        param = '-n' if platform.system().lower() == 'windows' else '-c'
        # 构建ping命令
        command = ['ping', param, str(count), '-w', str(timeout), host]
        try:
            # 执行ping命令
            output = subprocess.run(command, 
                                stdout=subprocess.PIPE, 
                                stderr=subprocess.PIPE,
                                text=True)
            # 检查返回码和输出
            if output.returncode == 0:
                print(f"主机 {host} 可达")
                print("Ping结果:")
                print(output.stdout)
                print("Tincs服务配置成功")
                return True
            else:
                print(f"主机 {host} 不可达")
                print("错误信息:")
                print(output.stderr)
                print("Tincs服务配置失败")
                return False
        except Exception as e:
            print(f"执行ping命令时出错: {str(e)}")
            return False
        finally:
            ##杀死tincs进程
            pid = get_tincd_pid_by_network(self.network_name)
            if pid:
                os.system(f"kill -9 {pid}")
                print(f"杀死tincs进程:{pid}")
            else:
                print(f"未找到tincs进程")
    ##配置Tincs服务
    def config_tincs_service(self):
        print(f"正在配置Tincs服务:{self.network_name}")
        os.system(f"tincd -n {self.network_name} -K")
        self.start_tincs_service()
        self.ping_host(self.pri_ip)
        
        
    ##停止Tincs服务
    def stop_tincs_service(self):
        print(f"正在停止Tincs服务:{self.network_name}")
        os.system(f"tincd -n {self.network_name} -d")
    ##创建Tincs服务
    def create_tincs_service(self):
        print(f"正在创建Tincs服务:{self.network_name}")
        self.create_net_dir()
        self.config_tincs_service()
        self.set_auto_start()
        
    def delete_tincs_service(self):
        print(f"正在删除Tincs服务:{self.network_name}")
        ##杀死tincs进程
        ##删除目录
        self.delete_net_dir()
        ##删除开机自启服务
        self.delete_auto_start()
        
    def display_tincs_info(self):
        print(f"Tincs服务配置文件:{self.network_name}")
        print(f"公网IP:{self.pub_ip}")
        print(f"私网IP:{self.pri_ip}")
        print(f"端口号:{self.port}")
        print(f"服务状态:{self.get_status()}")
        print(f"创建时间:{self.create_time}")
        print("--------------------------------")
        
    def start_tincs_service(self):
        print(f"正在启动Tincs服务:{self.network_name}")
        try:
            # 使用subprocess.Popen在后台运行tincd
            process = subprocess.Popen(
                ['tincd', '-n', self.network_name, '-D'],
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                stdin=subprocess.PIPE,
                start_new_session=True  # 确保进程在后台运行
            )
            # 等待一小段时间确保进程启动
            time.sleep(2)
            
            # 使用ps命令检查tincd是否在运行
            check_command = f"ps -ef | grep 'tincd -n {self.network_name}' | grep -v grep"
            check_process = subprocess.run(
                check_command,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                shell=True,
                text=True#以字符串的格式输出
            )
            
            print(f"检查命令: {check_command}")
            print(f"检查输出: {check_process.stdout}")
            print(f"检查错误: {check_process.stderr}")
            print(f"返回码: {check_process.returncode}")
            
            if check_process.returncode == 0 and self.network_name in check_process.stdout:
                print(f"Tincs服务 {self.network_name} 已成功启动")
                return True
            else:
                print(f"Tincs服务 {self.network_name} 启动失败")
                return False
        except Exception as e:
            print(f"启动Tincs服务时出错: {str(e)}")
            return False
        
        
        
  
        
    
