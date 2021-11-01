import os

def detect_enry(s):
    s = "go run ./enry_wrapper/enry_wrapper.go \'{}\'".format(s)
    return os.system(s)