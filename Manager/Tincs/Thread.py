##线程类
class Thread:
    thread_id=''
    tincs_list=[]#Tincs列表
    status=''
    create_time=''
    
    def __init__(self,thread_id,tincs_list,status,create_time):
        self.thread_id=thread_id
        self.tincs_list=tincs_list
        self.status=status
        self.create_time=create_time    
        
    def get_thread_id(self):
        return self.thread_id
    
    def get_tincs_list(self):
        return self.tincs_list  
    
    def get_status(self):
        return self.status
    
    def get_create_time(self):
        return self.create_time
    
    
