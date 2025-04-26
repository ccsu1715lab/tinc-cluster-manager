##Tincs类
class Tincs:
    networkname=''
    pub_ip=''
    pri_ip=''
    port=-1
    net_id=''
    status=''
    create_time=''
    
    def __init__(self,networkname,pub_ip,pri_ip,port,net_id,status,create_time):
        self.networkname=networkname
        self.pub_ip=pub_ip
        self.pri_ip=pri_ip
        self.port=port
        self.net_id=net_id
        self.status=status
        self.create_time=create_time

    def get_networkname(self):
        return self.networkname
    
    def get_pub_ip(self):
        return self.pub_ip
    
    def get_pri_ip(self):
        return self.pri_ip
    
    def get_port(self):
        return self.port    
    
    def get_net_id(self):
        return self.net_id
    
    def get_status(self):
        return self.status  
    
    def get_create_time(self):
        return self.create_time
    
    ##创建内网文件目录
    def create_net_dir(self):
        print(f"正在创建内网文件目录:{self.networkname}")
        
    ##删除内网文件目录
    def delete_net_dir(self):
        print(f"正在删除内网文件目录:{self.networkname}")
        
    ##设置开机自启服务
    def set_auto_start(self):
        print(f"正在设置开机自启服务:{self.networkname}")
        
        
        
  
        
    
