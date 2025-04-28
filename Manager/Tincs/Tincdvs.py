from TincsManager import TincsManager
from ThreadManager import ThreadManager
import os
class Tincdvs:    
    TincsManager=None
    ThreadManager=None
    XML_FILE_PATH='./xml/tincs.xml'##配置文件路径
    
    def __init__(self):
        self.TincsManager=TincsManager(self.XML_FILE_PATH)
        self.ThreadManager=ThreadManager()

### 生成Tincs服务配置
    def generate_tincs(self,network_name,pub_ip,pri_ip,port):
        if True:#os.path.isdir(f"/etc/tinc"):
            self.TincsManager.generate_tincs(network_name,pub_ip,pri_ip,port)
        else:
            print("您暂未安装tinc,执行apt install tinc")    
        ##self.TincsManager.generate_tincs(network_name,pub_ip,pri_ip,port)

##展示所有的Tincs服务信息
    def show_all_tincs(self):
        self.TincsManager.show_all_tincs()


##删除Tincs服务
    def delete_tincs(self,network_name):
        self.TincsManager.delete_tincs(network_name)
    
##展示单个Tincs服务信息
    def show_single_tincs(self,network_name):
        self.TincsManager.show_single_tincs(network_name)

### 分配线程
    def assign_thread(self,network_name):
        self.ThreadManager.assign_thread(network_name)
    
### 删除线程
    def delete_thread(self,thread_id):
        self.ThreadManager.delete_thread(thread_id)

### 展示所有线程信息
    def show_all_threads(self):
        self.ThreadManager.show_all_threads()

### 展示单个线程信息
    def show_single_thread(self,thread_id):
        self.ThreadManager.show_single_thread(thread_id)
    


    
    












