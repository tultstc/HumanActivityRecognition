import pickle

with open("ntu_custom_dataset.pkl", "rb") as f:
    dataset = pickle.load(f)

print("Dataset keys:", dataset.keys())
print("Danh sách", dataset["split"])
print("Danh sách video tập train:", dataset["split"]["xsub_train"])
print("Danh sách video tập val:", dataset["split"]["xsub_val"])
print("Số lượng annotations:", len(dataset["annotations"]))

count = 0
for i, ann in enumerate(dataset["annotations"]):
    count += 1
    print(f"\n🔹 Annotation {i+1}:")
    print(f"- frame_dir: {ann['frame_dir']}")
    print(f"- label: {ann['label']}")
    print(f"- total_frames: {ann['total_frames']}")
    print(f"- keypoint shape: {ann['keypoint'].shape}")
    print(f"- keypoint_score shape: {ann['keypoint_score'].shape}")
    if count == 4:
        exit()
        
if len(dataset["annotations"]) > 0:
    print("Cấu trúc annotation đầu tiên:", dataset["annotations"][0].keys())
else:
    print("⚠️ Không có annotations nào!")
