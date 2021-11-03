/*
 * Copyright 2010-2020 JetBrains s.r.o. and Kotlin Programming Language contributors.
 * Use of this source code is governed by the Apache 2.0 license that can be found in the license/LICENSE.txt file.
 */

package kotlin.io

import kotlin.wasm.internal.*

/** Prints the line separator to the standard output stream. */
public actual fun println() {
    println("")
}

/** Prints the given [message] and the line separator to the standard output stream. */
public actual fun println(message: Any?) {
    printlnImpl(exportString(message.toString()))
}

/** Prints the given [message] to the standard output stream. */
public actual fun print(message: Any?) {
    // TODO: Support print without newline
    println(message)
}

@SinceKotlin("1.6")
public actual fun readln(): String = TODO("Wasm stdlib: IO")

@SinceKotlin("1.6")
public actual fun readlnOrNull(): String? = TODO("Wasm stdlib: IO")


internal actual interface Serializable

@WasmImport("runtime", "println")
private fun printlnImpl(messageAddr: Int): Unit =
    implementedAsIntrinsic
