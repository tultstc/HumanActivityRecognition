import sys
import os
from random import *
from ctypes import *
import logging
import logging.config

def configure_logging():

    # Define the configuration
    LOGGING_CONFIG = {
        'version': 1,
        'disable_existing_loggers': False,  # Don't disable existing loggers
        'formatters': {
            'simple': {
                'format': '%(asctime)s - %(name)s - %(levelname)s - %(message)s',
            },
            'detailed': {
                'format': '%(asctime)s - %(name)s - %(levelname)s - %(message)s - %(filename)s:%(lineno)d',
            },
        },
        'handlers': {
            'console': {
                'class': 'logging.StreamHandler',
                'formatter': 'simple',
                'level': 'DEBUG',
            },
            'file_handler': {
                'class': 'logging.FileHandler',
                'formatter': 'detailed',
                'level': 'INFO',
                'filename': 'app.log',
            },
        },
        'loggers': {
            'main_logger': {  # Main logger
                'handlers': ['console'],
                'level': 'INFO',
                'propagate': False,
            },
            'module_logger': {  # Module logger
                'handlers': ['console'],
                'level': 'WARNING',
                'propagate': False,
            },
            'app_logger': {  # Custom logger
                'handlers': ['console', 'file_handler'],
                'level': 'DEBUG',
                'propagate': False,
            },
        },
    }
    # Apply the configuration
    logging.config.dictConfig(LOGGING_CONFIG)

class LicCheck:
    def __init__(self):
        self.logger = logging.getLogger("module_logger")
        self.logger.setLevel(logging.WARNING)
        hinst          = CDLL(os.getcwd() + "/libsdx.so")  
        self.SDX_Find       = hinst.SDX_Find
        self.SDX_Open       = hinst.SDX_Open
        self.SDX_Close      = hinst.SDX_Close
        self.SDX_Read       = hinst.SDX_Read
        self.SDX_Write      = hinst.SDX_Write
        self.SDX_GetVersion = hinst.SDX_GetVersion
        self.SDX_Transform  = hinst.SDX_Transform
        self.SDX_RSAEncrypt = hinst.SDX_RSAEncrypt
        self.SDX_RSADecrypt = hinst.SDX_RSADecrypt
        #
        HID_MODE = -1
        #
        SDXERR_SUCCESS					= 0		# Success
        SDXERR_NO_SUCH_DEVICE				= 0xA0100001	# Specified SDX is not found (parameter error)
        SDXERR_NOT_OPENED_DEVICE			= 0xA0100002	# Need to call SDX_Open first to open the SDX, then call this function (operation error)
        SDXERR_WRONG_UID				= 0xA0100003	# Wrong UID(parameter error)
        SDXERR_WRONG_INDEX				= 0xA0100004	# Block index error (parameter error)
        SDXERR_TOO_LONG_SEED				= 0xA0100005	# Seed character string is longer than 64 bytes when calling GenUID (parameter error)
        SDXERR_WRITE_PROTECT				= 0xA0100006	# Tried to write to write-protected dongle(operation error)
        SDXERR_WRONG_START_INDEX			= 0xA0100007	# Start index wrong (parameter error)
        SDXERR_INVALID_LEN				= 0xA0100008	# Invalid length (parameter error)
        SDXERR_TOO_LONG_ENCRYPTION_DATA			= 0xA0100009	# Chipertext is too long (cryptography error)
        SDXERR_GENERATE_KEY				= 0xA010000A	# Generate key error (cryptography error)
        SDXERR_INVALID_KEY				= 0xA010000B	# Invalid key (cryptography error)
        SDXERR_FAILED_ENCRYPTION			= 0xA010000C	# Failed to encrypt string (cryptography error)
        SDXERR_FAILED_WRITE_KEY				= 0xA010000D	# Failed to write key (cryptography error)
        SDXERR_FAILED_DECRYPTION			= 0xA010000E	# Failed to decrypt string (Cryptography error)	
        SDXERR_OPEN_DEVICE				= 0xA010000F	# Open device error (Windows error)
        SDXERR_READ_REPORT				= 0xA0100010	# Read record error(Windows error)
        SDXERR_WRITE_REPORT				= 0xA0100011	# Write record error(Windows error)
        SDXERR_SETUP_DI_GET_DEVICE_INTERFACE_DETAIL	= 0xA0100012	# Internal error (Windows error)
        SDXERR_GET_ATTRIBUTES				= 0xA0100013	# Internal error (Windows error)
        SDXERR_GET_PREPARSED_DATA			= 0xA0100014	# Internal error (Windows error)
        SDXERR_GETCAPS					= 0xA0100015	# Internal error (Windows error)
        SDXERR_FREE_PREPARSED_DATA			= 0xA0100016	# Internal error (Windows error)
        SDXERR_FLUSH_QUEUE				= 0xA0100017	# Internal error (Windows error)
        SDXERR_SETUP_DI_CLASS_DEVS			= 0xA0100018	# Internal error (Windows error)
        SDXERR_GET_SERIAL				= 0xA0100019	# Internal error (Windows error)
        SDXERR_GET_PRODUCT_STRING			= 0xA010001A	# Internal error (Windows error)
        SDXERR_TOO_LONG_DEVICE_DETAIL			= 0xA010001B	# Internal error
        SDXERR_UNKNOWN_DEVICE				= 0xA0100020	# Unknown device(hardware error)
        SDXERR_VERIFY					= 0xA0100021	# Verification error(hardware error)
        SDXERR_UNKNOWN_ERROR				= 0xA010FFFF	# Unknown error(hardware error)
        #
        self.hid    = c_int(0)
        self.uid    = c_int(-683571668)    #Value for VideoAnalysis
        self.seed   = c_char*512
        self.arr    = c_char*512
        self.key512 = c_char*512
        self.lenght  = c_int(0)

    def check(self,isRead = False):
        result = {
            "status": False,
            "camera": 0,
            "model": 0,
            "desc": ""
        }
        ret = self.SDX_Find()
        if ret < 0:
            self.logger.warning("Error Finding SecureDongle X: 0x%x" %ret)
            return result
        elif ret == 0:
            self.logger.warning("No SecureDongle X plugged")
            return result
        ret = self.SDX_Open(1, self.uid.value, byref(self.hid))
        if ret < 0:
            self.logger.warning("SDX_Open Error: 0x%x" %ret)
            return result
        result["status"] = True
        if(isRead):
            handle = ret
            block = 0   #Read block 0
            buf = self.arr()
            ret = self.SDX_Read(handle, block, buf)
            if ret < 0:
                self.logger.warning("SDX_Read Error: 0x%x" %ret)
                return result
            string_data = buf.value.decode('utf-8').split("|")
            if(len(string_data) == 2):
                result["desc"] = string_data[0]
                camera_model = string_data[1].split(".")
                result["camera"] = int(camera_model[0])
                result["model"] = int(camera_model[1])
            self.SDX_Close(handle)
        else:
            self.SDX_Close(ret)    
        return result