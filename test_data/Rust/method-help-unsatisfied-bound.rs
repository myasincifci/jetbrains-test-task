struct Foo;

fn main() {
    let a: Result<(), Foo> = Ok(());
    a.unwrap();
    //~^ ERROR the method
}
