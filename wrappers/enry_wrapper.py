import os

def detect_enry(s):
    s = "go run ./wrappers/enry_wrapper.go \'{}\'".format(s)
    return os.system(s)