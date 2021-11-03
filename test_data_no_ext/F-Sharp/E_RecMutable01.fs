// #Regression #Conformance #ObjectOrientedTypes #Classes #LetBindings 

// rec (mutable)
// See FSHARP1.0:2329 
//<Expects id="FS0874" span="(7,37-7,38)" status="error">Mutable 'let' bindings can't be recursive or defined in recursive modules or namespaces</Expects>
type C() = class
             static let rec mutable m = 0       // only record fields and simple 'let' bindings may be marked mutable.
           end

   
