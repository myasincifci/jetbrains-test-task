// #Regression #Diagnostics 
// Regression test for FSHARP1.0:1273
// Give warning on use of interface and/or override implementations in augmentations
//
//<Expects id="FS0060" span="(13,22-13,25)" status="warning"></Expects>
//<Expects id="FS0001" span="(13,28-13,31)" status="error"></Expects>

type T2 = class
            abstract M : int
            //default a.M = 3
           end
type T2 with
            override x.M = 0.0       // warning + error    
        end
