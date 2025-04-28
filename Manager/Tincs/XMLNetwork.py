import xml.etree.ElementTree as ET
from datetime import datetime
from Tincs import Tincs
import os
class XMLNetwork:
    XML_FILE_PATH=None
    root=None
    def __init__(self,XML_FILE_PATH):
        self.XML_FILE_PATH=XML_FILE_PATH
        if not os.path.exists(self.XML_FILE_PATH):
            print(f"初始化配置文件:{self.XML_FILE_PATH}")
            root=ET.Element("NetworkColony")
            tree=ET.ElementTree(root)
            with open(self.XML_FILE_PATH,'wb') as f:
                tree.write(f,encoding="utf-8",xml_declaration=True)
        self.root=ET.parse(self.XML_FILE_PATH).getroot()
        
    def Append_network_config(self,Tincs):
        network=ET.SubElement(self.root,"network")
        xml_network_name=ET.SubElement(network,"network_name")
        xml_network_name.text=Tincs.network_name
        xml_pub_ip=ET.SubElement(network,"pub_ip")
        xml_pub_ip.text=Tincs.pub_ip
        xml_pri_ip=ET.SubElement(network,"pri_ip")
        xml_pri_ip.text=Tincs.pri_ip
        xml_port=ET.SubElement(network,"port")
        xml_port.text=str(Tincs.port)
        xml_create_time=ET.SubElement(network,"create_time")
        xml_create_time.text=datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        tree=ET.ElementTree(self.root)
        with open(self.XML_FILE_PATH,'wb') as f:    
            tree.write(f,encoding="utf-8",xml_declaration=True)

    def Remove_network_config(self,network_name):
        for network in self.root.findall("network"):
            if network.find("network_name").text==network_name:
                self.root.remove(network)
                tree=ET.ElementTree(self.root)
                with open(self.XML_FILE_PATH,'wb') as f:
                    tree.write(f,encoding="utf-8",xml_declaration=True)
                print(f"删除网络{network_name}成功")
                return
        print(f"删除网络{network_name}失败")
        
    def Show_single_network_config(self,network_name):
        for network in self.root.findall("network"):
            if network.find("network_name").text==network_name:
                print(f"网络名称:{network.find('network_name').text}")
                print(f"公网IP:{network.find('pub_ip').text}")
                print(f"私网IP:{network.find('pri_ip').text}")
                print(f"端口号:{network.find('port').text}")
                print(f"创建时间:{network.find('create_time').text}")
                return
        print(f"网络{network_name}不存在")

    def Show_all_network_config(self):
        for network in self.root.findall("network"):
            network_name=network.find("network_name").text
            pub_ip=network.find("pub_ip").text
            pri_ip=network.find("pri_ip").text
            port=network.find("port").text
            create_time=network.find("create_time").text
            print(f"网络名称:{network_name},公网IP:{pub_ip},私网IP:{pri_ip},端口号:{port},创建时间:{create_time}")
            
 ##加载所有的Tinc对象           
    def Load_all_Tincs(self,Tincs_list):
        New_Tincs=None
        for network in self.root.findall("network"):
            network_name=network.find("network_name").text
            pub_ip=network.find("pub_ip").text
            pri_ip=network.find("pri_ip").text
            port=network.find("port").text
            create_time=network.find("create_time").text
            New_Tincs=Tincs(network_name,pub_ip,pri_ip,port,create_time)
            Tincs_list[network_name]=New_Tincs
