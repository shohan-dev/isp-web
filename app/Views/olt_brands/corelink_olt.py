import requests
import base64
import json
import time
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
    """Login for Corelink v1 firmware using session-based login.
    The OLT redirects /index.asp to its internal LAN IP (192.168.91.2),
    so we must use / as entry point and POST to /sw.cgi with uni_mars_ap.
    """
    try:
        sess = requests.Session()
        sess.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Accept': '*/*',
        })

        # Step 1: Hit root page to warm up connection (don't follow redirects)
        sess.get(f"http://{ip}/", timeout=8, allow_redirects=False)
        time.sleep(0.3)

        # Step 2: POST login to /sw.cgi (same as the OLT's JS does via XHR)
        # The OLT JS encodes as base64(user + '&' + pass)
        credentials = f"{username}&{password}"
        credentials_encoded = base64.b64encode(credentials.encode('utf-8')).decode('utf-8')
        login_payload = f"set=login&user={credentials_encoded}"
        login_headers = {
            'Content-type': 'uni_mars_ap',
            'Referer': f'http://{ip}/',
            'Origin': f'http://{ip}',
            'X-Requested-With': 'XMLHttpRequest',
        }
        resp = sess.post(f"http://{ip}/sw.cgi", data=login_payload,
                         headers=login_headers, timeout=10, allow_redirects=False)

        if resp.status_code == 200:
            return sess  # Return session with cookies
        else:
            sys.stderr.write(f"loginv1 sw.cgi returned {resp.status_code}\n")
            return None
    except Exception as e:
        sys.stderr.write(f"loginv1 failed: {e}\n")
        return None

def loginv2(ip, username, password):
    """Login for Corelink v2/v3 firmware (XML API) - returns session or None."""
    credentials = f"{username}&{password}"
    credentials_encoded = base64.b64encode(credentials.encode('utf-8')).decode('utf-8')
    url = f"http://{ip}/sw.cgi"
    payload = f"set=login&user={credentials_encoded}"

    sess = requests.Session()
    sess.headers.update({
        'Accept': '*/*',
        'Content-type': 'uni_mars_ap',
        'Origin': f'http://{ip}',
        'Referer': f'http://{ip}/',
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'X-Requested-With': 'XMLHttpRequest',
        'Connection': 'keep-alive'
    })

    for attempt in range(3):
        try:
            response = sess.post(url, data=payload, timeout=10, allow_redirects=False)
            sys.stderr.write(f"DEBUG LOGIN: status={response.status_code}, content={response.text[:200]}\n")

            if response.status_code == 200:
                # value="-1" means wrong credentials; value="0" means success
                if 'value="-1"' in response.text or "value='-1'" in response.text:
                    sys.stderr.write("loginv2: credentials rejected by OLT (value=-1)\n")
                    return None
                return sess

            # 302 = session already active / wrong version
            if response.status_code == 302:
                sys.stderr.write(f"loginv2: got 302 (session busy or wrong version), attempt {attempt+1}/3\n")
                if attempt < 2:
                    wait = 3
                    sys.stderr.write(f"loginv2: waiting {wait}s before retry...\n")
                    time.sleep(wait)
                    continue
                else:
                    return None

            # 500 = wrong version
            if response.status_code == 500:
                sys.stderr.write(f"loginv2: got 500, not a v2/v3 OLT\n")
                return None

        except requests.exceptions.ConnectionError as e:
            # RemoteDisconnected = OLT slammed connection (session locked or busy)
            sys.stderr.write(f"loginv2: OLT closed connection (session busy or locked): {e}\n")
            if attempt < 2:
                wait = (attempt + 1) * 3
                sys.stderr.write(f"loginv2: waiting {wait}s before retry {attempt+2}/3...\n")
                time.sleep(wait)
            else:
                return None

        except Exception as e:
            sys.stderr.write(f"loginv2 attempt {attempt+1} failed: {e}\n")
            if attempt < 2:
                time.sleep(1.5)

    return None

def logoutv2(ip, sess):
    """Logout session from Corelink OLT"""
    try:
        url = f"http://{ip}/sw.cgi"
        sess.post(url, data="set=logout", headers={'Content-type': 'uni_mars_ap'}, timeout=5, allow_redirects=False)
    except Exception as e:
        sys.stderr.write(f"logoutv2 failed: {e}\n")

# ========= CORE LINK V1 (HTML/JavaScript) =========
def corelink_v1(olt, ip, username, password, action, pon_ports, key, cmd, onuid):
    """For Corelink firmware version 1 (HTML/JS based).
    Uses session-based login to avoid being redirected to the OLT's internal LAN IP.
    """
    try:
        sess = loginv1(ip, username, password)
        if not sess:
            return error_response(action, olt)
        
        current_date_time = now()
        
        if action == "onustatus":
            url = f"http://{ip}/onuAllPonOnuList.asp"
            response = sess.get(url, timeout=10, allow_redirects=False)
            # If 302 redirect to internal IP, login failed - retry with longer wait
            if response.status_code in (301, 302):
                sys.stderr.write(f"v1 onustatus: got {response.status_code} redirect, session not established\n")
                return error_response(action, olt)
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
            response = sess.get(url, timeout=10, allow_redirects=False)
            if response.status_code in (301, 302):
                return "0.00"
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
            response = sess.get(url, timeout=10, allow_redirects=False)
            if response.status_code in (301, 302):
                return error_response(action, olt)
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
            response2 = sess.get(url2, timeout=10)
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
    """Auto-detect Corelink firmware version and use appropriate function.
    Login is performed ONCE here and reused - do NOT login again inside corelink_hybrid.
    """
    print(f"Trying Corelink v2/v3 API...", file=sys.stderr)
    auth_headers = loginv2(ip, username, password)
    if auth_headers:
        print(f"Detected Corelink v2/v3 (XML API)", file=sys.stderr)
        # Minimal pause - don't sleep long or NAT will drop the TCP connection
        time.sleep(0.1)
        return corelink_hybrid(olt, ip, username, password, action, pon_ports, key, cmd, onuid, auth_headers=auth_headers)
    
    # Fall back to v1 (HTML/JS)
    print(f"Falling back to Corelink v1 (HTML/JS)...", file=sys.stderr)
    return corelink_v1(olt, ip, username, password, action, pon_ports, key, cmd, onuid)

def corelink_hybrid(olt, ip, username, password, action, pon_ports, key, cmd, onuid, auth_headers=None):
    """Hybrid function that works with v2/v3 XML API.
    If auth_headers is provided (from corelink_auto), login is SKIPPED to avoid double-login OLT overload.
    """
    try:
        url = f"http://{ip}/sw.cgi"

        # Only login if we don't already have auth headers
        if auth_headers is None:
            credentials = f"{username}&{password}"
            credentials_encoded = base64.b64encode(credentials.encode('utf-8')).decode('utf-8')
            login_payload = f"set=login&user={credentials_encoded}"
            auth_headers = {
                'Accept': '*/*',
                'Content-type': 'uni_mars_ap',
                'Origin': f'http://{ip}',
                'Referer': f'http://{ip}/',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'X-Requested-With': 'XMLHttpRequest',
                'Connection': 'close'
            }
            for attempt in range(3):
                try:
                    login_resp = requests.post(url, headers=auth_headers, data=login_payload, timeout=10, allow_redirects=False)
                    if login_resp.status_code == 200:
                        break
                except Exception as e:
                    if attempt == 2:
                        sys.stderr.write(f"Login failed after 3 attempts: {e}\n")
                        return error_response(action, olt)
                    time.sleep(1.5)

        current_date_time = now()
        
        if action == "onustatus":
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
            
            # New diagnostic lists for PHP side
            voltage_list = []
            temp_list = []
            bias_list = []
            tx_power_list = []
            vendor_list = []
            
            headers_onu = {
                'Accept': '*/*',
                'Content-type': 'uni_mars_ap',
                'Origin': f'http://{ip}',
                'Referer': f'http://{ip}/m/onu_all_onu.htm',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Connection': 'keep-alive'
            }
            
            # Query all ports in a single API call to prevent OLT lockup
            payload = "get=onualllist&sysUnit=1"
            
            response = None
            for attempt in range(3):
                try:
                    response = auth_headers.post(url, headers=headers_onu, data=payload, timeout=20, allow_redirects=False)
                    sys.stderr.write(f"DEBUG: OLT Response Status: {response.status_code}\n")
                    if response.status_code == 200:
                        break
                except Exception as e:
                    sys.stderr.write(f"Error fetching Corelink ONUs attempt {attempt+1}: {e}\n")
                    if attempt == 2:
                        sys.stderr.write(f"Error fetching Corelink ONUs after 3 attempts: {e}\n")
                        if auth_headers and isinstance(auth_headers, requests.Session):
                            logoutv2(ip, auth_headers)
                        return error_response(action, olt)
                    time.sleep(1.0)
            
            try:
                if response and response.status_code == 200:
                    root = ET.fromstring(response.text)
                    for item in root.findall('item'):
                        onu_data = item.get('onu', '')
                        if not onu_data:
                            continue
                        data = onu_data.split(',')
                        if len(data) >= 10:
                            # Format prefix: e.g. "ONU_1/1"
                            onu_ids.append(f"ONU_{data[0].strip()}")
                            descriptions.append(data[1].strip() if len(data) > 1 else "")
                            mac_addresses.append(data[2].strip() if len(data) > 2 else "00:00:00:00:00:00")
                            
                            status_text = data[3].strip().lower() if len(data) > 3 else ""
                            is_online = status_text == 'up'
                            statuses.append("Online" if is_online else "Offline")
                            
                            # Distance at index 6 or 7
                            dist_val = 0
                            if len(data) > 6 and data[6].strip().isdigit():
                                dist_val = int(data[6].strip())
                            elif len(data) > 7 and data[7].strip().isdigit():
                                dist_val = int(data[7].strip())
                            distances.append(dist_val)
                            
                            # Temp at index 9: e.g. 485 -> 48.5
                            temp_val = None
                            if len(data) > 9 and data[9].strip() not in ["", "-", "--"]:
                                val = data[9].strip()
                                if val.replace('-', '').isdigit():
                                    temp_val = str(round(float(val) / 10.0, 1))
                                else:
                                    temp_val = val
                            temp_list.append(temp_val)
                            
                            # Supply Voltage at index 10: e.g. 332 -> 3.32
                            volts_val = None
                            if len(data) > 10 and data[10].strip() not in ["", "-", "--"]:
                                val = data[10].strip()
                                if val.isdigit():
                                    volts_val = str(round(float(val) / 100.0, 2))
                                else:
                                    volts_val = val
                            voltage_list.append(volts_val)

                            # Bias current at index 11: e.g. 273 -> 27.3
                            bias_val = None
                            if len(data) > 11 and data[11].strip() not in ["", "-", "--"]:
                                val = data[11].strip()
                                if val.isdigit():
                                    bias_val = str(round(float(val) / 10.0, 1))
                                else:
                                    bias_val = val
                            bias_list.append(bias_val)
                            
                            # Tx Power at index 12: e.g. 222 -> 2.22
                            tx_val = None
                            if len(data) > 12 and data[12].strip() not in ["", "-", "--"]:
                                val = data[12].strip()
                                if val.replace('-', '').isdigit():
                                    tx_val = str(round(float(val) / 100.0, 2))
                                else:
                                    tx_val = val
                            tx_power_list.append(tx_val)
                            
                            # Rx Power at index 13: e.g. -19.98
                            rx_val = "0.00"
                            if len(data) > 13 and data[13].strip() not in ["", "-", "--"]:
                                rx_val = data[13].strip()
                            rx_power.append(rx_val)
                            
                            # Defaults for not supported fields
                            vendor_list.append("")
                            
                            olt_name.append(olt)
                            register_times.append(current_date_time)
                            deregister_times.append(current_date_time)
                            
                            # Deregister reason (use Status to default, or reason_code at index 18 if present)
                            if len(data) > 18:
                                reason_code = data[18].strip()
                                deregister_reasons.append("Wire Down" if reason_code == "0" else "Power Off")
                            else:
                                deregister_reasons.append("Power Off")
            except Exception as e:
                sys.stderr.write(f"Error fetching Corelink ONUs: {e}\n")
                return error_response(action, olt)
                    
            # Fallback if nothing was fetched
            if not onu_ids:
                return error_response(action, olt)
            
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
                "voltage": voltage_list,
                "temp": temp_list,
                "bias": bias_list,
                "tx_power": tx_power_list,
                "vendor": vendor_list,
                "olt_status": "Online",
                "online": online_onu,
                "offline": offline_onu,
                "wire_down": wire_down
            }
            
            if auth_headers and isinstance(auth_headers, requests.Session):
                logoutv2(ip, auth_headers)
            return json.dumps(data_dict, indent=4)
            
        elif action == "rxpower":
            payload = "get=onualllist&sysUnit=1"
            headers_onu = {
                'Accept': '*/*',
                'Origin': f'http://{ip}',
                'Referer': f'http://{ip}/m/onu_all_onu.htm',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Connection': 'keep-alive'
            }
            
            response = auth_headers.post(url, headers=headers_onu, data=payload, timeout=10, allow_redirects=False)
            
            if response.status_code != 200:
                if auth_headers and isinstance(auth_headers, requests.Session):
                    logoutv2(ip, auth_headers)
                return "0.00"
                
            try:
                root = ET.fromstring(response.text)
            except:
                if auth_headers and isinstance(auth_headers, requests.Session):
                    logoutv2(ip, auth_headers)
                return "0.00"
            
            onu_ids = []
            rx_power = []
            
            for item in root.findall('item'):
                onu_data = item.get('onu', '')
                if onu_data:
                    data = onu_data.split(',')
                    if len(data) >= 14:
                        onu_ids.append(f"ONU_{data[0].strip()}")
                        rx_power.append(data[13].strip() if len(data) > 13 and data[13].strip() else "0.00")
            
            if onuid in onu_ids:
                index = onu_ids.index(onuid)
                res = rx_power[index] if index < len(rx_power) else "0.00"
                if auth_headers and isinstance(auth_headers, requests.Session):
                    logoutv2(ip, auth_headers)
                return res
            if auth_headers and isinstance(auth_headers, requests.Session):
                logoutv2(ip, auth_headers)
            return "0.00"
            
        elif action == "routermac":
            # Get system info
            headers_sys = {
                'Accept': '*/*',
                'Origin': f'http://{ip}',
                'Referer': f'http://{ip}/m/system_info.htm',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Connection': 'keep-alive'
            }
            
            payload = "get=sysinfo2&sysunit=1"
            response = auth_headers.post(url, headers=headers_sys, data=payload, timeout=10, allow_redirects=False)
            
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
            headers_mac = {
                'Accept': '*/*',
                'Origin': f'http://{ip}',
                'Referer': f'http://{ip}/m/olt_mac_fdb.htm',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Connection': 'keep-alive'
            }
            
            payload = "get=oltfdb&sysUnit=1"
            response = auth_headers.post(url, headers=headers_mac, data=payload, timeout=10, allow_redirects=False)
            
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
            
            if auth_headers and isinstance(auth_headers, requests.Session):
                logoutv2(ip, auth_headers)
            return json.dumps(result, indent=4)
            
    except Exception as e:
        if auth_headers and isinstance(auth_headers, requests.Session):
            logoutv2(ip, auth_headers)
        # print(f"Corelink hybrid error: {e}", file=sys.stderr)
        return error_response(action, olt)

# ========= MAIN ENTRY POINT =========
def main():
    """Main function for command-line usage"""
    if len(sys.argv) > 1:
        action = sys.argv[1]
        
        if action == "status":
            result = corelink_auto(
                OLT_NAME, IP, USERNAME, PASSWORD,
                "onustatus", 4, "", "23", ""
            )
            print(result)
        elif action == "mac":
            result = corelink_auto(
                OLT_NAME, IP, USERNAME, PASSWORD,
                "routermac", 4, "", "23", ""
            )
            print(result)
        elif action.startswith("rx:"):
            onu_id = action.replace("rx:", "")
            result = corelink_auto(
                OLT_NAME, IP, USERNAME, PASSWORD,
                "rxpower", 4, "", "23", onu_id
            )
            print(result)
        else:
            print(json.dumps({"error": "Unknown action"}))
    else:
        # Default: get ONU status
        result = corelink_auto(
            OLT_NAME, IP, USERNAME, PASSWORD,
            "onustatus", 4, "", "23", ""
        )
        print(result)

if __name__ == "__main__":
    main()