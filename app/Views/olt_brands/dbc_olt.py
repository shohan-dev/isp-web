import requests
import json
import re
from datetime import datetime
import sys
from bs4 import BeautifulSoup
import urllib3

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# ========= CONFIG =========
OLT_NAME = "DBC_OLT"


IP_ARG    = sys.argv[2] if len(sys.argv) > 2 else "160.187.159.48"
PORT_ARG  = sys.argv[3] if len(sys.argv) > 3 else "4811"
USERNAME  = sys.argv[4] if len(sys.argv) > 4 else "shihab"
PASSWORD  = sys.argv[5] if len(sys.argv) > 5 else "shihab@1213"

IP        = f"{IP_ARG}:{PORT_ARG}"


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
def dbc_get_session(ip, username, password):
    """Login to dbc OLT and get session"""
    try:
        url = f"https://{ip}/action/main.html"
        payload = f'user={username}&pass={password}&button=Login&who=100'
        headers = {
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Connection': 'keep-alive',
            'Content-Type': 'application/x-www-form-urlencoded',
            'Origin': f'https://{ip}',
            'Pragma': 'no-cache',
            'Referer': f'https://{ip}/action/login.html'
        }

        response = requests.post(url, headers=headers, data=payload, timeout=60, verify=False)
        return response.text

    except Exception as e:
        return None

def dbc_login(ip, username, password):
    """Alternative login function for dbc"""
    try:
        url = f"https://{ip}/action/main.html"
        payload = f'user={username}&pass={password}&button=Submit&who=100'
        headers = {
            'Origin': f'https://{ip}',
            'Referer': f'https://{ip}/action/login.html',
            'Content-Type': 'application/x-www-form-urlencoded'
        }

        response = requests.post(url, headers=headers, data=payload, timeout=10, verify=False)
        return response.text

    except Exception as e:
        return None

# ========= dbc EPON =========
def dbc_epon(olt, ip, username, password, action, pon_ports, key, cmd, onuid):
    """For dbc EPON OLTs"""
    try:
        current_date_time = now()
        
        # First check if we need to login
        check_url = f"https://{ip}/action/onustatusinfo.html"
        check_payload = 'select=255&who=100'
        check_headers = {
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Connection': 'keep-alive',
            'Content-Type': 'application/x-www-form-urlencoded'
        }
        
        check_response = requests.get(check_url, headers=check_headers, data=check_payload, 
                                     timeout=60, verify=False)
        html_content = check_response.text
        soup = BeautifulSoup(html_content, 'html.parser')
        
        # Find the <body> tag
        body_tag = soup.find('body')
        if body_tag and not body_tag.text.strip():
            login_result = dbc_get_session(ip, username, password)
            if not login_result:
                return error_response(action, olt)
        
        if action == "onustatus":
            url = f"https://{ip}/action/onustatusinfo.html"
            payload = 'select=255&who=100'
            headers = {
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Connection': 'keep-alive',
                'Content-Type': 'application/x-www-form-urlencoded'
            }
            
            response = requests.get(url, headers=headers, data=payload, timeout=60, verify=False)
            html_content = response.text
            soup = BeautifulSoup(html_content, 'html.parser')
            
            # Find the second table (index 1)
            tables = soup.find_all('table')
            if len(tables) < 2:
                return error_response(action, olt)
                
            table = tables[1]
            
            olt_name = []
            onu_ids = []
            statuses = []
            mac_addresses = []
            distance = []
            descriptions = []
            rx_power = []
            register_times = []
            deregister_times = []
            deregister_reasons = []
            
            for row in table.find_all('tr')[1:]:  # Skip the header row
                cols = row.find_all('td')
                if len(cols) >= 9:
                    onu_ids.append(cols[0].text.strip())
                    statuses.append(cols[1].text.strip())
                    mac_addresses.append(cols[2].text.strip())
                    descriptions.append(cols[3].text.strip())
                    distance.append(cols[4].text.strip())
                    rx_power.append('0.00')
                    olt_name.append(olt)
                    register_times.append(current_date_time)
                    deregister_times.append(current_date_time)
                    deregister_reasons.append(cols[8].text.strip() if len(cols) > 8 else "Power Off")
            
            online_onu = 0
            offline_onu = 0
            wire_down = 0

            for index, item in enumerate(statuses):
                if item == "Online":
                    online_onu += 1
                elif item == "Offline" and deregister_reasons[index] == "Power Off":
                    offline_onu += 1
                elif item == "Offline" and deregister_reasons[index] == "Wire Down":
                    wire_down += 1
            
            data_dict = {
                "olt": olt_name,
                "onu_id": onu_ids,
                "status": statuses,
                "mac": mac_addresses,
                "distance": distance,
                "des": descriptions,
                "rx": rx_power,
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
            if onuid:
                try:
                    # Parse ONU ID: format should be like "1/1:1"
                    parts = onuid.split("/")
                    if len(parts) >= 2:
                        pon_onu = parts[1].split(":")
                        if len(pon_onu) >= 2:
                            pon = pon_onu[0]
                            onu = pon_onu[1]
                            
                            url = f"https://{ip}/action/onuBasic.html?gponid={pon}&gonuid={onu}"
                            headers = {'Connection': 'keep-alive'}
                            
                            response = requests.get(url, headers=headers, timeout=60, verify=False)
                            html_content = response.text
                            soup = BeautifulSoup(html_content, 'html.parser')

                            # Find the row containing "Receive Power"
                            receive_power_row = soup.find('td', {'class': 'hd'}, string='Receive Power')
                            if receive_power_row:
                                receive_power_value = receive_power_row.find_next('td').get_text(strip=True)
                                return receive_power_value
                            else:
                                return "0.00"
                except:
                    pass
            return "0.00"
        
        elif action == "routermac":
            # Get system info
            sys_url = f"https://{ip}/action/systeminfo.html"
            sys_payload = 'select=255&who=100'
            sys_headers = {
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Connection': 'keep-alive',
                'Content-Type': 'application/x-www-form-urlencoded'
            }
            
            sys_response = requests.get(sys_url, headers=sys_headers, data=sys_payload, 
                                       timeout=60, verify=False)
            sys_html = sys_response.text
            sys_soup = BeautifulSoup(sys_html, 'html.parser')
            
            cpu = 0
            memory = 0
            cpu_td = sys_soup.find('td', string='CPU Usage')
            memory_td = sys_soup.find('td', string='Memory Usage')
            
            if cpu_td:
                cpu_value = cpu_td.find_next('td').get_text(strip=True)
                cpu = int(cpu_value.strip('%')) if '%' in cpu_value else 0
            
            if memory_td:
                memory_value = memory_td.find_next('td').get_text(strip=True)
                memory = int(memory_value.strip('%')) if '%' in memory_value else 0
            
            # Get MAC table
            mac_url = f"https://{ip}/action/macinfo.html"
            mac_response = requests.get(mac_url, headers=sys_headers, timeout=60, verify=False)
            mac_html = mac_response.text
            mac_soup = BeautifulSoup(mac_html, 'html.parser')
            
            router_macs = []
            port_ids = []
            mac_table = mac_soup.find('table', {'border': '1'})
            
            if mac_table:
                rows = mac_table.find_all('tr')[1:]
                for row in rows:
                    cols = row.find_all('td')
                    if len(cols) >= 4:
                        router_macs.append(cols[1].text.strip())
                        port_ids.append(cols[3].text.strip())
            
            result = {
                "olt": olt,
                "cpu": cpu,
                "memory": memory,
                "router_mac": router_macs,
                "onu_id": port_ids
            }
            
            return json.dumps(result, indent=4)
        
        elif action == "opm":
            if pon_ports:
                ports = int(pon_ports)
                # Simple round-robin port selection
                import random
                pon = random.randint(1, ports) if ports > 0 else 1
                
                opm_url = f"https://{ip}/action/onuopmdiag.html"
                opm_payload = f'select={pon}&who=100'
                opm_headers = {
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                    'Connection': 'keep-alive',
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
                
                opm_response = requests.get(opm_url, headers=opm_headers, data=opm_payload, 
                                          timeout=60, verify=False)
                opm_html = opm_response.text
                opm_soup = BeautifulSoup(opm_html, 'html.parser')
                
                onu_ids = []
                onu_macs = []
                rx_power = []
                
                opm_table = opm_soup.find('table', {'border': '1'})
                if opm_table:
                    rows = opm_table.find_all('tr')[1:]
                    for row in rows:
                        cols = row.find_all('td')
                        if len(cols) >= 9:
                            onu_ids.append(cols[0].text.strip())
                            onu_macs.append(cols[1].text.strip())
                            rx_power.append(cols[8].text.strip())
                
                result = {
                    "onu_id": onu_ids,
                    "onu_mac": onu_macs,
                    "rx_power": rx_power
                }
                
                return json.dumps(result, indent=4)
            
            return error_response(action, olt)
        
        else:
            return error_response(action, olt)
            
    except Exception as e:
        return error_response(action, olt)

# ========= dbc GPON =========
def dbc_gpon(olt, ip, username, password, action, pon_ports, key, cmd, onuid):
    """For dbc GPON OLTs"""
    try:
        current_date_time = now()
        
        # Check session
        check_url = f"https://{ip}/action/onustatusinfo.html"
        check_response = requests.get(check_url, timeout=60, verify=False)
        check_soup = BeautifulSoup(check_response.text, 'html.parser')
        
        body_tag = check_soup.find('body')
        if body_tag and not body_tag.text.strip():
            login_result = dbc_login(ip, username, password)
            if not login_result:
                return error_response(action, olt)
        
        if action == "onustatus":
            url = f"https://{ip}/action/onustatusinfo.html"
            response = requests.get(url, timeout=60, verify=False)
            soup = BeautifulSoup(response.text, 'html.parser')
            
            olt_name = []
            onu_ids = []
            statuses = []
            mac_addresses = []
            descriptions = []
            rx_power = []
            register_times = []
            deregister_times = []
            deregister_reasons = []
            distances = []
            
            # Get PON ports from select element
            select_element = soup.find('select', id='s')
            if select_element:
                option_values = [option['value'] for option in select_element.find_all('option')]
                
                for value in option_values:
                    post_url = f"https://{ip}/action/onustatusinfo.html"
                    post_payload = f'select={value}'
                    post_headers = {
                        'Connection': 'keep-alive',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                    
                    post_response = requests.post(post_url, headers=post_headers, 
                                                 data=post_payload, timeout=60, verify=False)
                    post_soup = BeautifulSoup(post_response.text, 'html.parser')
                    
                    tables = post_soup.find_all('table')
                    if len(tables) >= 2:
                        table = tables[1]
                        
                        for row in table.find_all('tr')[1:]:
                            cols = row.find_all('td')
                            if len(cols) >= 4:
                                onu_ids.append(cols[0].text.strip())
                                
                                if cols[2].text.strip().lower() == "enable":
                                    statuses.append("Online")
                                else:
                                    statuses.append("Offline")
                                
                                mac_addresses.append(cols[0].text.strip())
                                descriptions.append("")
                                rx_power.append('0.00')
                                olt_name.append(olt)
                                distances.append(0)
                                register_times.append(current_date_time)
                                deregister_times.append(current_date_time)
                                
                                if cols[3].text.strip() == "Los":
                                    deregister_reasons.append("Wire Down")
                                else:
                                    deregister_reasons.append("Power Off")
            
            online_onu = 0
            offline_onu = 0
            wire_down = 0

            for index, item in enumerate(statuses):
                if item == "Online":
                    online_onu += 1
                elif item == "Offline" and deregister_reasons[index] == "Power Off":
                    offline_onu += 1
                elif item == "Offline" and deregister_reasons[index] == "Wire Down":
                    wire_down += 1
            
            data_dict = {
                "olt": olt_name,
                "onu_id": onu_ids,
                "status": statuses,
                "mac": mac_addresses,
                "des": descriptions,
                "rx": rx_power,
                "distances": distances,
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
            if onuid:
                try:
                    # Parse ONU ID: format should be like "1/1:1"
                    parts = onuid.split("/")
                    if len(parts) >= 2:
                        pon_onu = parts[1].split(":")
                        if len(pon_onu) >= 2:
                            pon = pon_onu[0]
                            onu = pon_onu[1]
                            
                            url = f"https://{ip}/action/onuoptical.html?ponid={pon}&onuid={onu}"
                            response = requests.get(url, timeout=60, verify=False)
                            soup = BeautifulSoup(response.text, 'html.parser')
                            
                            # Find Rx optical level
                            rx_row = soup.find('td', {'class': 'hd'}, string='Rx optical level')
                            if rx_row:
                                rx_value = rx_row.find_next('td').get_text(strip=True)
                                return rx_value
                except:
                    pass
            return "0.00"
        
        elif action == "routermac":
            # Get system info
            sys_url = f"https://{ip}/action/systeminfo.html"
            sys_response = requests.get(sys_url, timeout=60, verify=False)
            sys_soup = BeautifulSoup(sys_response.text, 'html.parser')
            
            cpu = 0
            memory = 0
            cpu_td = sys_soup.find('td', string='CPU Usage')
            memory_td = sys_soup.find('td', string='Memory Usage')
            
            if cpu_td:
                cpu_value = cpu_td.find_next('td').get_text(strip=True)
                cpu = int(cpu_value.strip('%')) if '%' in cpu_value else 0
            
            if memory_td:
                memory_value = memory_td.find_next('td').get_text(strip=True)
                memory = int(memory_value.strip('%')) if '%' in memory_value else 0
            
            # Get MAC table
            mac_url = f"https://{ip}/action/macinfoPon.html"
            mac_response = requests.get(mac_url, timeout=60, verify=False)
            mac_soup = BeautifulSoup(mac_response.text, 'html.parser')
            
            router_macs = []
            port_ids = []
            mac_table = mac_soup.find('table', {'border': '1'})
            
            if mac_table:
                rows = mac_table.find_all('tr')[1:]
                for row in rows:
                    cols = row.find_all('td')
                    if len(cols) >= 5:
                        router_macs.append(cols[2].text.strip().upper())
                        port_ids.append(f"GPON0/{cols[4].text.strip()}")
            
            result = {
                "olt": olt,
                "cpu": cpu,
                "memory": memory,
                "router_mac": router_macs,
                "onu_id": port_ids
            }
            
            return json.dumps(result, indent=4)
        
        elif action == "opm":
            if pon_ports:
                ports = int(pon_ports)
                # Simple round-robin port selection
                import random
                pon = random.randint(1, ports) if ports > 0 else 1
                
                onu_ids = []
                onu_macs = []
                rx_power = []
                
                # Check multiple ONU groups
                for group in range(4):
                    url = f"https://{ip}/action/pononuopticalinfo.html"
                    payload = f'pon={pon}&onu_group={group}&who=100&onuid=0'
                    headers = {'Content-Type': 'application/x-www-form-urlencoded'}
                    
                    response = requests.post(url, headers=headers, data=payload, 
                                           timeout=60, verify=False)
                    soup = BeautifulSoup(response.text, 'html.parser')
                    
                    table = soup.find('table', {'border': '1'})
                    if table:
                        rows = table.find_all('tr')[1:]
                        for row in rows:
                            cols = row.find_all('td')
                            if len(cols) >= 3:
                                onu_ids.append(cols[0].text.strip())
                                onu_macs.append(cols[0].text.strip())
                                rx_value = cols[2].text.strip().replace("(dBm)", "").replace("NULL", "00.0")
                                rx_power.append(rx_value)
                
                result = {
                    "onu_id": onu_ids,
                    "onu_mac": onu_macs,
                    "rx_power": rx_power
                }
                
                return json.dumps(result, indent=4)
            
            return error_response(action, olt)
        
        else:
            return error_response(action, olt)
            
    except Exception as e:
        return error_response(action, olt)

# ========= AUTO-DETECT dbc TYPE =========
def dbc_auto(olt, ip, username, password, action, pon_ports, key, cmd, onuid):
    """Auto-detect dbc OLT type and use appropriate function"""
    try:
        
        # First try EPON
        try:
            result = dbc_epon(olt, ip, username, password, action, pon_ports, key, cmd, onuid)
            if result and "ERROR" not in result:
                print("Detected dbc EPON", file=sys.stderr)
                return result
        except:
            pass
        
        # Then try GPON
        try:
            result = dbc_gpon(olt, ip, username, password, action, pon_ports, key, cmd, onuid)
            if result and "ERROR" not in result:
                print("Detected dbc GPON", file=sys.stderr)
                return result
        except:
            pass
        
        return error_response(action, olt)
        
    except Exception as e:
        return error_response(action, olt)

# ========= MAIN ENTRY POINT =========
def main():
    """Main function for command-line usage"""
    if len(sys.argv) > 1:
        action = sys.argv[1]
        
        if action == "status":
            result = dbc_auto(
                OLT_NAME, IP, USERNAME, PASSWORD,
                "onustatus", 4, "", "23", ""
            )
            print(result)
        elif action == "mac":
            result = dbc_auto(
                OLT_NAME, IP, USERNAME, PASSWORD,
                "routermac", 4, "", "23", ""
            )
            print(result)
        elif action.startswith("rx:"):
            onu_id = action.replace("rx:", "")
            result = dbc_auto(
                OLT_NAME, IP, USERNAME, PASSWORD,
                "rxpower", 4, "", "23", onu_id
            )
            print(result)
        elif action == "opm":
            result = dbc_auto(
                OLT_NAME, IP, USERNAME, PASSWORD,
                "opm", 4, "", "23", ""
            )
            print(result)
        else:
            print(json.dumps({"error": "Unknown action"}))
    else:
        # Default: get ONU status
        result = dbc_auto(
            OLT_NAME, IP, USERNAME, PASSWORD,
            "onustatus", 4, "", "23", ""
        )
        print(result)

if __name__ == "__main__":
    main()