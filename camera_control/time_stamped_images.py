import os
from datetime import datetime, timedelta

class TimeStampedImages:
    def __init__(self, folder_path):
        self.folder_path = folder_path
    
    def find_images_in_range(self, start_timestamp, end_timestamp):
        # Convert the incoming time into a datetime object
        start = datetime.fromtimestamp(start_timestamp)
        end = datetime.fromtimestamp(end_timestamp)
        
        matched_files = []
        
        # Iterate through all subfolders in a folder
        for item in os.listdir(self.folder_path):
            try:
                for foldername in os.listdir(self.folder_path+"/"+item):
                    # Convert the subfolder name, assuming the format is 'YYYYMMDD_HH'
                    folder_datetime = datetime.strptime(item+"_"+foldername, "%Y%m%d_%H")
                    # Check whether the folder time is within the specified range
                    if start  - timedelta(hours=1) <= folder_datetime <= end + timedelta(hours=1):  # Add one hour to include the entire hour in the range
                        # Iterate through all files in this subfolder
                        full_folder_path = os.path.join(self.folder_path, item,foldername)
                        for filename in os.listdir(full_folder_path):
                            if filename.endswith('.jpg'):
                                if start <= datetime.strptime(filename, "%Y%m%d%H%M%S.jpg") <= end:
                                    file_path = os.path.join(full_folder_path, filename)
                                    matched_files.append(file_path)
            except ValueError:
                # Ignored if subfolder name is not in datetime format
                continue
        
        return matched_files

# # # Usage examples
# folder_path = 'camera_100/image/7'
# start_timestamp = float("1714055407.8254943") - 15
# end_timestamp = float("1714055407.8254943") + 15

# tsi = TimeStampedImages(folder_path)
# image_paths = tsi.find_images_in_range(start_timestamp, end_timestamp)

# starttime = '2024-04-25 15:09:10'
# endtime = "2024-04-25 15:10:00"
# start_timestamp = datetime.strptime(starttime, "%Y-%m-%d %H:%M:%S").timestamp()
# end_timestamp = datetime.strptime(endtime, "%Y-%m-%d %H:%M:%S").timestamp()
# filelist = tsi.find_images_in_range(start_timestamp, end_timestamp)
# len(filelist)