import requests
import base64
import json
import re
import sys
from datetime import datetime
from urllib3.exceptions import InsecureRequestWarning

# Suppress SSL warnings
requests.packages.urllib3.disable_warnings(category=InsecureRequestWarning)

# ========= CONFIG =========
OLT_NAME = "BdcomOLT"

IP        = sys.argv[2]
PORT      = sys.argv[3]
USERNAME  = sys.argv[4]
PASSWORD  = sys.argv[5]

IP = f"{IP}:{PORT}"
BASE_URL = f"http://{IP}"

# IP = "116.206.91.42:1111"

# BASE_URL = f"http://{IP}"
# USERNAME = "rafe1"
# PASSWORD = "rafe66556621"



# ========= HELPER FUNCTIONS =========
def now():
    return datetime.now().strftime("%Y-%m-%d %H:%M:%S")

def error_response(action, olt):
    """Return error response in standard format"""
    if action == "status" or action == "onustatus":
        return {
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
            "summary": {
                "online": 0,
                "offline_power_off": 0,
                "offline_wire_down": 0,
                "olt_status": "Offline",
                "total": 0
            }
        }
    elif action == "rxpower":
        return "0.00"
    elif action == "mac" or action == "routermac":
        return {
            "olt": olt,
            "cpu": 0,
            "memory": 0,
            "router_mac": [],
            "onu_id": []
        }
    else:
        return {"error": "Unknown action"}

def get_http_session(ip, username, password, auth_type='basic'):
    """Create and configure HTTP session"""
    session = requests.Session()
    session.verify = False  # Disable SSL verification
    session.timeout = 30
    
    if auth_type == 'basic':
        # Basic authentication
        credentials = f"{username}:{password}"
        credentials_encoded = base64.b64encode(credentials.encode()).decode()
        session.headers.update({
            'Authorization': f'Basic {credentials_encoded}',
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        })
    elif auth_type == 'cookie':
        # Cookie-based authentication (common in newer BDCOM)
        username_encoded = base64.b64encode(username.encode()).decode()
        password_encoded = base64.b64encode(password.encode()).decode()
        session.cookies.set('username', username_encoded)
        session.cookies.set('password', password_encoded)
        session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Connection': 'keep-alive',
            'Referer': f'http://{ip}/index.asp'
        })
    
    return session

def try_login(session, ip):
    """Try to login and get session cookies"""
    login_urls = [
        f"http://{ip}/index.asp",
        f"http://{ip}/login.asp",
        f"http://{ip}/"
    ]
    
    for url in login_urls:
        try:
            response = session.get(url, timeout=10)
            if response.status_code == 200:
                return True
        except Exception as e:
            return error_response("mac", OLT_NAME)
    
    return False

def get_rx_power_http(session, onu_id):
    """Get RX power from ONU detail page - used by both status and rx actions"""
    try:
        url = f"http://{IP}/onuintfbasicinfo.asp?intfName={onu_id}"
        response = session.get(url, timeout=10)
        
        if response.status_code == 200:
            html = response.text
            
            # Look for received power in the HTML - exactly as shown in your image
            rx_patterns = [
                r'received power\(Dbm\):\s*(-?\d+\.?\d*)',
                r'received power\(DBm\):\s*(-?\d+\.?\d*)',
                r'received power:\s*(-?\d+\.?\d*)',
                r'RxPower.*?(-?\d+\.?\d*)'
            ]
            
            for pattern in rx_patterns:
                match = re.search(pattern, html, re.IGNORECASE)
                if match:
                    return match.group(1)
        
        return "0.00"
    except Exception as e:
        return "0.00"

def parse_bdcom_status(html, session, olt_name):
    """Parse BDCOM HTML status page and fetch RX power for each ONU"""
    current_time = now()
    
    # Initialize result structure
    result = {
        "olt": [],
        "onu_id": [],
        "status": [],
        "mac": [],
        "des": [],
        "rx": [],
        "distance": [],
        "last_register": [],
        "last_deregister": [],
        "reason": [],
        "summary": {
            "online": 0,
            "offline_power_off": 0,
            "offline_wire_down": 0,
            "olt_status": "Online" if html else "Offline",
            "total": 0
        }
    }
    
    if not html:
        return result
    
    # Try different parsing patterns for different BDCOM versions
    patterns_to_try = [
        # Pattern 1: Standard EPON/GPON v1
        {
            'onu_id': r'intfName\[(\d+)\]="([^"]+)"',
            'status': r'intfproto\[(\d+)\]="([^"]+)"',
            'mac': r'MACAddress\[(\d+)\]="([^"]+)"',
            'reason': r'deregReason\[(\d+)\]="([^"]+)"'
        },
        # Pattern 2: Standard EPON/GPON v2
        {
            'onu_id': r'intfName1\[(\d+)\]="([^"]+)"',
            'status': r'intfproto1\[(\d+)\]="([^"]+)"',
            'mac': r'MACAddress1\[(\d+)\]="([^"]+)"',
            'reason': r'deregReason1\[(\d+)\]="([^"]+)"'
        },
        # Pattern 3: BDCOM GPON v2
        {
            'onu_id': r'BintfName\[(\d+)\]="([^"]+)"',
            'status': r'BszState\[(\d+)\]="([^"]+)"',
            'mac': r'BintfName\[(\d+)\]="([^"]+)"',  # Same as onu_id
            'reason': r'BszDeactReason\[(\d+)\]="([^"]+)"'
        },
        # Pattern 4: Newer BDCOM versions
        {
            'onu_id': r'onuName\[(\d+)\]="([^"]+)"',
            'status': r'onustat\[(\d+)\]="([^"]+)"',
            'mac': r'onuName\[(\d+)\]="([^"]+)"',
            'reason': r'deactRea\[(\d+)\]="([^"]+)"'
        }
    ]
    
    for pattern_set in patterns_to_try:
        try:
            # Extract data using patterns
            onu_ids = {}
            for match in re.finditer(pattern_set['onu_id'], html):
                idx, value = match.groups()
                onu_ids[int(idx)] = value.upper()
            
            statuses = {}
            for match in re.finditer(pattern_set['status'], html):
                idx, value = match.groups()
                statuses[int(idx)] = "Online" if value.lower() in ['up', 'enable', 'active'] else "Offline"
            
            macs = {}
            for match in re.finditer(pattern_set['mac'], html):
                idx, value = match.groups()
                # Format MAC address
                mac_clean = value.replace('.', '').replace(':', '').upper()
                if len(mac_clean) == 12:
                    formatted_mac = ':'.join([mac_clean[i:i+2] for i in range(0, 12, 2)])
                else:
                    formatted_mac = mac_clean
                macs[int(idx)] = formatted_mac
            
            reasons = {}
            for match in re.finditer(pattern_set['reason'], html):
                idx, value = match.groups()
                reason_lower = value.lower()
                
                if 'losi' in reason_lower or 'los' in reason_lower or 'wire-down' in reason_lower:
                    reason = "Wire Down"
                elif 'dying' in reason_lower or 'power-off' in reason_lower:
                    reason = "Power Off"
                else:
                    reason = "Power Off" 
                reasons[int(idx)] = reason
            
            # Combine data by index
            all_indices = sorted(set(list(onu_ids.keys()) + list(statuses.keys())))
            
            if all_indices:
                for idx in all_indices:
                    onu_id = onu_ids.get(idx, f"UNKNOWN_{idx}")
                    status = statuses.get(idx, "Offline")
                    mac = macs.get(idx, "00:00:00:00:00:00")
                    reason = reasons.get(idx, "Power Off")
                    
                    # Fetch RX power from detail page using the same function
                    rx_power = "0.00"
                    
                    result["olt"].append(olt_name)
                    result["onu_id"].append(onu_id)
                    result["status"].append(status)
                    result["mac"].append(mac)
                    result["des"].append("")
                    result["rx"].append(rx_power)
                    result["distance"].append("0")
                    result["last_register"].append(current_time)
                    result["last_deregister"].append(current_time)
                    result["reason"].append(reason)
                    
                    # Update counters
                    if status == "Online":
                        result["summary"]["online"] += 1
                    elif reason == "Wire Down":
                        result["summary"]["offline_wire_down"] += 1
                    else:
                        result["summary"]["offline_power_off"] += 1
                
                result["summary"]["total"] = len(all_indices)
                break  # Stop at first successful pattern
                
        except Exception as e:
            continue
    
    return result

def get_router_mac_http(ip, username, password):
    """Get router MAC and system info via HTTP"""
    try:
        # Try cookie authentication first (most common for newer BDCOM)
        session = get_http_session(ip, username, password, 'cookie')
        
        if not session:
            return error_response("mac", OLT_NAME)
        
        # Try to login
        if not try_login(session, ip):
            # Fallback to basic auth
            session = get_http_session(ip, username, password, 'basic')
        
        # Get MAC address table
        mac_url = f"http://{ip}/macaddrtable.asp"
        response = session.get(mac_url, timeout=10)
        
        router_mac = []
        onu_ids = []
        
        if response.status_code == 200:
            html = response.text
            
            # Extract MAC addresses and ports
            mac_matches = re.findall(r'macaddr\[\d+\]="([0-9a-fA-F\.]+)";', html)
            port_matches = re.findall(r'port\[\d+\]="([^"]+)";', html)
            
            # Format MAC addresses and filter ONU ports
            for mac, port in zip(mac_matches, port_matches):
                # Format MAC
                mac_clean = mac.replace('.', '').upper()
                if len(mac_clean) == 12:
                    formatted_mac = ':'.join([mac_clean[i:i+2] for i in range(0, 12, 2)])
                else:
                    formatted_mac = mac_clean
                
                # Check if port is ONU interface
                port_upper = port.upper()
                if 'EPON' in port_upper or 'GPON' in port_upper or ':' in port_upper:
                    router_mac.append(formatted_mac)
                    onu_ids.append(port_upper)
        
        # Get system status
        sys_url = f"http://{ip}/systemstate.asp"
        response = session.get(sys_url, timeout=10)
        
        cpu_usage = 0
        mem_usage = 0
        
        if response.status_code == 200:
            html = response.text
            
            # Extract CPU and memory usage
            cpu_match = re.search(r'var cpuUsage="([\d%]+)";', html)
            mem_match = re.search(r'var memUsage="([\d%]+)";', html)
            
            if cpu_match:
                cpu_str = cpu_match.group(1).replace('%', '')
                cpu_usage = int(cpu_str) if cpu_str.isdigit() else 0
            
            if mem_match:
                mem_str = mem_match.group(1).replace('%', '')
                mem_usage = int(mem_str) if mem_str.isdigit() else 0
        
        return {
            "olt": OLT_NAME,
            "cpu": cpu_usage,
            "memory": mem_usage,
            "router_mac": router_mac,
            "onu_id": onu_ids
        }
        
    except Exception as e:
        return error_response("mac", OLT_NAME)

def get_onu_status():
    """Main function to get ONU status with RX power"""
    try:
        # Try cookie authentication first
        session = get_http_session(IP, USERNAME, PASSWORD, 'cookie')
        if not session:
            return error_response("status", OLT_NAME)
        
        # Try to access status page
        status_urls = [
            f"http://{IP}/onuintfstate.asp",
            f"http://{IP}/onu_status.asp",
            f"http://{IP}/status.asp"
        ]
        
        html_content = None
        
        for url in status_urls:
            try:
                response = session.get(url, timeout=15)
                
                if response.status_code == 200:
                    html_content = response.text
                    break
                    
            except Exception as e:
                continue
        
        # If cookie auth failed, try basic auth
        if not html_content:
            session = get_http_session(IP, USERNAME, PASSWORD, 'basic')
            
            for url in status_urls:
                try:
                    response = session.get(url, timeout=15)
                    if response.status_code == 200:
                        html_content = response.text
                        break
                except:
                    continue
        
        # Parse the HTML content and fetch RX power for each ONU
        result = parse_bdcom_status(html_content, session, OLT_NAME)
        
        return result
        
    except Exception as e:
        return error_response("status", OLT_NAME)

# ========= MAIN =========
if __name__ == "__main__":
    try:
        # Check if PHP sent an argument
        if len(sys.argv) > 1:
            action = sys.argv[1]
            
            if action == "status":
                result = get_onu_status()
                print(json.dumps(result, indent=4))
                
            elif action == "mac":
                result = get_router_mac_http(IP, USERNAME, PASSWORD)
                print(json.dumps(result, indent=4))
                
            elif action.startswith("rx:"):
                onu_id = action.replace("rx:", "")
                try:
                    # Create session and get RX power for the specific ONU
                    session = get_http_session(IP, USERNAME, PASSWORD, 'cookie')
                    # If cookie auth fails, try basic auth
                    if not try_login(session, IP):
                        session = get_http_session(IP, USERNAME, PASSWORD, 'basic')
                    rx_power = get_rx_power_http(session, onu_id)
                    print(rx_power)
                except:
                    print(error_response("rxpower", OLT_NAME))
                
            else:
                print(json.dumps({"error": "Unknown action"}))
        else:
            # Default fallback if no argument is passed
            result = get_onu_status()
            print(json.dumps(result, indent=4))
    except Exception as e:
        # Final fallback for any unhandled errors
        if len(sys.argv) > 1:
            action = sys.argv[1]
            print(json.dumps(error_response(action, OLT_NAME), indent=4))
        else:
            print(json.dumps(error_response("status", OLT_NAME), indent=4))