# SecureDongle X Python 2.4+ Sample
# (c)2008 SecureMetric Technology Sdn Bhd
import sys
from random import *
from ctypes import *
hinst          = CDLL("VideoAnalysis/etc/libsdx.so")  
SDX_Find       = hinst.SDX_Find
SDX_Open       = hinst.SDX_Open
SDX_Close      = hinst.SDX_Close
SDX_Read       = hinst.SDX_Read
SDX_Write      = hinst.SDX_Write
SDX_GetVersion = hinst.SDX_GetVersion
SDX_Transform  = hinst.SDX_Transform
SDX_RSAEncrypt = hinst.SDX_RSAEncrypt
SDX_RSADecrypt = hinst.SDX_RSADecrypt
#
#
HID_MODE = -1
#
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
#
hid    = c_int(0)
uid    = c_int(0)
seed   = c_char*512
arr    = c_char*512
key512 = c_char*512
lengt  = c_int(0)

print ("")
print ("1. Write SecureDongle X")
print ("2. Read SecureDongle X")
print ("3. Transform Data")
print ("4. RSA Encrypt")
print ("5. RSA Decrypt")
print ("6. Write with Map")
print ("7. Read with Map")
print ("0. Exit")
print ("")
sel = 1
while sel != 0:
    sel = input("Please enter selection: ")
    sel = int(sel)
    if sel == 1:
        ret = SDX_Find()
        if ret < 0:
            print ("Error Finding SecureDongle X: 0x%x" %ret)
            sys.exit(0)
        elif ret == 0:
            print ("No SecureDongle X plugged")
            sys.exit(0)
        uid.value = int(input("Please input UID (i.e. 715400947): "))
        ret = SDX_Open(1, uid.value, byref(hid))
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        handle = ret
        block = c_int(0)
        block = input("Please input write block index (0-4): ")
        buf = arr()
        buf = input("Please input data to be written (i.e. SDX - Sample Data): ")
        print ("Write data: %s" %buf)
        ret = SDX_Write(handle, block, buf)
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        print ("Write OK")
        SDX_Close(handle)
        print ("")
    elif sel == 2:
        ret = SDX_Find()
        if ret < 0:
            print ("Error Finding SecureDongle X: 0x%x" %ret)
            sys.exit(0)
        elif ret == 0:
            print ("No SecureDongle X plugged")
            sys.exit(0)
        uid.value = int(input("Please input UID (i.e. 715400947): "))
        ret = SDX_Open(1, uid.value, byref(hid))
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        handle = ret
        block = int(input("Please input Read block index (0-4): "))
        buf = arr()
        ret = SDX_Read(handle, block, buf)
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        print ("Read data: %s" %buf.value)
        print ("Read OK" )
        SDX_Close(handle)
        print ("")
    elif sel == 3:
        ret = SDX_Find()
        if ret < 0:
            print ("Error Finding SecureDongle X: 0x%x" %ret)
            sys.exit(0)
        elif ret == 0:
            print ("No SecureDongle X plugged")
            sys.exit(0)
        uid.value = input("Please input UID (i.e. 715400947): ")
        ret = SDX_Open(1, uid.value, byref(hid))
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        handle = ret
        buf = arr()
        buf = input("Please input data to be transformed (i.e. SDX - Transform Data): ")
        ret = SDX_Transform(handle, len(buf), buf)
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        print ("Transform result: %s" %buf)
 
        SDX_Close(handle)
        print ("")
    elif sel == 4:
        ret = SDX_Find()
        if ret < 0:
            print ("Error Finding SecureDongle X: 0x%x" %ret)
            sys.exit(0)
        elif ret == 0:
            print ("No SecureDongle X plugged")
            sys.exit(0)
        uid.value = input("Please input UID (i.e. 715400947): ")
        ret = SDX_Open(1, uid.value, byref(hid))
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        handle = ret
        block = c_int(0)
        block = input("Please input start index (0-2559): ")
        buf = arr()
        buf = input("Please input data to encrypt (i.e. SDX - Test RSA Encryption): ")
        key512 = arr()
        lengt = c_int(len(buf))
        ret = SDX_RSAEncrypt(handle, block, buf, byref(lengt), key512)
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        print ("Write success, size: %d. Key to decrypt is stored inside key512 variable." %lengt.value)
        print ("You can try reading the contents of the SDX"        )
        SDX_Close(handle)
        print ("")
    elif sel == 5:
        ret = SDX_Find()
        if ret < 0:
            print ("Error Finding SecureDongle X: 0x%x" %ret)
            sys.exit(0)
        elif ret == 0:
            print ("No SecureDongle X plugged")
            sys.exit(0)
        uid.value = input("Please input UID (i.e. 715400947): ")
        ret = SDX_Open(1, uid.value, byref(hid))
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        handle = ret
        block = input("Please input start index (0-2559): ")
        lengt = input("Please enter length of data to decrypt: ")
        buf = arr()
        ret = SDX_RSADecrypt(handle, block, buf, byref(c_int(lengt)), key512)
        print ("Decrypted data: %s" %buf.value)
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0) 
        SDX_Close(handle)
        print ("")
    elif sel == 6:
        ret = SDX_Find()
        if ret < 0:
            print ("Error Finding SecureDongle X: 0x%x" %ret)
            sys.exit(0)
        elif ret == 0:
            print ("No SecureDongle X plugged")
            sys.exit(0)
        uid.value = input("Please input UID (i.e. 715400947): ")
        ret = SDX_Open(1, uid.value, byref(hid))
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        handle = ret
        block = c_int(0)
        block = input("Please input write block index (0-4): ")
        buf = arr()
        buf = input("Please input data to be written (i.e. SDX - Sample Data): ")
        print ("Original data: %s" %buf)
        lengt = c_int(len(buf))
        print ("Length = %d" %lengt.value)
        ##################### ENCRYPTION SAMPLE #######################
        temp = arr()
        if lengt.value > 512:
            print ("Error: Data size max is 512")
            sys.exit(0)
        for i in range(512):
            if (randint(1, 2) == 1):
                temp[i] = chr(randint(65, 90))
            else:
                temp[i] = chr(randint(97, 122))
        for i in range(lengt.value):
            sze = i * (lengt.value-1)
            indx = sze % 512
            temp[indx] = buf[i]
                
        ###############################################################
        ret = SDX_Write(handle, block, temp)
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        print ("Data Written: %s" %temp.value)
        SDX_Close(handle)
        print ("")
    elif sel == 7:
        ret = SDX_Find()
        if ret < 0:
            print ("Error Finding SecureDongle X: 0x%x" %ret)
            sys.exit(0)
        elif ret == 0:
            print ("No SecureDongle X plugged")
            sys.exit(0)
        uid.value = input("Please input UID (i.e. 715400947): ")
        ret = SDX_Open(1, uid.value, byref(hid))
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        handle = ret
        block = input("Please input Read block index (0-4): ")
        datasize = input("Please enter size of the data to be read (1-512): ")
        if (datasize < 1) or (datasize > 512):
            print ("Data size wrong")
            sys.exit(0)
        buf = arr()
        ret = SDX_Read(handle, block, buf)
        if ret < 0:
            print ("Error: 0x%x" %ret)
            sys.exit(0)
        ##################### DECRYPTION SAMPLE #######################
        temp = arr()
        for i in range(datasize):
            sze = i * (datasize-1)
            indx = sze % 512
            temp[i] = buf[indx]
                
        ###############################################################
            
        print ("Read data: %s" %temp.value)
        print ("Read OK" )
        SDX_Close(handle)
        print("")
