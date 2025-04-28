import subprocess

def get_tincd_pid(network_name=None):
    """
    获取tincd进程的PID
    :param network_name: 网络名称（可选）
    :return: 进程信息字典或None
    """
    try:
        # 构建ps命令
        if network_name:
            command = f"ps -ef | grep 'tincd.*-n {network_name}' | grep -v grep"
        else:
            command = "ps -ef | grep tincd | grep -v grep"
        
        # 执行命令
        result = subprocess.run(command, 
                              shell=True,
                              stdout=subprocess.PIPE, 
                              stderr=subprocess.PIPE,
                              text=True)
        
        if result.returncode == 0:
            # 解析输出
            lines = result.stdout.split('\n')
            
            # 如果指定了网络名称，只返回第一个匹配的进程
            if network_name:
                for line in lines:
                    if line.strip():
                        parts = line.split()
                        if len(parts) >= 8:
                            # 提取网络名称
                            network = None
                            for part in parts:
                                if part.startswith('-n'):
                                    network = parts[parts.index(part) + 1]
                                    break
                            
                            if network == network_name:
                                return {
                                    'user': parts[0],      # 用户
                                    'pid': parts[1],       # 进程ID
                                    'ppid': parts[2],      # 父进程ID
                                    'network': network,    # 网络名称
                                    'command': ' '.join(parts[7:])  # 完整命令
                                }
                return None
            
            # 如果没有指定网络名称，返回所有tincd进程
            processes = []
            for line in lines:
                if line.strip():
                    parts = line.split()
                    if len(parts) >= 8:
                        # 提取网络名称
                        network = None
                        for part in parts:
                            if part.startswith('-n'):
                                network = parts[parts.index(part) + 1]
                                break
                                
                        processes.append({
                            'user': parts[0],      # 用户
                            'pid': parts[1],       # 进程ID
                            'ppid': parts[2],      # 父进程ID
                            'network': network,    # 网络名称
                            'command': ' '.join(parts[7:])  # 完整命令
                        })
            return processes
        else:
            print(f"执行命令出错: {result.stderr}")
            return None if network_name else []
            
    except Exception as e:
        print(f"获取tincd进程信息时出错: {str(e)}")
        return None if network_name else []

def print_tincd_info(processes):
    """
    打印tincd进程信息
    :param processes: 进程信息字典或列表
    """
    if not processes:
        print("没有找到tincd进程")
        return
        
    print("\ntincd进程信息:")
    print("用户\tPID\tPPID\t网络名称\t命令")
    print("-" * 100)
    
    if isinstance(processes, dict):
        proc = processes
        print(f"{proc['user']}\t{proc['pid']}\t{proc['ppid']}\t{proc['network']}\t{proc['command']}")
    else:
        for proc in processes:
            print(f"{proc['user']}\t{proc['pid']}\t{proc['ppid']}\t{proc['network']}\t{proc['command']}")

def get_tincd_pid_by_network(network_name):
    """
    根据网络名称获取tincd进程的PID
    :param network_name: 网络名称
    :return: PID或None
    """
    process = get_tincd_pid(network_name)
    return process['pid'] if process else None

if __name__ == "__main__":
    # 获取所有tincd进程
    print("获取所有tincd进程信息:")
    all_processes = get_tincd_pid()
    print_tincd_info(all_processes)
    
    # 获取特定网络的tincd进程
    network_name = "sadf"  # 示例：查找sadf网络的tincd进程
    print(f"\n查找网络 {network_name} 的tincd进程:")
    specific_process = get_tincd_pid(network_name)
    print_tincd_info(specific_process)
    
    # 只获取PID
    pid = get_tincd_pid_by_network(network_name)
    if pid:
        print(f"\n网络 {network_name} 的tincd进程PID: {pid}")
    else:
        print(f"\n未找到网络 {network_name} 的tincd进程") 