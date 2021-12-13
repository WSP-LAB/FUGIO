import json
import requests
import random
import copy 

class HTTPGen():
    def __init__(self, callback_data, overwrite_method, overwrite_key, encode='None'):
        
        log_data = copy.deepcopy(callback_data)
        self._url = "http://{}{}".format(
                log_data['SERVER']['HTTP_HOST'],
                log_data['SERVER']['SCRIPT_NAME'])
        self._method = log_data['SERVER']['REQUEST_METHOD']
        headers = dict()
        for server_key, server_value in log_data['SERVER'].items():
            if(server_key == "HTTP_COOKIE"):
                continue
            if(server_key[:5] == "HTTP_"):
                headers[server_key[5:]] = server_value

        rand_num = str(random.randint(1000000000, 9999999999))
        headers['X-Requested-With'] = "{}{}{}{}{}".format(rand_num,
                overwrite_method, rand_num,
                overwrite_key, rand_num,
                encode,
                rand_num)

        cookie_jar = dict()
        if(overwrite_method == "COOKIE"):
            for cookie_key, cookie_value in log_data['COOKIE'].items():
                if(cookie_key == overwrite_key):
                    if(encode == 'base64'):
                        cookie_jar[cookie_key] = "TzoxNzoiZHVtbXlfY2xhc3NfcjM1M3QiOjE6e3M6MTI6InVzZWRfbWV0aG9kcyI7YTowOnt9fQ=="
                    else:
                        cookie_jar[cookie_key] = 'O:17:"dummy_class_r353t":1:{s:12:"used_methods";a:0:{}}'
                else:
                    cookie_jar[cookie_key] = cookie_value
        else:
            if isinstance(log_data['COOKIE'], dict):
                for cookie_key, cookie_value in log_data['COOKIE'].items():
                    cookie_jar[cookie_key] = cookie_value

            if(encode == 'base64'):
                log_data[overwrite_method][overwrite_key] = "TzoxNzoiZHVtbXlfY2xhc3NfcjM1M3QiOjE6e3M6MTI6InVzZWRfbWV0aG9kcyI7YTowOnt9fQ=="
            else:
                log_data[overwrite_method][overwrite_key] = 'O:17:"dummy_class_r353t":1:{s:12:"used_methods";a:0:{}}'

        if(self._method == "GET"):
            r = requests.get(url=self._url, params = log_data['GET'],
                    headers = headers, cookies = cookie_jar,
                    allow_redirects = False)
        elif(self._method == "POST"):
            r = requests.post(url=self._url, params = log_data['GET'],
                    data = log_data['POST'],
                    headers = headers, cookies = cookie_jar,
                    allow_redirects = False)

