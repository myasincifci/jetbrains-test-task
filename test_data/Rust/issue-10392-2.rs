// run-rustfix

pub struct A { pub foo: isize }

fn a() -> A { panic!() }

fn main() {
    let A { .., } = a(); //~ ERROR: expected `}`
}
