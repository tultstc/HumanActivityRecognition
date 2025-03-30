from snap7.logo import Logo
import time
import logging

Logo_7 = False

class PlcService:
    def __init__(self, plc_ip):
        self.plc_ip = plc_ip
        self.logger = logging.getLogger("module_logger")
        self.logger.setLevel(logging.WARNING)
    def write_alarm_on(self,addresson):
        try:
            plc = Logo()            
            plc.connect(self.plc_ip, 0x0300, 0x0200)
            if plc.get_connected():
                self.logger.debug("PLC is connected")
                # value_s = 0b10
                # plc.write("V1104.0", value_s)
                value_s = 0b1
                plc.write(addresson, value_s)
                self.logger.info(f"Read value must be 1 - check: {str(plc.read(addresson))}")
                plc.disconnect()
                self.logger.debug("PLC is disconnected")
            else:
                self.logger.error("Conncetion failed")
            plc.destroy()
        except Exception as e:
            self.logger.error(f"Exception occurred while sending message: {str(e)}")       

    def write_alarm_off(self,addressoff):
        try:
            plc = Logo()            
            plc.connect(self.plc_ip, 0x0300, 0x0200)
            if plc.get_connected():
                self.logger.debug("PLC is connected")
                value_s = 0b1
                plc.write(addressoff, value_s)
                self.logger.debug(f"Read value must be 1 - check: {str(plc.read(addressoff))}")
                plc.disconnect()
                self.logger.debug("PLC is disconnected")
            else:
                self.logger.error("Conncetion failed")
            plc.destroy()
        except Exception as e:
            self.logger.error(f"Exception occurred while sending message: {str(e)}")   
        
