import psutil
import time
import requests
import socket
from datetime import datetime

# 后端更新数据的 API 地址，需要根据实际情况修改
UPDATE_API_URL = 'http://127.0.0.1/JOmfPHGEtW.php/trafficmonitor/upsertTrafficData'

def get_network_traffic():
    net_io_counters = psutil.net_io_counters(pernic=True)
    interface_traffic = {}
    for interface, counters in net_io_counters.items():
        # 如果你只需要特定接口，比如 wlan，可添加筛选条件
        if 'wlan' in interface.lower():
            interface_traffic[interface] = {
                'bytes_sent': counters.bytes_sent,
                'bytes_recv': counters.bytes_recv
            }
    return interface_traffic

def get_local_ip():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
    except Exception:
        ip = '127.0.0.1'
    finally:
        s.close()
    return ip

def send_traffic_data(interval=1):
    prev_traffic = get_network_traffic()
    local_ip = get_local_ip()
    hostname = socket.gethostname()
    while True:
        time.sleep(interval)
        current_traffic = get_network_traffic()
        for interface in prev_traffic:
            if interface in current_traffic:
                sent_speed = (current_traffic[interface]['bytes_sent'] - prev_traffic[interface]['bytes_sent']) / interval
                recv_speed = (current_traffic[interface]['bytes_recv'] - prev_traffic[interface]['bytes_recv']) / interval
                try:
                    # 获取当前时间并转换为 YYYY-MM-DD HH:MM:SS 格式
                    record_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                    data_to_send = {
                        'hostname': hostname,
                        'host_ip': local_ip,
                        'interface_name': interface,
                        'sent_speed': sent_speed,
                        'recv_speed': recv_speed,
                        'record_time': record_time
                    }
                    response = requests.post(UPDATE_API_URL, json=data_to_send)
                    if response.status_code == 200:
                        print('Traffic data updated successfully.')
                    else:
                        print(f'Failed to update traffic data. Status code: {response.status_code}, Response: {response.text}')
                except Exception as e:
                    print(f'Error sending traffic data: {e}')
        prev_traffic = current_traffic

if __name__ == "__main__":
    send_traffic_data()