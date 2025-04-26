###Tincs管理器
from Tincs import Tincs
class TincsManager:
    Tincs_list=[]#Tincs列表
    config_path='./Tincs/config'##配置文件路径
    
    ##加载配置文件
    def load_info(serf):
        print("---加载Tincs配置文件---")
    
    ##保存配置文件
    def save_info(self):
        print("---保存Tincs配置文件---")
        
    
    ##生成Tincs
    def generate_tincs(self,networkname,pub_ip,pri_ip,port):
        print("---生成Tincs---")
        print(f"正在生成Tincs服务配置文件:{networkname},公网IP:{pub_ip},私网IP:{pri_ip},端口号:{port}")
    
    ##删除Tincs
    def delete_tincs(self,networkname):
        print("---删除Tincs---")
        print(f"正在删除Tincs服务配置文件:{networkname}")
    ##展示单个Tincs
    def show_single_tincs(self,networkname):
        print("---展示单个Tincs---")
        print(f"正在展示Tincs服务配置文件:{networkname}")
    ##展示所有Tincsa
    def show_all_tincs(self):
        print("---展示所有Tincs---")
    
    
