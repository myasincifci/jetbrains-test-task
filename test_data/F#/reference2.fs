// #Regression #NoMT #Import #NoMono 
// Regression test for FSHARP1.0:1168
// Make sure we can reference both .dll and .exe
// from both .exe and .dll
// using both fsc and fsi
// Note: the code here consumes stuff imported from reference1.exe or reference1.dll
// It has nothing to do with the test itself. Any F# code would pretty much do.

module M

type y() = class
              inherit M.x()
           end

let v = new y()

exit 0
#if INTERACTIVE
;;
#endif
