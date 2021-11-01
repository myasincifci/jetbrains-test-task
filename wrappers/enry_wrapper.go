package main

import (
	"fmt"
	"os"

	"github.com/go-enry/go-enry/v2"
	"github.com/go-enry/go-enry/v2/data"
)

func main() {

	arg_0 := os.Args[1]

	// Convert map to slice of keys.
	// TODO: make static
	keys := []string{}
	for key, _ := range data.LanguagesLogProbabilities {
		keys = append(keys, key)
	}

	lang := (enry.GetLanguagesByClassifier("", []byte(arg_0), keys))[0]
	// lang := enry.GetLanguage("", []byte(arg_0))

	fmt.Println(lang)
}
