import subprocess
from timeit import default_timer as timer 

def detect_enry(paths: list, no_ext=False):
    start = timer()
    ext = 1

    if no_ext:
        ext = 0

    cmd = ["go", "run", "./wrappers/enry_wrapper.go", str(ext), "./wrappers/enry_wrapper.go"] + paths
    lines = subprocess.run(cmd, stdout=subprocess.PIPE).stdout.splitlines()
    
    end = timer()

    print("Enry ran in " + str(round(end - start)) + " seconds.")
    
    return lines