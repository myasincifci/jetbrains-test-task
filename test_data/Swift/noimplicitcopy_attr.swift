// RUN: %target-swift-emit-silgen -enable-experimental-move-only -parse-stdlib -disable-availability-checking %s | %FileCheck %s
// RUN: %target-swift-emit-sil -enable-experimental-move-only -parse-stdlib -disable-availability-checking %s | %FileCheck -check-prefix=CHECK-SIL %s
// RUN: %target-swift-emit-sil -O -enable-experimental-move-only -Xllvm -sil-disable-pass=FunctionSignatureOpts -parse-stdlib -disable-availability-checking %s | %FileCheck -check-prefix=CHECK-SIL %s

public class Klass {}

// CHECK: bb0(%0 : @noImplicitCopy @owned $Klass):
// CHECK-SIL: bb0(%0 : @noImplicitCopy $Klass):

public func arguments(@_noImplicitCopy _ x: Klass) -> Klass {
    return x
}
