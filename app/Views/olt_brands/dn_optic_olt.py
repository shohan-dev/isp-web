import requests
import json
import hashlib
from datetime import datetime
import sys
import urllib3

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

OLT_NAME = "DN_OPTIC_OLT"

IP_ARG    = sys.argv[2] if len(sys.argv) > 2 else "160.191.83.178"
PORT_ARG  = sys.argv[3] if len(sys.argv) > 3 else "2633"
USERNAME  = sys.argv[4] if len(sys.argv) > 4 else "Chowara"
PASSWORD  = sys.argv[5] if len(sys.argv) > 5 else "Chowara3545"

IP        = f"{IP_ARG}:{PORT_ARG}"

def now():
    return datetime.now().strftime("%Y-%m-%d %H:%M:%S")

def error_response(action, olt):
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

def dn_optic_handler(olt, ip, username, password, action, onuid=""):
    try:
        current_date_time = now()
        session = requests.Session()
        
        # 1. Login via cgi-bin JSON API
        md5_pass = hashlib.md5(password.encode('utf-8')).hexdigest()
        login_url = f"http://{ip}/cgi-bin/h.cgi?module=sys_login"
        login_resp = session.post(login_url, json={"Usrname": username, "Password": md5_pass}, timeout=15)
        
        if login_resp.status_code != 200:
            return error_response(action, olt)
            
        login_data = login_resp.json()
        if login_data.get("code") != 0 or "data" not in login_data or "token" not in login_data["data"]:
            return error_response(action, olt)
            
        token = login_data["data"]["token"]
        headers = {"token": token}
        
        if action == "onustatus":
            # Fetch ONUs and Optical Info
            opt_url = f"http://{ip}/cgi-bin/h.cgi?module=ont_optical_list_get&PageSize=500&PageNumber=1"
            opt_resp = session.get(opt_url, headers=headers, timeout=25)
            
            if opt_resp.status_code != 200:
                return error_response(action, olt)
                
            opt_json = opt_resp.json()
            if opt_json.get("code") != 0:
                return error_response(action, olt)
                
            onu_list = opt_json.get("data", {}).get("list", [])
            
            olt_names = []
            onu_ids = []
            statuses = []
            mac_addresses = []
            distances = []
            descriptions = []
            rx_powers = []
            register_times = []
            deregister_times = []
            deregister_reasons = []
            
            online_onu = 0
            offline_onu = 0
            wire_down = 0
            
            for item in onu_list:
                pon_id = item.get("PonId", "")
                onu_num = item.get("OnuId", "")
                full_onu_id = f"EPON{pon_id}:{onu_num}"
                
                rx = item.get("RxPower", 0.0)
                mac = item.get("Mac", "00:00:00:00:00:00")
                desc = item.get("OnuDesc") or item.get("OnuName") or ""
                dist = item.get("Range", 0)
                
                # Signal thresholds for online/offline
                if rx != 0 and rx != 0.0 and rx != -40.0:
                    status = "Online"
                    reason = "Power Off"
                    online_onu += 1
                else:
                    status = "Offline"
                    reason = "Wire Down"
                    wire_down += 1
                
                olt_names.append(olt)
                onu_ids.append(full_onu_id)
                statuses.append(status)
                mac_addresses.append(mac)
                distances.append(str(dist))
                descriptions.append(desc)
                rx_powers.append(f"{rx:.2f}" if isinstance(rx, (int, float)) else str(rx))
                register_times.append(current_date_time)
                deregister_times.append(current_date_time)
                deregister_reasons.append(reason)
                
            return json.dumps({
                "olt": olt_names,
                "onu_id": onu_ids,
                "status": statuses,
                "mac": mac_addresses,
                "distance": distances,
                "des": descriptions,
                "rx": rx_powers,
                "last_register": register_times,
                "last_deregister": deregister_times,
                "reason": deregister_reasons,
                "olt_status": "Online",
                "online": online_onu,
                "offline": offline_onu,
                "wire_down": wire_down
            }, indent=4)

        elif action == "routermac":
            opt_url = f"http://{ip}/cgi-bin/h.cgi?module=ont_optical_list_get&PageSize=500&PageNumber=1"
            opt_resp = session.get(opt_url, headers=headers, timeout=25)
            
            router_macs = []
            onu_ids = []
            
            if opt_resp.status_code == 200 and opt_resp.json().get("code") == 0:
                onu_list = opt_resp.json().get("data", {}).get("list", [])
                for item in onu_list:
                    pon_id = item.get("PonId", "")
                    onu_num = item.get("OnuId", "")
                    mac = item.get("Mac", "")
                    if mac:
                        router_macs.append(mac)
                        onu_ids.append(f"EPON{pon_id}:{onu_num}")
                        
            return json.dumps({
                "olt": olt,
                "cpu": 0,
                "memory": 0,
                "router_mac": router_macs,
                "onu_id": onu_ids
            }, indent=4)

        elif action == "rxpower":
            if onuid:
                opt_url = f"http://{ip}/cgi-bin/h.cgi?module=ont_optical_list_get&PageSize=500&PageNumber=1"
                opt_resp = session.get(opt_url, headers=headers, timeout=15)
                if opt_resp.status_code == 200 and opt_resp.json().get("code") == 0:
                    onu_list = opt_resp.json().get("data", {}).get("list", [])
                    for item in onu_list:
                        pon_id = item.get("PonId", "")
                        onu_num = str(item.get("OnuId", ""))
                        if onuid in f"{pon_id}:{onu_num}" or onuid in str(item.get("Mac", "")):
                            return f"{item.get('RxPower', 0.0):.2f}"
            return "0.00"

        return error_response(action, olt)

    except Exception as e:
        return error_response(action, olt)

def main():
    if len(sys.argv) > 1:
        action = sys.argv[1]
        
        if action == "status":
            print(dn_optic_handler(OLT_NAME, IP, USERNAME, PASSWORD, "onustatus"))
        elif action == "mac":
            print(dn_optic_handler(OLT_NAME, IP, USERNAME, PASSWORD, "routermac"))
        elif action.startswith("rx:"):
            onu_id = action.replace("rx:", "")
            print(dn_optic_handler(OLT_NAME, IP, USERNAME, PASSWORD, "rxpower", onu_id))
        else:
            print(json.dumps({"error": "Unknown action"}))
    else:
        print(dn_optic_handler(OLT_NAME, IP, USERNAME, PASSWORD, "onustatus"))

if __name__ == "__main__":
    main()