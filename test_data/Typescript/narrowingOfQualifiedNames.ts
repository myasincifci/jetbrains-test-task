// @strict: true

// Repro from #43411

interface IProperties {
    foo?: {
        aaa: string
        bbb: string
    }
}

function init(properties: IProperties) {
    if (properties.foo) {
        type FooOK = typeof properties.foo;
        properties.foo; // type is { aaa: string; bbb: string; }
        for (const x of [1, 2, 3]) {
          properties.foo; // type is { aaa: string; bbb: string; }
          type FooOrUndefined = typeof properties.foo; // type should be { aaa: string; bbb: string; }
        }
    }
}

interface DeepOptional {
    a?: {
        b?: {
            c?: string
        }
    }
}

function init2(foo: DeepOptional) {
    if (foo.a) {
        type A = typeof foo.a;
        type B = typeof foo.a.b;
        type C = typeof foo.a.b.c;

        for(const _ of [1]) {
            type A = typeof foo.a;
            type B = typeof foo.a.b;
            type C = typeof foo.a.b.c;

            if (foo.a.b) {
                type A = typeof foo.a;
                type B = typeof foo.a.b;
                type C = typeof foo.a.b.c;

                for(const _ of [1]) {
                    type A = typeof foo.a;
                    type B = typeof foo.a.b;
                    type C = typeof foo.a.b.c;

                    if (foo.a.b.c) {
                        type A = typeof foo.a;
                        type B = typeof foo.a.b;
                        type C = typeof foo.a.b.c;

                        for(const _ of [1]) {
                            type A = typeof foo.a;
                            type B = typeof foo.a.b;
                            type C = typeof foo.a.b.c;
                        }
                    }
                }
            }
        }
    }
}