import requests
import base64
import json
from datetime import datetime
import sys
import re
import xml.etree.ElementTree as ET

# ========= CONFIG =========
IP        = sys.argv[2]
PORT      = sys.argv[3]
USERNAME  = sys.argv[4]
PASSWORD  = sys.argv[5]

IP = f"{IP}:{PORT}"


OLT_NAME = "CorelinkOLT"
# IP = "116.206.91.42:2222"  # Updated to your working IP
BASE_URL = f"http://{IP}"
# USERNAME = "rafe1"
# PASSWORD = "rafe66556621"

# ========= HELPERS =========
def now():
    return datetime.now().strftime("%Y-%m-%d %H:%M:%S")

def error_response(action, olt):
    """Return error response in standard format"""
    if action == "onustatus":
        return json.dumps({
            "olt": [olt],
            "onu_id": ["ERROR"],
            "status": ["Error"],
            "mac": ["00:00:00:00:00:00"],
            "des": ["Connection Failed"],
            "rx": ["0.00"],
            "distance": [0],
            "last_register": [now()],
            "last_deregister": [now()],
            "reason": ["Connection Error"],
            "olt_status": "Offline",
            "online": 0,
            "offline": 0,
            "wire_down": 0
        }, indent=4)
    elif action in ["rxpower", "opm"]:
        return "0.00"
    elif action == "routermac":
        return json.dumps({
            "olt": olt,
            "cpu": 0,
            "memory": 0,
            "router_mac": [],
            "onu_id": []
        }, indent=4)

# ========= LOGIN FUNCTIONS =========
def loginv1(ip, username, password):
    """Login for Corelink v1 firmware"""
    credentials = f"{username}:{password}"
    credentials_encoded = base64.b64encode(credentials.encode('utf-8')).decode('utf-8')
    auth = f"Basic {credentials_encoded}"
    
    headers = {
        'Authorization': auth,
        'Connection': 'keep-alive',
        'Referer': f'http://{ip}/index.asp',
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36'
    }
    
    # Refresh page to establish session
    try:
        requests.get(f"http://{ip}/index.asp", headers=headers, timeout=5)
        return headers
    except:
        return None

def loginv2(ip, username, password):
    """Login for Corelink v2/v3 firmware (XML API)"""
    credentials = f"{username}&{password}"
    credentials_encoded = base64.b64encode(credentials.encode('utf-8')).decode('utf-8')
    url = f"http://{ip}/sw.cgi"

    payload = f"set=login&user={credentials_encoded}"
    headers = {
        'Accept': '*/*',
        'Content-type': 'uni_mars_ap',
        'Origin': f'http://{ip}',
        'Referer': f'http://{ip}/',
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'X-Requested-With': 'XMLHttpRequest'
    }

    try:
        response = requests.post(url, headers=headers, data=payload, timeout=10)
        return headers if response.status_code == 200 else None
    except:
        return None

# ========= CORE LINK V1 (HTML/JavaScript) =========
def corelink_v1(olt, ip, username, password, action, pon_ports, key, cmd, onuid):
    """For Corelink firmware version 1 (HTML/JS based)"""
    try:
        headers = loginv1(ip, username, password)
        if not headers:
            return error_response(action, olt)
        
        current_date_time = now()
        
        if action == "onustatus":
            url = f"http://{ip}/onuAllPonOnuList.asp"
            response = requests.get(url, headers=headers, timeout=10)
            html_content = response.text
            
            # Parse JavaScript array
            pattern = re.compile(r"var onutable=new Array\((.*?)\);", re.DOTALL)
            match = pattern.search(html_content)
            if not match:
                return error_response(action, olt)
            
            onutable_content = match.group(1).strip()
            onutable_lines = onutable_content.split('\n')
            
            # Initialize arrays
            olt_name = []
            onu_ids = []
            statuses = []
            mac_addresses = []
            descriptions = []
            rx_power = []
            distances = []
            register_times = []
            deregister_times = []
            deregister_reasons = []
            
            for line in onutable_lines:
                if not line.strip():
                    continue
                    
                data = line.replace("'", "").split(',')
                if len(data) >= 19:
                    onu_ids.append(f"ONU_{data[0]}")
                    descriptions.append(data[1])
                    mac_addresses.append(data[2])
                    statuses.append("Online" if data[3].lower() == "up" else "Offline")
                    rx_power.append(data[15] if len(data) > 15 else "0.00")
                    olt_name.append(olt)
                    distances.append(0)
                    register_times.append(current_date_time)
                    deregister_times.append(current_date_time)
                    deregister_reasons.append("Wire Down" if data[18] == "0" else "Power Off")
            
            # Calculate counts
            online_onu = statuses.count("Online")
            offline_onu = sum(1 for i, status in enumerate(statuses) 
                            if status == "Offline" and deregister_reasons[i] == "Power Off")
            wire_down = sum(1 for i, status in enumerate(statuses) 
                          if status == "Offline" and deregister_reasons[i] == "Wire Down")
            
            data_dict = {
                "olt": olt_name,
                "onu_id": onu_ids,
                "status": statuses,
                "mac": mac_addresses,
                "des": descriptions,
                "rx": rx_power,
                "distance": distances,
                "last_register": register_times,
                "last_deregister": deregister_times,
                "reason": deregister_reasons,
                "olt_status": "Online",
                "online": online_onu,
                "offline": offline_onu,
                "wire_down": wire_down
            }
            
            return json.dumps(data_dict, indent=4)
            
        elif action == "rxpower":
            url = f"http://{ip}/onuAllPonOnuList.asp"
            response = requests.get(url, headers=headers, timeout=10)
            html_content = response.text
            
            pattern = re.compile(r"var onutable=new Array\((.*?)\);", re.DOTALL)
            match = pattern.search(html_content)
            if not match:
                return "0.00"
            
            onutable_content = match.group(1).strip()
            onutable_lines = onutable_content.split('\n')
            
            onu_ids = []
            rx_power = []
            
            for line in onutable_lines:
                if not line.strip():
                    continue
                    
                data = line.replace("'", "").split(',')
                if len(data) >= 16:
                    onu_ids.append(f"ONU_{data[0]}")
                    rx_power.append(data[15] if len(data) > 15 else "0.00")
            
            if onuid in onu_ids:
                index = onu_ids.index(onuid)
                return rx_power[index] if index < len(rx_power) else "0.00"
            return "0.00"
            
        elif action == "routermac":
            # Get MAC addresses
            url = f"http://{ip}/oltMacFdb.asp"
            response = requests.get(url, headers=headers, timeout=10)
            html_content = response.text
            
            pattern = re.compile(r"var olt_fdb=new Array\((.*?)\);", re.DOTALL)
            match = pattern.search(html_content)
            
            mac_addresses = []
            onu_ids = []
            
            if match:
                olt_fdb_content = match.group(1).strip()
                olt_fdb_lines = olt_fdb_content.split('\n')
                
                for line in olt_fdb_lines:
                    if not line.strip():
                        continue
                    data = line.replace("'", "").split(',')
                    if len(data) >= 5:
                        onu_ids.append(data[0])
                        mac_addresses.append(data[4])
            
            # Get CPU and memory
            url2 = f"http://{ip}/system.asp"
            response2 = requests.get(url2, headers=headers, timeout=10)
            html_content2 = response2.text
            
            pattern2 = r'var sysInfo\s*=\s*new\s*Array\((.*?)\);'
            match2 = re.search(pattern2, html_content2, re.DOTALL)
            
            cpu = 0
            memory = 0
            
            if match2:
                sys_info_contents = match2.group(1).strip().split('",')
                sys_info = [element.strip().strip('"') for element in sys_info_contents]
                if len(sys_info) >= 13:
                    cpu = int(float(sys_info[11]))
                    memory = int(float(sys_info[12]))
            
            result = {
                "olt": olt,
                "cpu": cpu,
                "memory": memory,
                "router_mac": mac_addresses,
                "onu_id": onu_ids
            }
            
            return json.dumps(result, indent=4)
            
    except Exception as e:
        print(f"Corelink v1 error: {e}", file=sys.stderr)
        return error_response(action, olt)

# ========= CORE LINK V2 (XML API) =========
def corelink_v2(olt, ip, username, password, action, pon_ports, key, cmd, onuid):
    """For Corelink firmware version 2 (XML API based) - WORKING VERSION"""
    try:
        headers = loginv2(ip, username, password)
        if not headers:
            return error_response(action, olt)
        
        current_date_time = now()
        
        if action == "onustatus":
            payload = "get=onualllist&sysUnit=1"
            url = f"http://{ip}/sw.cgi"
            
            # Use appropriate referer
            headers_onu = headers.copy()
            headers_onu['Referer'] = f'http://{ip}/m/onu_all_onu.htm'
            
            response = requests.post(url, headers=headers_onu, data=payload, timeout=10)
            
            if response.status_code != 200:
                return error_response(action, olt)
                
            # Parse XML response
            root = ET.fromstring(response.text)
            
            olt_name = []
            onu_ids = []
            descriptions = []
            mac_addresses = []
            statuses = []
            rx_power = []
            distances = []
            register_times = []
            deregister_times = []
            deregister_reasons = []
            
            for item in root.findall('item'):
                onu_data = item.get('onu', '')
                if not onu_data:
                    continue
                    
                data = onu_data.split(',')
                if len(data) >= 19:
                    onu_ids.append(f"ONU_{data[0]}")
                    descriptions.append(data[1])
                    mac_addresses.append(data[2])
                    statuses.append("Online" if data[3].strip().lower() == 'up' else "Offline")
                    rx_power.append(data[15] if len(data) > 15 and data[15].strip() else "0.00")
                    olt_name.append(olt)
                    distances.append(0)
                    register_times.append(current_date_time)
                    deregister_times.append(current_date_time)
                    deregister_reasons.append("Wire Down" if data[18].strip() == "0" else "Power Off")
            
            # Calculate counts
            online_onu = statuses.count("Online")
            offline_onu = sum(1 for i, status in enumerate(statuses) 
                            if status == "Offline" and deregister_reasons[i] == "Power Off")
            wire_down = sum(1 for i, status in enumerate(statuses) 
                          if status == "Offline" and deregister_reasons[i] == "Wire Down")
            
            data_dict = {
                "olt": olt_name,
                "onu_id": onu_ids,
                "status": statuses,
                "mac": mac_addresses,
                "des": descriptions,
                "rx": rx_power,
                "distance": distances,
                "last_register": register_times,
                "last_deregister": deregister_times,
                "reason": deregister_reasons,
                "olt_status": "Online",
                "online": online_onu,
                "offline": offline_onu,
                "wire_down": wire_down
            }
            
            return json.dumps(data_dict, indent=4)
            
        elif action == "rxpower":
            payload = "get=onualllist&sysUnit=1"
            url = f"http://{ip}/sw.cgi"
            
            headers_onu = headers.copy()
            headers_onu['Referer'] = f'http://{ip}/m/onu_all_onu.htm'
            
            response = requests.post(url, headers=headers_onu, data=payload, timeout=10)
            
            if response.status_code != 200:
                return "0.00"
                
            root = ET.fromstring(response.text)
            
            onu_ids = []
            rx_power = []
            
            for item in root.findall('item'):
                onu_data = item.get('onu', '')
                if onu_data:
                    data = onu_data.split(',')
                    if len(data) >= 16:
                        onu_ids.append(f"ONU_{data[0]}")
                        rx_power.append(data[15] if len(data) > 15 and data[15].strip() else "0.00")
            
            if onuid in onu_ids:
                index = onu_ids.index(onuid)
                return rx_power[index] if index < len(rx_power) else "0.00"
            return "0.00"
            
        elif action == "routermac":
            # Get system info
            headers_sys = headers.copy()
            headers_sys['Referer'] = f'http://{ip}/m/system_info.htm'
            
            payload = "get=sysinfo2&sysunit=1"
            url = f"http://{ip}/sw.cgi"
            response = requests.post(url, headers=headers_sys, data=payload, timeout=10)
            
            cpu = 0
            memory = 0
            
            if response.status_code == 200:
                try:
                    root = ET.fromstring(response.text)
                    cpu_elem = root.find(".//item[@cpu]")
                    memory_elem = root.find(".//item[@memory]")
                    
                    if cpu_elem is not None:
                        cpu = int(cpu_elem.attrib['cpu'].strip())
                    
                    if memory_elem is not None:
                        memory_str = memory_elem.attrib['memory']
                        parts = memory_str.split('?')
                        if len(parts) >= 2:
                            used = int(parts[1].strip())
                            total = int(parts[0].strip())
                            memory = round((used / total) * 100) if total > 0 else 0
                except:
                    pass
            
            # Get MAC addresses
            headers_mac = headers.copy()
            headers_mac['Referer'] = f'http://{ip}/m/olt_mac_fdb.htm'
            
            payload = "get=oltfdb&sysUnit=1"
            url = f"http://{ip}/sw.cgi"
            response = requests.post(url, headers=headers_mac, data=payload, timeout=10)
            
            mac_addresses = []
            onu_ids = []
            
            if response.status_code == 200:
                try:
                    root = ET.fromstring(response.text)
                    for item in root.findall('item'):
                        mac_data = item.get('mac', '')
                        if mac_data:
                            data = mac_data.split(',')
                            if len(data) >= 5:
                                onu_ids.append(data[0])
                                mac_addresses.append(data[4])
                except:
                    pass
            
            result = {
                "olt": olt,
                "cpu": cpu,
                "memory": memory,
                "router_mac": mac_addresses,
                "onu_id": onu_ids
            }
            
            return json.dumps(result, indent=4)
            
    except Exception as e:
        print(f"Corelink v2 error: {e}", file=sys.stderr)
        return error_response(action, olt)

# ========= CORE LINK V3 (Enhanced XML API) =========
def corelink_v3(olt, ip, username, password, action, pon_ports, key, cmd, onuid):
    """For Corelink firmware version 3 (Enhanced XML API)"""
    try:
        headers = loginv2(ip, username, password)
        if not headers:
            return error_response(action, olt)
        
        current_date_time = now()
        
        if action == "onustatus":
            payload = "get=onualllist&sysUnit=1"
            url = f"http://{ip}/sw.cgi"
            
            headers_onu = headers.copy()
            headers_onu['Referer'] = f'http://{ip}/m/onu_all_onu.htm'
            
            response = requests.post(url, headers=headers_onu, data=payload, timeout=15)
            
            if response.status_code != 200:
                return error_response(action, olt)
                
            # Parse XML response
            root = ET.fromstring(response.text)
            
            olt_name = []
            onu_ids = []
            descriptions = []
            mac_addresses = []
            statuses = []
            rx_power = []
            distances = []
            register_times = []
            deregister_times = []
            deregister_reasons = []
            
            for item in root.findall('item'):
                onu_data = item.get('onu', '')
                if not onu_data:
                    continue
                    
                data = onu_data.split(',')
                if len(data) >= 19:
                    onu_ids.append(f"ONU_{data[0]}")
                    descriptions.append(data[1])
                    mac_addresses.append(data[2])
                    statuses.append("Online" if data[3].strip().lower() == 'up' else "Offline")
                    rx_power.append(data[15] if len(data) > 15 and data[15].strip() else "0.00")
                    olt_name.append(olt)
                    distances.append(0)
                    register_times.append(current_date_time)
                    deregister_times.append(current_date_time)
                    deregister_reasons.append("Wire Down" if data[18].strip() == "0" else "Power Off")
            
            # Calculate counts
            online_onu = statuses.count("Online")
            offline_onu = sum(1 for i, status in enumerate(statuses) 
                            if status == "Offline" and deregister_reasons[i] == "Power Off")
            wire_down = sum(1 for i, status in enumerate(statuses) 
                          if status == "Offline" and deregister_reasons[i] == "Wire Down")
            
            data_dict = {
                "olt": olt_name,
                "onu_id": onu_ids,
                "status": statuses,
                "mac": mac_addresses,
                "des": descriptions,
                "rx": rx_power,
                "distance": distances,
                "last_register": register_times,
                "last_deregister": deregister_times,
                "reason": deregister_reasons,
                "olt_status": "Online",
                "online": online_onu,
                "offline": offline_onu,
                "wire_down": wire_down
            }
            
            return json.dumps(data_dict, indent=4)
            
        elif action == "rxpower":
            payload = "get=onualllist&sysUnit=1"
            url = f"http://{ip}/sw.cgi"
            
            headers_onu = headers.copy()
            headers_onu['Referer'] = f'http://{ip}/m/onu_all_onu.htm'
            
            response = requests.post(url, headers=headers_onu, data=payload, timeout=15)
            
            if response.status_code != 200:
                return "0.00"
                
            root = ET.fromstring(response.text)
            
            onu_ids = []
            rx_power = []
            
            for item in root.findall('item'):
                onu_data = item.get('onu', '')
                if onu_data:
                    data = onu_data.split(',')
                    if len(data) >= 16:
                        onu_ids.append(f"ONU_{data[0]}")
                        rx_power.append(data[15] if len(data) > 15 and data[15].strip() else "0.00")
            
            if onuid in onu_ids:
                index = onu_ids.index(onuid)
                return rx_power[index] if index < len(rx_power) else "0.00"
            return "0.00"
            
        elif action == "routermac":
            # Get system info
            headers_sys = headers.copy()
            headers_sys['Referer'] = f'http://{ip}/m/system_info.htm'
            
            payload = "get=sysinfo2&sysunit=1"
            url = f"http://{ip}/sw.cgi"
            response = requests.post(url, headers=headers_sys, data=payload, timeout=15)
            
            cpu = 0
            memory = 0
            
            if response.status_code == 200:
                try:
                    root = ET.fromstring(response.text)
                    cpu_elem = root.find(".//item[@cpu]")
                    memory_elem = root.find(".//item[@memory]")
                    
                    if cpu_elem is not None:
                        cpu = int(cpu_elem.attrib['cpu'].strip())
                    
                    if memory_elem is not None:
                        memory_str = memory_elem.attrib['memory']
                        parts = memory_str.split('?')
                        if len(parts) >= 2:
                            used = int(parts[1].strip())
                            total = int(parts[0].strip())
                            memory = round((used / total) * 100) if total > 0 else 0
                except Exception as e:
                    print(f"Error parsing system info: {e}", file=sys.stderr)
            
            # Get MAC addresses
            headers_mac = headers.copy()
            headers_mac['Referer'] = f'http://{ip}/m/olt_mac_fdb_tk.htm'
            
            payload = "get=oltfdb&sysUnit=undefined"
            url = f"http://{ip}/sw.cgi"
            response = requests.post(url, headers=headers_mac, data=payload, timeout=15)
            
            mac_addresses = []
            onu_ids = []
            
            if response.status_code == 200:
                try:
                    root = ET.fromstring(response.text)
                    for item in root.findall('item'):
                        mac_data = item.get('mac', '')
                        if mac_data:
                            data = mac_data.split(',')
                            if len(data) >= 2:
                                onu_ids.append(data[0])
                                mac_addresses.append(data[1])
                except Exception as e:
                    print(f"Error parsing MAC table: {e}", file=sys.stderr)
            
            result = {
                "olt": olt,
                "cpu": cpu,
                "memory": memory,
                "router_mac": mac_addresses,
                "onu_id": onu_ids
            }
            
            return json.dumps(result, indent=4)
            
    except Exception as e:
        print(f"Corelink v3 error: {e}", file=sys.stderr)
        return error_response(action, olt)

# ========= AUTO-DETECT VERSION =========
def corelink_auto(olt, ip, username, password, action, pon_ports, key, cmd, onuid):
    """Auto-detect Corelink firmware version and use appropriate function"""
    try:
        # First try v2/v3 (XML API)
        print(f"Trying Corelink v2/v3 API...", file=sys.stderr)
        headers = loginv2(ip, username, password)
        if headers:
            # Test if XML API works
            test_payload = "get=sysinfo2&sysunit=1"
            test_url = f"http://{ip}/sw.cgi"
            test_response = requests.post(test_url, headers=headers, data=test_payload, timeout=5)
            
            if test_response.status_code == 200:
                print(f"Detected Corelink v2/v3 (XML API)", file=sys.stderr)
                # Check for v3 specific endpoint
                if "/olt_mac_fdb_tk.htm" in test_response.url or "undefined" in test_payload:
                    return corelink_v3(olt, ip, username, password, action, pon_ports, key, cmd, onuid)
                else:
                    return corelink_v2(olt, ip, username, password, action, pon_ports, key, cmd, onuid)
        
        # Fall back to v1 (HTML/JS)
        print(f"Falling back to Corelink v1 (HTML/JS)...", file=sys.stderr)
        return corelink_v1(olt, ip, username, password, action, pon_ports, key, cmd, onuid)
        
    except Exception as e:
        print(f"Auto-detect error: {e}", file=sys.stderr)
        return error_response(action, olt)

# ========= CORE LINK V2/V3 HYBRID (WORKING VERSION) =========
def corelink_hybrid(olt, ip, username, password, action, pon_ports, key, cmd, onuid):
    """Hybrid function that works with v2/v3 XML API - TESTED AND WORKING"""
    try:
        headers = loginv2(ip, username, password)
        if not headers:
            return error_response(action, olt)
        
        current_date_time = now()
        
        if action == "onustatus":
            payload = "get=onualllist&sysUnit=1"
            url = f"http://{ip}/sw.cgi"
            
            # Use appropriate referer
            headers_onu = headers.copy()
            headers_onu['Referer'] = f'http://{ip}/m/onu_all_onu.htm'
            
            response = requests.post(url, headers=headers_onu, data=payload, timeout=10)
            
            if response.status_code != 200:
                print(f"Failed to get ONU list: {response.status_code}", file=sys.stderr)
                return error_response(action, olt)
                
            # Parse XML response
            try:
                root = ET.fromstring(response.text)
            except Exception as e:
                print(f"XML parse error: {e}", file=sys.stderr)
                print(f"Response: {response.text[:500]}", file=sys.stderr)
                return error_response(action, olt)
            
            olt_name = []
            onu_ids = []
            descriptions = []
            mac_addresses = []
            statuses = []
            rx_power = []
            distances = []
            register_times = []
            deregister_times = []
            deregister_reasons = []
            
            for item in root.findall('item'):
                onu_data = item.get('onu', '')
                if not onu_data:
                    continue
                    
                data = onu_data.split(',')
                if len(data) >= 10:
                    onu_ids.append(f"ONU_{data[0].strip()}")
                    descriptions.append(data[1].strip() if len(data) > 1 else "")
                    mac_addresses.append(data[2].strip() if len(data) > 2 else "00:00:00:00:00:00")
                    
                    status_text = data[3].strip().lower() if len(data) > 3 else ""
                    statuses.append("Online" if status_text == 'up' else "Offline")
                    
                    rx_value = data[15].strip() if len(data) > 15 and data[15].strip() else "0.00"
                    rx_power.append(rx_value)
                    
                    olt_name.append(olt)
                    distances.append(0)
                    register_times.append(current_date_time)
                    deregister_times.append(current_date_time)
                    
                    if len(data) > 18:
                        reason_code = data[18].strip()
                        deregister_reasons.append("Wire Down" if reason_code == "0" else "Power Off")
                    else:
                        deregister_reasons.append("Power Off")
            
            # Calculate counts
            online_onu = statuses.count("Online")
            offline_onu = sum(1 for i, status in enumerate(statuses) 
                            if status == "Offline" and deregister_reasons[i] == "Power Off")
            wire_down = sum(1 for i, status in enumerate(statuses) 
                          if status == "Offline" and deregister_reasons[i] == "Wire Down")
            
            data_dict = {
                "olt": olt_name,
                "onu_id": onu_ids,
                "status": statuses,
                "mac": mac_addresses,
                "des": descriptions,
                "rx": rx_power,
                "distance": distances,
                "last_register": register_times,
                "last_deregister": deregister_times,
                "reason": deregister_reasons,
                "olt_status": "Online",
                "online": online_onu,
                "offline": offline_onu,
                "wire_down": wire_down
            }
            
            return json.dumps(data_dict, indent=4)
            
        elif action == "rxpower":
            payload = "get=onualllist&sysUnit=1"
            url = f"http://{ip}/sw.cgi"
            
            headers_onu = headers.copy()
            headers_onu['Referer'] = f'http://{ip}/m/onu_all_onu.htm'
            
            response = requests.post(url, headers=headers_onu, data=payload, timeout=10)
            
            if response.status_code != 200:
                return "0.00"
                
            try:
                root = ET.fromstring(response.text)
            except:
                return "0.00"
            
            onu_ids = []
            rx_power = []
            
            for item in root.findall('item'):
                onu_data = item.get('onu', '')
                if onu_data:
                    data = onu_data.split(',')
                    if len(data) >= 16:
                        onu_ids.append(f"ONU_{data[0].strip()}")
                        rx_power.append(data[15].strip() if len(data) > 15 and data[15].strip() else "0.00")
            
            if onuid in onu_ids:
                index = onu_ids.index(onuid)
                return rx_power[index] if index < len(rx_power) else "0.00"
            return "0.00"
            
        elif action == "routermac":
            # Get system info
            headers_sys = headers.copy()
            headers_sys['Referer'] = f'http://{ip}/m/system_info.htm'
            
            payload = "get=sysinfo2&sysunit=1"
            url = f"http://{ip}/sw.cgi"
            response = requests.post(url, headers=headers_sys, data=payload, timeout=10)
            
            cpu = 0
            memory = 0
            
            if response.status_code == 200:
                try:
                    root = ET.fromstring(response.text)
                    cpu_elem = root.find(".//item[@cpu]")
                    memory_elem = root.find(".//item[@memory]")
                    
                    if cpu_elem is not None:
                        cpu = int(cpu_elem.attrib['cpu'].strip())
                    
                    if memory_elem is not None:
                        memory_str = memory_elem.attrib['memory']
                        parts = memory_str.split('?')
                        if len(parts) >= 2:
                            used = int(parts[1].strip())
                            total = int(parts[0].strip())
                            memory = round((used / total) * 100) if total > 0 else 0
                except:
                    pass
            
            # Get MAC addresses
            headers_mac = headers.copy()
            headers_mac['Referer'] = f'http://{ip}/m/olt_mac_fdb.htm'
            
            payload = "get=oltfdb&sysUnit=1"
            url = f"http://{ip}/sw.cgi"
            response = requests.post(url, headers=headers_mac, data=payload, timeout=10)
            
            mac_addresses = []
            onu_ids = []
            
            if response.status_code == 200:
                try:
                    root = ET.fromstring(response.text)
                    for item in root.findall('item'):
                        mac_data = item.get('mac', '')
                        if mac_data:
                            data = mac_data.split(',')
                            if len(data) >= 5:
                                onu_ids.append(data[0].strip())
                                mac_addresses.append(data[4].strip() if len(data) > 4 else "")
                except:
                    pass
            
            result = {
                "olt": olt,
                "cpu": cpu,
                "memory": memory,
                "router_mac": mac_addresses,
                "onu_id": onu_ids
            }
            
            return json.dumps(result, indent=4)
            
    except Exception as e:
        # print(f"Corelink hybrid error: {e}", file=sys.stderr)
        return error_response(action, olt)

# ========= MAIN ENTRY POINT =========
def main():
    """Main function for command-line usage"""
    if len(sys.argv) > 1:
        action = sys.argv[1]
        
        if action == "status":
            result = corelink_hybrid(
                OLT_NAME, IP, USERNAME, PASSWORD,
                "onustatus", 4, "", "23", ""
            )
            print(result)
        elif action == "mac":
            result = corelink_hybrid(
                OLT_NAME, IP, USERNAME, PASSWORD,
                "routermac", 4, "", "23", ""
            )
            print(result)
        elif action.startswith("rx:"):
            onu_id = action.replace("rx:", "")
            result = corelink_hybrid(
                OLT_NAME, IP, USERNAME, PASSWORD,
                "rxpower", 4, "", "23", onu_id
            )
            print(result)
        else:
            print(json.dumps({"error": "Unknown action"}))
    else:
        # Default: get ONU status
        result = corelink_hybrid(
            OLT_NAME, IP, USERNAME, PASSWORD,
            "onustatus", 4, "", "23", ""
        )
        print(result)

if __name__ == "__main__":
    main()