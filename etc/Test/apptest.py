import asyncio
import time
tasks = []
async def task1():
    try:
        while True:
            start_time = time.monotonic()   
            await asyncio.sleep(0.01)
            print(f"Task 1 running, taking {time.monotonic() - start_time:.2f} seconds")
    except asyncio.CancelledError:
        print("Task 1 cancelled!")
        raise

async def task2():
    try:
        while True:
            start_time =time.monotonic()    
            await asyncio.sleep(0.02)
            print(f"Task 2 running, taking {time.monotonic() - start_time:.2f} seconds")
    except asyncio.CancelledError:
        print("Task 2 cancelled!")
        raise
async def task3():
    try:
        while True:
            start_time = time.monotonic()  
            await asyncio.sleep(0.03)
            print(f"Task 3 running, taking {time.monotonic() - start_time:.2f} seconds")
    except asyncio.CancelledError:
        print("Task 3 cancelled!")
        raise
async def task4():
    try:
        while True:
            start_time = time.monotonic()   
            await asyncio.sleep(0.04)
            print(f"Task 4 running, taking {time.monotonic() - start_time:.2f} seconds")
    except asyncio.CancelledError:
        print("Task 4 cancelled!")
        raise
async def task5():
    try:
        while True:
            start_time = time.monotonic()   
            await asyncio.sleep(0.05)
            print(f"Task 5 running, taking {time.monotonic() - start_time:.2f} seconds")
    except asyncio.CancelledError:
        print("Task 5 cancelled!")
        raise

async def main():
    t1 = asyncio.create_task(task1())
    tasks.append(t1)
    t2 = asyncio.create_task(task2())
    tasks.append(t2)
    t3 = asyncio.create_task(task3())
    tasks.append(t3)
    t4 = asyncio.create_task(task4())
    tasks.append(t4)
    t5 = asyncio.create_task(task5())
    tasks.append(t5)
    try:
        # Run tasks indefinitely
        await asyncio.gather(*tasks)
    except KeyboardInterrupt:
        print("Gracefully shutting down...")
        t1.cancel()
        t2.cancel()
        t3.cancel()
        t4.cancel()
        t5.cancel()
        await asyncio.gather(*tasks, return_exceptions=True)

# Run the event loop
asyncio.run(main())
