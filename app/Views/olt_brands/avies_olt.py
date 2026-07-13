import os
import requests
import base64
import json
from datetime import datetime
import sys

# action    = sys.argv[1]      # status / onu_list / rx:ONU02/11
IP        = sys.argv[2]
PORT      = sys.argv[3]
USERNAME  = sys.argv[4]
PASSWORD  = sys.argv[5]
LOGIN_KEY = sys.argv[6] if len(sys.argv) > 6 else ""

# # ========= CONFIG =========
IP = f"{IP}:{PORT}"

OLT_NAME = "AviesOLT"

# IP = "103.112.131.44:44"
BASE_URL = f"http://{IP}"
# USERNAME = "root"
# PASSWORD = "admin"
# LOGIN_KEY = "1761d487ba0cde5f285059b5cca9a22c"   # Change if needed



# Folder where tokens will be stored
TOKEN_DIR = "avies_tokens"

# Create the folder if it doesn't exist
os.makedirs(TOKEN_DIR, exist_ok=True)

# File path inside the folder
TOKEN_FILE = os.path.join(TOKEN_DIR, f"token_{IP.replace(':','_')}.txt")


# ========= HELPERS =========
def now():
    return datetime.now().strftime("%Y-%m-%d %H:%M:%S")


def save_token(token):
    with open(TOKEN_FILE, "w") as f:
        f.write(token)


def get_token():
    try:
        with open(TOKEN_FILE, "r") as f:
            return f.read().strip()
    except:
        return ""


# ========= LOGIN =========
def avies_login():
    value = base64.b64encode(PASSWORD.encode()).decode()

    data = {
        "method": "set",
        "param": {
            "name": USERNAME,
            "key": LOGIN_KEY,
            "value": value,
            "captcha_v": "",
            "captcha_f": "",
        },
    }

    url = f"{BASE_URL}/userlogin?form=login"

    response = requests.post(
        url,
        headers={"Content-Type": "text/plain"},
        data=json.dumps(data),
        timeout=10,
    )

    token = response.headers.get("X-Token")
    if token:
        save_token(token)
    return token


# ========= ONU STATUS =========
def get_onu_status():
    token = get_token()

    # Step 1: Check token via onu_allow_list
    headers = {"X-Token": token}
    r = requests.get(f"{BASE_URL}/onu_allow_list", headers=headers, timeout=10)
    code = r.json().get("code")

    if code == 0 or not token:
        token = avies_login()
        headers = {"X-Token": token}
        requests.get(f"{BASE_URL}/onu_allow_list", headers=headers, timeout=10)

    # Step 2: Get ONU table
    r = requests.get(f"{BASE_URL}/onutable", headers=headers, timeout=10)
    data = r.json().get("data", [])

    result = {
        "olt": [],
        "onu_id": [],
        "status": [],
        "mac": [],
        "name": [],
        "rx_power": [],
        "last_seen": [],
        "reason": [],
    }

    online = offline = wire_down = 0

    for e in data:
        onu_id = f"EPON0/{e['port_id']}:{e['onu_id']}"
        status = "Online" if e["status"] == "Online" else "Offline"

        if e["last_down_reason"] == "Laser out":
            reason = "Wire Down"
        else:
            reason = "Power Off"

        result["olt"].append(OLT_NAME)
        result["onu_id"].append(onu_id)
        result["status"].append(status)
        result["mac"].append(e["macaddr"].upper())
        result["name"].append(e["onu_name"])
        result["rx_power"].append(e["receive_power"])
        result["last_seen"].append(now())
        result["reason"].append(reason)

        if status == "Online":
            online += 1
        elif reason == "Wire Down":
            wire_down += 1
        else:
            offline += 1

    result["summary"] = {
        "online": online,
        "offline_power_off": offline,
        "offline_wire_down": wire_down,
        "olt_status": "Online",
    }

    print(json.dumps(result, indent=4))


# ========= RX POWER OF SINGLE ONU =========
def get_rx_power(onuid):
    token = get_token()
    headers = {"X-Token": token}
    
    response = requests.get(f"{BASE_URL}/onutable", headers=headers, timeout=10)
    
    # Check if the response is actually JSON
    try:
        data_json = response.json()
    except Exception as e:
        # If it fails, the token might be expired. Try re-logging.
        token = avies_login()
        headers = {"X-Token": token}
        response = requests.get(f"{BASE_URL}/onutable", headers=headers, timeout=10)
        try:
            data_json = response.json()
        except:
            print("0.00") # Final fallback
            return

    data = data_json.get("data", [])
    for e in data:
        onu_id = f"EPON0/{e['port_id']}:{e['onu_id']}"
        if onu_id.upper() == onuid.upper():
            print(e["receive_power"])
            return

    print("0.00")


# ========= ROUTER MAC + CPU =========
def get_router_mac():
    token = get_token()
    headers = {"X-Token": token}

    # 🔹 Step 1: Validate token using pon_mac age
    r = requests.get(f"{BASE_URL}/pon_mac?form=age", headers=headers, timeout=10)
    try:
        code = r.json().get("code")
    except:
        code = 0

    if code == 0 or not token:
        token = avies_login()
        headers = {"X-Token": token}
        requests.get(f"{BASE_URL}/pon_mac?form=age", headers=headers, timeout=10)

    # 🔹 Step 2: Trigger MAC table refresh
    requests.get(f"{BASE_URL}/pon_mac?form=table", headers=headers, timeout=10)

    # 🔹 Step 3: Get MAC table
    r = requests.get(f"{BASE_URL}/pon_mac_table", headers=headers, timeout=10)
    data = r.json().get("data", [])

    onu_ids = []
    macs = []

    for e in data:
        onu_ids.append(f"EPON0/{e['port_id']}:{e['onu_id']}")
        macs.append(e["macaddr"].upper())

    # 🔹 Step 4: CPU & Memory
    r = requests.get(f"{BASE_URL}/board?info=cpu", headers=headers, timeout=10)
    sysinfo = r.json().get("data", {})

    result = {
        "olt": OLT_NAME,
        "cpu": sysinfo.get("cpu_usage"),
        "memory": sysinfo.get("memory_usage"),
        "router_mac": macs,
        "onu_id": onu_ids,
    }

    print(json.dumps(result, indent=4))


# ========= MAIN =========
# if __name__ == "__main__":
#     print("Fetching ONU status...\n")
#     get_onu_status()

    # To use other features, uncomment:
    # get_rx_power("EPON0/1:1")
    # get_router_mac()

if __name__ == "__main__":
    # Check if PHP sent an argument
    if len(sys.argv) > 1:
        action = sys.argv[1]
        
        if action == "status":
            get_onu_status()
        elif action == "mac":
            get_router_mac()
        elif action.startswith("rx:"):
            # Example: "rx:EPON0/1:1"
            onu_id = action.replace("rx:", "")
            get_rx_power(onu_id)
        else:
            print(json.dumps({"error": "Unknown action"}))
    else:
        # Default fallback if no argument is passed
        get_onu_status()