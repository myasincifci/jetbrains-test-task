import os

def detect(s):
    s = "ruby linguist_wrapper.rb \'{}\'".format(s)
    os.system(s)