package main

import (
	"fmt"
	"github.com/go-enry/go-enry/v2"
	"github.com/go-enry/go-enry/v2/data"
	"io/ioutil"
	"os"
)

func main() {

	// Convert map to slice of keys.
	// TODO: make static
	keys := []string{}
	for key, _ := range data.LanguagesLogProbabilities {
		keys = append(keys, key)
	}

	for _, arg := range os.Args[1:] {
		dat, _ := ioutil.ReadFile(arg)
		lang := enry.GetLanguage("", dat)

		if lang == "" {
			lang_2 := enry.GetLanguagesByClassifier("", dat, keys)
			fmt.Println(lang_2[0])
		} else {
			fmt.Println(lang)
		}
	}

	// lang := (enry.GetLanguagesByClassifier("", []byte(arg_0), keys))[0]
	// // lang := enry.GetLanguage("", []byte(arg_0))

	// fmt.Println(lang)
}
