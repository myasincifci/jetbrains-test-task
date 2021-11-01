package main

import (
	"fmt"
	"os"

	"github.com/go-enry/go-enry/v2"
	"github.com/go-enry/go-enry/v2/data"
)

func main() {

	// c_text := `int *twoSum(int *nums, int numsSize, int target, int *returnSize)
	//   {
	//   int i, j;
	//   int *ret = calloc(2, sizeof(int));
	//   for (i = 0; i < numsSize; i++)
	//   {
	//       int key = target - nums[i];
	//       for (j = i + 1; j < numsSize; j++)
	//           if (nums[j] == key)
	//           {
	//               ret[0] = i;
	//               ret[1] = j;
	//           }
	//   }
	//   *returnSize = 2;
	//   return ret;
	//   }`

	// scala_text := `package Mathematics

	//   object AbsMin {

	//     /** Method returns Absolute minimum Element from the list
	//       *
	//       * @param listOfElements
	//       * @return
	//       */
	//     def abs: Int => Int = Abs.abs

	//     def absMin(elements: List[Int]): Int = abs(elements.minBy(x => abs(x)))

	//   }`

	arg_0 := os.Args[1]

	// Convert map to slice of keys.
	// TODO: make static
	keys := []string{}
	for key, _ := range data.LanguagesLogProbabilities {
		keys = append(keys, key)
	}

	lang := (enry.GetLanguagesByClassifier("", []byte(arg_0), keys))[0]

	fmt.Println(lang)
}
