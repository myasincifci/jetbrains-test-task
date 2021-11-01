import os

def detect_enry(paths: list):
    pths = " ".join(paths)
    s = "go run ./wrappers/enry_wrapper.go {}".format(pths)
    return os.system(s)