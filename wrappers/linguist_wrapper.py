import subprocess

def detect_linguist(paths: list):
    # pths = " ".join(paths)
    # s = "ruby ./wrappers/linguist_wrapper.rb {}".format(pths)
    # return os.system(s)

    cmd = ["ruby", "./wrappers/linguist_wrapper.rb"] + paths
    lines = subprocess.run(cmd, stdout=subprocess.PIPE).stdout.splitlines()
    return lines