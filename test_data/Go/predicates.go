// Copyright 2012 The Go Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

// This file implements commonly used type predicates.

package types

import "go/token"

// hasName reports whether typ has a name. This includes
// predeclared types, defined types, and type parameters.
// hasName may be called with types that are not fully set up.
func hasName(typ Type) bool {
	switch typ.(type) {
	case *Basic, *Named, *TypeParam:
		return true
	}
	return false
}

// isGeneric reports whether a type is a generic, uninstantiated type (generic
// signatures are not included).
func isGeneric(typ Type) bool {
	// A parameterized type is only instantiated if it doesn't have an instantiation already.
	named, _ := typ.(*Named)
	return named != nil && named.obj != nil && named.targs == nil && named.TypeParams() != nil
}

func is(typ Type, what BasicInfo) bool {
	switch t := under(typ).(type) {
	case *Basic:
		return t.info&what != 0
	case *TypeParam:
		return t.underIs(func(typ Type) bool { return is(typ, what) })
	}
	return false
}

func isBoolean(typ Type) bool  { return is(typ, IsBoolean) }
func isInteger(typ Type) bool  { return is(typ, IsInteger) }
func isUnsigned(typ Type) bool { return is(typ, IsUnsigned) }
func isFloat(typ Type) bool    { return is(typ, IsFloat) }
func isComplex(typ Type) bool  { return is(typ, IsComplex) }
func isNumeric(typ Type) bool  { return is(typ, IsNumeric) }
func isString(typ Type) bool   { return is(typ, IsString) }

// Note that if typ is a type parameter, isInteger(typ) || isFloat(typ) does not
// produce the expected result because a type set that contains both an integer
// and a floating-point type is neither (all) integers, nor (all) floats.
// Use isIntegerOrFloat instead.
func isIntegerOrFloat(typ Type) bool { return is(typ, IsInteger|IsFloat) }

// isNumericOrString is the equivalent of isIntegerOrFloat for isNumeric(typ) || isString(typ).
func isNumericOrString(typ Type) bool { return is(typ, IsNumeric|IsString) }

// isTyped reports whether typ is typed; i.e., not an untyped
// constant or boolean. isTyped may be called with types that
// are not fully set up.
func isTyped(typ Type) bool {
	// isTyped is called with types that are not fully
	// set up. Must not call asBasic()!
	t, _ := typ.(*Basic)
	return t == nil || t.info&IsUntyped == 0
}

// isUntyped(typ) is the same as !isTyped(typ).
func isUntyped(typ Type) bool {
	return !isTyped(typ)
}

func isOrdered(typ Type) bool { return is(typ, IsOrdered) }

func isConstType(typ Type) bool {
	// Type parameters are never const types.
	t := asBasic(typ)
	return t != nil && t.info&IsConstType != 0
}

// IsInterface reports whether typ is an interface type.
func IsInterface(typ Type) bool {
	return asInterface(typ) != nil
}

// isTypeParam reports whether typ is a type parameter.
func isTypeParam(typ Type) bool {
	_, ok := under(typ).(*TypeParam)
	return ok
}

// Comparable reports whether values of type T are comparable.
func Comparable(T Type) bool {
	return comparable(T, nil)
}

func comparable(T Type, seen map[Type]bool) bool {
	if seen[T] {
		return true
	}
	if seen == nil {
		seen = make(map[Type]bool)
	}
	seen[T] = true

	switch t := under(T).(type) {
	case *Basic:
		// assume invalid types to be comparable
		// to avoid follow-up errors
		return t.kind != UntypedNil
	case *Pointer, *Interface, *Chan:
		return true
	case *Struct:
		for _, f := range t.fields {
			if !comparable(f.typ, seen) {
				return false
			}
		}
		return true
	case *Array:
		return comparable(t.elem, seen)
	case *TypeParam:
		return t.iface().IsComparable()
	}
	return false
}

// hasNil reports whether a type includes the nil value.
func hasNil(typ Type) bool {
	switch t := under(typ).(type) {
	case *Basic:
		return t.kind == UnsafePointer
	case *Slice, *Pointer, *Signature, *Interface, *Map, *Chan:
		return true
	case *TypeParam:
		return t.underIs(hasNil)
	}
	return false
}

// An ifacePair is a node in a stack of interface type pairs compared for identity.
type ifacePair struct {
	x, y *Interface
	prev *ifacePair
}

func (p *ifacePair) identical(q *ifacePair) bool {
	return p.x == q.x && p.y == q.y || p.x == q.y && p.y == q.x
}

// For changes to this code the corresponding changes should be made to unifier.nify.
func identical(x, y Type, cmpTags bool, p *ifacePair) bool {
	if x == y {
		return true
	}

	switch x := x.(type) {
	case *Basic:
		// Basic types are singletons except for the rune and byte
		// aliases, thus we cannot solely rely on the x == y check
		// above. See also comment in TypeName.IsAlias.
		if y, ok := y.(*Basic); ok {
			return x.kind == y.kind
		}

	case *Array:
		// Two array types are identical if they have identical element types
		// and the same array length.
		if y, ok := y.(*Array); ok {
			// If one or both array lengths are unknown (< 0) due to some error,
			// assume they are the same to avoid spurious follow-on errors.
			return (x.len < 0 || y.len < 0 || x.len == y.len) && identical(x.elem, y.elem, cmpTags, p)
		}

	case *Slice:
		// Two slice types are identical if they have identical element types.
		if y, ok := y.(*Slice); ok {
			return identical(x.elem, y.elem, cmpTags, p)
		}

	case *Struct:
		// Two struct types are identical if they have the same sequence of fields,
		// and if corresponding fields have the same names, and identical types,
		// and identical tags. Two embedded fields are considered to have the same
		// name. Lower-case field names from different packages are always different.
		if y, ok := y.(*Struct); ok {
			if x.NumFields() == y.NumFields() {
				for i, f := range x.fields {
					g := y.fields[i]
					if f.embedded != g.embedded ||
						cmpTags && x.Tag(i) != y.Tag(i) ||
						!f.sameId(g.pkg, g.name) ||
						!identical(f.typ, g.typ, cmpTags, p) {
						return false
					}
				}
				return true
			}
		}

	case *Pointer:
		// Two pointer types are identical if they have identical base types.
		if y, ok := y.(*Pointer); ok {
			return identical(x.base, y.base, cmpTags, p)
		}

	case *Tuple:
		// Two tuples types are identical if they have the same number of elements
		// and corresponding elements have identical types.
		if y, ok := y.(*Tuple); ok {
			if x.Len() == y.Len() {
				if x != nil {
					for i, v := range x.vars {
						w := y.vars[i]
						if !identical(v.typ, w.typ, cmpTags, p) {
							return false
						}
					}
				}
				return true
			}
		}

	case *Signature:
		// Two function types are identical if they have the same number of parameters
		// and result values, corresponding parameter and result types are identical,
		// and either both functions are variadic or neither is. Parameter and result
		// names are not required to match.
		// Generic functions must also have matching type parameter lists, but for the
		// parameter names.
		if y, ok := y.(*Signature); ok {
			return x.variadic == y.variadic &&
				identicalTParams(x.TypeParams().list(), y.TypeParams().list(), cmpTags, p) &&
				identical(x.params, y.params, cmpTags, p) &&
				identical(x.results, y.results, cmpTags, p)
		}

	case *Union:
		if y, _ := y.(*Union); y != nil {
			xset := computeUnionTypeSet(nil, token.NoPos, x)
			yset := computeUnionTypeSet(nil, token.NoPos, y)
			return xset.terms.equal(yset.terms)
		}

	case *Interface:
		// Two interface types are identical if they describe the same type sets.
		// With the existing implementation restriction, this simplifies to:
		//
		// Two interface types are identical if they have the same set of methods with
		// the same names and identical function types, and if any type restrictions
		// are the same. Lower-case method names from different packages are always
		// different. The order of the methods is irrelevant.
		if y, ok := y.(*Interface); ok {
			xset := x.typeSet()
			yset := y.typeSet()
			if !xset.terms.equal(yset.terms) {
				return false
			}
			a := xset.methods
			b := yset.methods
			if len(a) == len(b) {
				// Interface types are the only types where cycles can occur
				// that are not "terminated" via named types; and such cycles
				// can only be created via method parameter types that are
				// anonymous interfaces (directly or indirectly) embedding
				// the current interface. Example:
				//
				//    type T interface {
				//        m() interface{T}
				//    }
				//
				// If two such (differently named) interfaces are compared,
				// endless recursion occurs if the cycle is not detected.
				//
				// If x and y were compared before, they must be equal
				// (if they were not, the recursion would have stopped);
				// search the ifacePair stack for the same pair.
				//
				// This is a quadratic algorithm, but in practice these stacks
				// are extremely short (bounded by the nesting depth of interface
				// type declarations that recur via parameter types, an extremely
				// rare occurrence). An alternative implementation might use a
				// "visited" map, but that is probably less efficient overall.
				q := &ifacePair{x, y, p}
				for p != nil {
					if p.identical(q) {
						return true // same pair was compared before
					}
					p = p.prev
				}
				if debug {
					assertSortedMethods(a)
					assertSortedMethods(b)
				}
				for i, f := range a {
					g := b[i]
					if f.Id() != g.Id() || !identical(f.typ, g.typ, cmpTags, q) {
						return false
					}
				}
				return true
			}
		}

	case *Map:
		// Two map types are identical if they have identical key and value types.
		if y, ok := y.(*Map); ok {
			return identical(x.key, y.key, cmpTags, p) && identical(x.elem, y.elem, cmpTags, p)
		}

	case *Chan:
		// Two channel types are identical if they have identical value types
		// and the same direction.
		if y, ok := y.(*Chan); ok {
			return x.dir == y.dir && identical(x.elem, y.elem, cmpTags, p)
		}

	case *Named:
		// Two named types are identical if their type names originate
		// in the same type declaration.
		if y, ok := y.(*Named); ok {
			xargs := x.TypeArgs().list()
			yargs := y.TypeArgs().list()

			if len(xargs) != len(yargs) {
				return false
			}

			if len(xargs) > 0 {
				// Instances are identical if their original type and type arguments
				// are identical.
				if !Identical(x.orig, y.orig) {
					return false
				}
				for i, xa := range xargs {
					if !Identical(xa, yargs[i]) {
						return false
					}
				}
				return true
			}

			// TODO(gri) Why is x == y not sufficient? And if it is,
			//           we can just return false here because x == y
			//           is caught in the very beginning of this function.
			return x.obj == y.obj
		}

	case *TypeParam:
		// nothing to do (x and y being equal is caught in the very beginning of this function)

	case nil:
		// avoid a crash in case of nil type

	default:
		unreachable()
	}

	return false
}

func identicalTParams(x, y []*TypeParam, cmpTags bool, p *ifacePair) bool {
	if len(x) != len(y) {
		return false
	}
	for i, x := range x {
		y := y[i]
		if !identical(x.bound, y.bound, cmpTags, p) {
			return false
		}
	}
	return true
}

// Default returns the default "typed" type for an "untyped" type;
// it returns the incoming type for all other types. The default type
// for untyped nil is untyped nil.
//
func Default(typ Type) Type {
	if t, ok := typ.(*Basic); ok {
		switch t.kind {
		case UntypedBool:
			return Typ[Bool]
		case UntypedInt:
			return Typ[Int]
		case UntypedRune:
			return universeRune // use 'rune' name
		case UntypedFloat:
			return Typ[Float64]
		case UntypedComplex:
			return Typ[Complex128]
		case UntypedString:
			return Typ[String]
		}
	}
	return typ
}
