import subprocess
from guesslang import Guess
from tqdm import tqdm
from timeit import default_timer as timer 

def detect_guesslang(paths: list):

    start = timer()

    guess = Guess()
    
    langs = []
    for path in tqdm(paths):
        with open(path, 'r') as file:
            data = file.read().replace('\n', '')

            if not data.strip():
                print(path)

            lang = guess.language_name(data)
            langs.append(lang)

    end = timer()
    
    print("Guesslang ran in " + str(round(end - start)) + " seconds.")

    return langs