package main

import (
	"fmt"
	"io/ioutil"
	"os"
	"strings"

	// "github.com/go-enry/go-enry/v2"
	"github.com/go-enry/go-enry/v2"
	"github.com/go-enry/go-enry/v2/data"
)

func main() {
	// TODO: make static
	keys := []string{}
	for key, _ := range data.LanguagesLogProbabilities {
		keys = append(keys, key)
	}

	for _, arg := range os.Args[3:] {

		split := strings.Split(arg, "/")
		filename := split[len(split)-1]

		dat, _ := ioutil.ReadFile(arg)

		lang := ""

		if os.Args[1] == "1" {
			lang = enry.GetLanguage(filename, dat)
		} else {
			lang = enry.GetLanguage("dummy", dat)
		}

		if lang == "" {
			lang_2 := enry.GetLanguagesByClassifier("", dat, keys)
			fmt.Println(lang_2[0])
		} else {
			fmt.Println(lang)
		}
	}
}
