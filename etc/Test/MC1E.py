import socket
import struct
import time
def create_1e_message(subheader, command, subcommand, start_address, device_code, data_length, data=None):
    """
    Create a binary 1E protocol message.
    Args:
        command (int): Command code (e.g., 0x0401 for read).
        subcommand (int): Subcommand code (e.g., 0x0000 for word devices).
        start_address (int): Starting address (e.g., 100 for D100).
        device_code (int): Device type code (e.g., 0xA8 for D).
        data_length (int): Number of words or bits to read/write.
        data (list): Data to write (optional, only for write commands).
    Returns:
        bytes: The complete binary message.
    """
    # Fixed fields for 1E protocol
    pc_no = 0xFF
    monitoring_timer = 0x0A00

    # Convert command and subcommand to bytes
    command_bytes = struct.pack("<H", command)
    subcommand_bytes = struct.pack("<H", subcommand)

    # Convert start address, device code, and data length
    start_address_bytes = struct.pack("<H", start_address)
    device_code_byte = struct.pack("B", device_code)
    data_length_bytes = struct.pack("<H", data_length)

    # Build the message
    message = struct.pack(
        "<B B H", subheader, pc_no, monitoring_timer
    ) + command_bytes + subcommand_bytes + start_address_bytes + device_code_byte + data_length_bytes

    # Append data for write commands
    if data:
        data_bytes = b"".join([struct.pack("<H", value) for value in data])
        message += data_bytes

    return message

def send_1e_command(ip, port, message):
    """
    Send a 1E binary protocol command and receive the response.
    Args:
        ip (str): PLC IP address.
        port (int): PLC port number.
        message (bytes): The binary command to send.
    Returns:
        bytes: The raw binary response from the PLC.
    """
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.connect((ip, port))
        s.sendall(message)  # Send the command
        response = s.recv(1024)  # Receive the response
        print("Raw Response (hex):", response.hex())
        return response

def read_word(ip, port):
    """
    Read data from the PLC using the 1E Binary Protocol. ip, port, device_code, start_address, count

    """
    # Create the read command
    command = bytearray([
    0x01,           # Subheader
    0xFF,                 # PC Number
    0x0A, 0x00,           # Monitoring Timer
    # 0x0A, 0x00,  0x00, 0x00, 0x20, 0x44,     # Start Device (10 = 0x0A, 0x44 for D)
    0x64, 0x00,  0x00, 0x00, 0x20, 0x44,     # Start Device (100 = 0x64, 0x44 for D)
    0x01, 0x00            # Device Count (2 devices)
])
    print(f'Message:{command}')
    # Send the command and parse the response
    response = send_1e_command(ip, port, command)
    if response[0] != 0x81:  # Check for valid response header
        raise ValueError("Invalid response header:", response.hex())

    # Extract data from the response
    # byte_data = bytes.fromhex('0f27')
    byte_data = bytes.fromhex(response.hex()[4:])# Skip header bytes and Complete code 0x81 0x00

    values = int.from_bytes(byte_data, byteorder='little')  
    return values
def read_bit(ip, port):
    """
    Read data from the PLC using the 1E Binary Protocol.

    """
    # Create the read command
    command = bytearray([
    0x00,           # Subheader
    0xFF,                 # PC Number
    0x0A, 0x00,           # Monitoring Timer
    0x03, 0x00,  0x00, 0x00, 0x20, 0x4D,     # Start Device (1 = 0x01, 0x4D for M)
    # 0x64, 0x00,  0x00, 0x00, 0x20, 0x4D,     # Start Device (100 = 0x64, 0x4D for M)
    0x02, 0x00            # Device Count (2 devices)
])
    print(f'Message:{command}')
    # Send the command and parse the response
    response = send_1e_command(ip, port, command)
    print(f'Message2:{response[0]}')
    if response[0] != 0x80:  # Check for valid response header
        raise ValueError("Invalid response header:", response.hex())

    # Extract data from the response
    values = response  # Skip header bytes and Complete code 0x80 0x00
    return values
def write_bit(ip, port):
    """
    writw data from the PLC using the 1E Binary Protocol.

    """
    # Create the writw command
    command = bytearray([
    0x02,           # Subheader
    0xFF,                 # PC Number
    0x0A, 0x00,           # Monitoring Timer
    0x03, 0x00,  0x00, 0x00, 0x20, 0x4D,     # Start Device (1 = 0x01, 0x4D for M)
    # 0x32, 0x00,  0x00, 0x00, 0x20, 0x4D,     # Start Device (M50 = 0x32, 0x4D for M)
    0x01, 0x00,            # Device Count (1 devices)\
    # 0x0C, 0x00,            # Device Count (1 devices)
    0x10 #, 0x11, 0x01, 0x00, 0x00, 0x01           # values
])
    print(f'Message:{command}')
    # Send the command and parse the response
    response = send_1e_command(ip, port, command)
    # if response[0] != 0x82:  # Check for valid response header
    #     raise ValueError("Invalid response header:", response.hex())
    return response
def write_word(ip, port):
    """
    writw data from the PLC using the 1E Binary Protocol.

    """
    # Create the writw command
    command = bytearray([
    0x03,           # Subheader
    0xFF,                 # PC Number
    0x0A, 0x00,           # Monitoring Timer
    0x64, 0x00,  0x00, 0x00, 0x20, 0x44,     # Start Device (100 = 0x64, 0x4D for d)
    # 0x32, 0x00,  0x00, 0x00, 0x20, 0x4D,     # Start Device (M50 = 0x32, 0x4D for M)
    0x03, 0x00,            # Device Count (1 devices)\
    # 0x0C, 0x00,            # Device Count (1 devices)
    0x34,0X12,0X76,0X98,0X09,0X01    # values
])
    print(f'Message:{command}')
    # Send the command and parse the response
    response = send_1e_command(ip, port, command)
    # if response[0] != 0x82:  # Check for valid response header
    #     raise ValueError("Invalid response header:", response.hex())
    return response

# Example usage
if __name__ == "__main__":
    plc_ip = "192.168.8.250"  # Replace with your PLC's IP address
    plc_port = 5001           # Typically 5000 for FX3U Ethernet

    # Read 10 registers starting from D100
    print("Reading Registers...")
    try:
        register_values = read_word(plc_ip, plc_port) #read_bit(plc_ip, plc_port)
        # little_endian_hex = bytearray.fromstring(register_values[4:])[::-1]
        print("Register Values:", register_values)
        
    except ValueError as e:
        print("Error:", e)
    # time.sleep(3)
    # # Write values 1234 and 5678 to D100 and D101
    # print("Writing Registers...")
    # try:
    #     write_response = write_bit(plc_ip, plc_port)
    #     print("Write Response (hex):", write_response.hex())
    # except ValueError as e:
    #     print("Error:", e)