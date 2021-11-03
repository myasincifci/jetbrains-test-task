// #ByRef #Regression #inline
// Regression test for DevDiv:122445 ("Internal compiler error when evaluating code with inline/byref")
//<Expects status="success"></Expects>

module M

// Should compile just fine
let inline f x (y:_ nativeptr) = (^a : (static member TryParse : string * ^a nativeptr -> bool)(x,y))
 
