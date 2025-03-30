import redis
r = redis.Redis(host='redis', port=6379, db=0)
# Assume that the list of camera URLs has been prepared in advance
# camera_urls = [
# "https://cctv-ss03.thb.gov.tw:443/T144-001K+150E",
# "https://cctv-ss03.thb.gov.tw:443/T144-003K+200E",
# "https://cctv-ss03.thb.gov.tw:443/T144-003K+250W",
# "https://cctv-ss03.thb.gov.tw:443/T144-003K+250W",
# ]

count = 0
# Clear old camera list
for worker_id in range(1, 10):
    worker_key = f'worker_{worker_id}_urls'
    r.delete(worker_key)
    count += 1

# # Assign new camera to container
# for url in camera_urls:
#     worker_id = count%3 + 1  # 工作器 ID
#     worker_key = f'worker_{worker_id}_urls'
#     r.sadd(worker_key, f'{count}|{url}')
#     count += 1

# Publish update events to all workers
for worker_id in range(1, 10):
    worker_key = f'worker_{worker_id}_urls'
    r.publish(f'{worker_key}_update', 'updated')