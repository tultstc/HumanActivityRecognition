import requests
import logging

class ApiService:
    def __init__(self, base_url):
        self.base_url = base_url
        self.logger = logging.getLogger(__name__)

    def get_camera_list(self):
        url = f"{self.base_url}/api/cameras"
        headers = {}
        response = requests.get(url, headers=headers)
        data = response.json()
        cameralist = []
        # print(f"Processing camera list {data}")
        # Check if the API response is a list
        if isinstance(data, list):
            for camera in data:
                # if camera.get("status") == 1:
                cameralist.append(camera)
        return cameralist

    def get_model_list(self):
        modellist = []
        try:
            url = f"{self.base_url}/api/models"
            headers = {}
            response = requests.get(url, headers=headers)
            data = response.json()            
            # print(f"Processing model list {data}")
            # Check if the API response is a list
            if isinstance(data, list):
                for model in data:
                    #if model.get("status") == 1:
                    modellist.append(model)
        except Exception as e:
            print(f"Failed to get model list")        
        return modellist
    
    def send_web_notify_message(self, message):

        url = f"{self.base_url}/api/xxx"
        payload = {'message': message}
        files = [('file', ('filepath', open(message.get('url'), 'rb'), 'image/jpeg'))]
        headers = {}
        response = requests.post(url, headers=headers, data=payload, files=files)
        print(response.text)

    def send_web_notify_message_v2(self, message,isfile = False):
        url = f"{self.base_url}/api/alarms"
        # headers = {'Authorization': 'Bearer ' + key}  # Line's Token is usually sent in the form of Authorization in Headers
        headers = {}
        files = {'file': open(message['url'], 'rb')} if isfile else None
        try:
            response = requests.post(url, headers=headers, data=message)
            if response.status_code == 200:
                self.logger.debug(f"Message successfully sent: {response.text}")
            else:
                self.logger.debug(f"Error sending message: {response.text}")
        except Exception as e:
            self.logger.debug(f"Exception occurred while sending message: {str(e)}")
        finally:
            if files:
                files['file'].close()  # Make sure the file is closed properly

    def update_web_notify_message(self, message,isfile = False):
        url = f"{self.base_url}/api/alarms/update"
        # headers = {'Authorization': 'Bearer ' + key}  # Line's Token is usually sent in the form of Authorization in Headers
        headers = {}
        files = {'file': open(message['url'], 'rb')} if isfile else None
        try:
            response = requests.put(url, headers=headers, data=message)
            if response.status_code == 200:
                self.logger.debug(f"Message successfully sent: {response.text}")
            else:
                self.logger.debug(f"Error sending message: {response.text}")
        except Exception as e:
            self.logger.debug(f"Exception occurred while sending message: {str(e)}")
        finally:
            if files:
                files['file'].close()  # Make sure the file is closed properly