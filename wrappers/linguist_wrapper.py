import os

def detect_linguist(paths: list):
    pths = " ".join(paths)
    s = "ruby ./wrappers/linguist_wrapper.rb {}".format(pths)
    return os.system(s)