import os

def detect_linguist(s):
    s = "ruby ./wrappers/linguist_wrapper.rb \'{}\'".format(s)
    return os.system(s)