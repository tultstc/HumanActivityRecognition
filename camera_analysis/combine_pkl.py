import os
import pickle
import argparse
from pathlib import Path

def combine_pkl_files(input_folders, output_file="ntu_custom_dataset.pkl"):
    dataset = {
        "split": {
            "xsub_train": [],
            "xsub_val": [],
            "xsub_test": []
        },
        "annotations": []
    }
    
    total_samples = 0
    folder_counts = {}
    
    for folder_type, folder_path in input_folders.items():
        if not os.path.exists(folder_path):
            print(f"Warning: Folder {folder_path} does not exist. Skipping.")
            folder_counts[folder_type] = 0
            continue
            
        folder_counts[folder_type] = 0
        
        for file_name in os.listdir(folder_path):
            if file_name.endswith(".pkl"):
                file_path = os.path.join(folder_path, file_name)
                
                try:
                    with open(file_path, "rb") as f:
                        data = pickle.load(f)

                    split_key = f"xsub_{folder_type}"

                    dataset["split"][split_key].append(data["frame_dir"])
                    dataset["annotations"].append(data)
                    
                    folder_counts[folder_type] += 1
                    total_samples += 1
                except Exception as e:
                    print(f"Error processing {file_path}: {str(e)}")
    
    # Remove empty split keys
    for key in list(dataset["split"].keys()):
        if len(dataset["split"][key]) == 0:
            del dataset["split"][key]
    
    output_path = Path(output_file)
    os.makedirs(output_path.parent, exist_ok=True)
    
    with open(output_file, "wb") as f:
        pickle.dump(dataset, f)
    
    result = {
        "success": True,
        "message": f"Dataset created successfully with {total_samples} total samples",
        "stats": {
            "total": total_samples,
            **folder_counts
        },
        "output_file": output_file
    }
    
    return result

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Combine PKL files into a single dataset")
    parser.add_argument("--train", type=str, help="Path to train folder")
    parser.add_argument("--val", type=str, help="Path to validation folder")
    parser.add_argument("--test", type=str, help="Path to test folder (optional)")
    parser.add_argument("--output", type=str, default="ntu_custom_dataset.pkl", help="Output file path")
    
    args = parser.parse_args()
    
    input_folders = {}
    if args.train:
        input_folders["train"] = args.train
    if args.val:
        input_folders["val"] = args.val
    if args.test:
        input_folders["test"] = args.test
    
    if not input_folders:
        print("Error: At least one input folder must be specified")
        exit(1)
    
    result = combine_pkl_files(input_folders, args.output)
    
    print(f"✅ {result['message']}:")
    for folder_type, count in result['stats'].items():
        if folder_type != 'total':
            print(f"  - {count} {folder_type} samples")