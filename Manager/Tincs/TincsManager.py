###Tincs管理器
from Tincs import Tincs
from datetime import datetime
from XMLNetwork import XMLNetwork
class TincsManager:
    Tincs_list={}#Tincs列表
    hash_network_name={}##内网名哈希表
    hash_pri_ip={}##私网IP哈希表
    hash_port={}##端口号哈希表
    conflict_info=['内网名冲突','私网IP冲突','端口号冲突','无冲突']##冲突信息
    XML_FILE_PATH=''##配置文件路径
    
    
    def __init__(self,XML_FILE_PATH):
        self.XML_FILE_PATH=XML_FILE_PATH
        self.XML_Manager=XMLNetwork(self.XML_FILE_PATH)
        self.load_info()
    ##加载配置文件
    def load_info(self):
        print("---加载Tincs配置文件---")
        ##初始化Tincs列表
        self.XML_Manager.Load_all_Tincs(self.Tincs_list)
        ##初始化内网名hash表
        for network_name, _ in self.Tincs_list.items():
            self.hash_network_name[network_name] = 1
        ##初始化私网IP哈希表
        for _, tincs in self.Tincs_list.items():
            self.hash_pri_ip[tincs.pri_ip] = 1
        ##初始化端口号哈希表
        for _, tincs in self.Tincs_list.items():
            self.hash_port[str(tincs.port)] = 1
        

        
    
    ##保存配置文件
    def save_info(self,Tincs):
        print("---保存Tincs配置文件---")
        self.XML_Manager.Append_network_config(Tincs)
        
    ##删除配置文件
    def delete_info(self,network_name):
        print("---删除Tincs配置文件---")
        self.XML_Manager.Remove_network_config(network_name)

    ##检测内网服务是否冲突
    def __check_net_conflict(self,network_name,pri_ip,port):
        isconflict=3
        if network_name in self.hash_network_name:
            isconflict=0
        elif pri_ip in self.hash_pri_ip:
            isconflict=1
        elif str(port) in self.hash_port:
            isconflict=2
        return self.conflict_info[isconflict]
        
    
    ##生成Tincs
    def generate_tincs(self,network_name,pub_ip,pri_ip,port):
        conflict_info=self.__check_net_conflict(network_name,pri_ip,port)
        if conflict_info!='无冲突':
            print(f"冲突类型：{conflict_info},无法生成Tincs服务！")
        else:
            print(f"正在生成Tincs服务配置文件:{network_name},公网IP:{pub_ip},私网IP:{pri_ip},端口号:{port}")
            New_tincs=Tincs(network_name,pub_ip,pri_ip,port,datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
            New_tincs.create_tincs_service()
            self.save_info(New_tincs)
    ##删除Tincs
    def delete_tincs(self,network_name):
        print("---删除Tincs---")
        if network_name in self.hash_network_name:
            print(f"正在删除Tincs服务配置文件:{network_name}")
            Tmp_tincs=Tincs(network_name,'1.1.1.1','1.1.1.1','12345','2025-04-27 10:00:00')
            Tmp_tincs.delete_tincs_service()
            self.delete_info(network_name)
        else:
            print(f"Tincs服务配置文件:{network_name}不存在")
    ##展示单个Tincs
    def show_single_tincs(self,network_name):
        if network_name in self.hash_network_name:
            print(f"正在展示Tincs服务配置文件:{network_name}")
            Tincs=self.Tincs_list[network_name]
            Tincs.display_tincs_info()
        else:
            print(f"Tincs服务配置文件:{network_name}不存在")
    ##展示所有Tincsa
    def show_all_tincs(self):
        print("---展示所有Tincs---")
        if len(self.Tincs_list)==0:
            print("当前没有Tincs服务")
        else:
            for _,network in self.Tincs_list.items():
                self.show_single_tincs(network.get_networkname())
    
    
