import xml.etree.ElementTree as ET
import os

class XMLManager:
    root=None
    XML_FILE_PATH=None
    def __init__(self,XML_FILE_PATH):
        self.XML_FILE_PATH=XML_FILE_PATH
        try:
            # 检查文件是否存在
            if not os.path.exists(self.XML_FILE_PATH):
                # 如果文件不存在，创建基本的XML结构
                self.root = ET.Element("TincsColony")
                tree = ET.ElementTree(self.root)
                # 确保目录存在
                os.makedirs(os.path.dirname(self.XML_FILE_PATH), exist_ok=True)
                # 写入文件
                with open(self.XML_FILE_PATH, 'wb') as f:
                    tree.write(f, encoding='utf-8', xml_declaration=True)
            else:
                # 检查文件是否为空
                if os.path.getsize(self.XML_FILE_PATH) == 0:
                    # 如果文件为空，创建基本的XML结构
                    self.root = ET.Element("TincsColony")
                    tree = ET.ElementTree(self.root)
                    with open(self.XML_FILE_PATH, 'wb') as f:
                        tree.write(f, encoding='utf-8', xml_declaration=True)
                else:
                    # 尝试解析XML文件
                    try:
                        self.root = ET.parse(self.XML_FILE_PATH).getroot()
                    except ET.ParseError:
                        # 如果解析失败，创建新的XML结构
                        print("XML文件格式错误，将创建新的XML结构")
                        self.root = ET.Element("TincsColony")
                        tree = ET.ElementTree(self.root)
                        with open(self.XML_FILE_PATH, 'wb') as f:
                            tree.write(f, encoding='utf-8', xml_declaration=True)
        except Exception as e:
            print(f"初始化XML管理器时出错: {str(e)}")
            raise
            
    def add_network(self,network_name,pub_ip,pri_ip,port):
        try:
            # 确保所有值都是字符串类型
            network_name = str(network_name)
            pub_ip = str(pub_ip)
            pri_ip = str(pri_ip)
            port = str(port)
            
            network=ET.SubElement(self.root,"network")
            xml_network_name=ET.SubElement(network,"network_name")
            xml_network_name.text=network_name
            xml_pub_ip=ET.SubElement(network,"pub_ip")
            xml_pub_ip.text=pub_ip
            xml_pri_ip=ET.SubElement(network,"pri_ip")
            xml_pri_ip.text=pri_ip
            xml_port=ET.SubElement(network,"port")
            xml_port.text=port
            # 使用with语句确保文件正确关闭
            with open(self.XML_FILE_PATH, 'wb') as f:
                ET.ElementTree(self.root).write(f, encoding='utf-8', xml_declaration=True)
        except Exception as e:
            print(f"添加网络时出错: {str(e)}")
            raise
        
    def delete_network(self,network_name):
        try:
            # 确保network_name是字符串类型
            network_name = str(network_name)
            
            for network in self.root.findall("network"):
                if network.find("network_name").text==network_name:
                    self.root.remove(network)
                    # 使用with语句确保文件正确关闭
                    with open(self.XML_FILE_PATH, 'wb') as f:
                        ET.ElementTree(self.root).write(f, encoding='utf-8', xml_declaration=True)
                    print(f"删除网络{network_name}成功")
                    return
            print(f"删除网络{network_name}失败")
        except Exception as e:
            print(f"删除网络时出错: {str(e)}")
            raise
        
def main():
    try:
        xml_manager=XMLManager("D:/Mytesetfiles/shared_folder/docker/Manager/Tincs/xml/tincs.xml")
        ##xml_manager.add_network("zwgk1","192.168.1.2","192.168.1.2","8080")
        xml_manager.delete_network("zwgk1")
        parse_tree=ET.parse(xml_manager.XML_FILE_PATH)
        parse_root=parse_tree.getroot()
        for network in parse_root.findall("network"):
            network_name=network.find("network_name").text
            pub_ip=network.find("pub_ip").text
            pri_ip=network.find("pri_ip").text
            port=network.find("port").text
            print(f"网络名称:{network_name},公网IP:{pub_ip},私网IP:{pri_ip},端口号:{port}")
    except Exception as e:
        print(f"程序执行出错: {str(e)}")
    
if __name__=="__main__":
    main()

    
