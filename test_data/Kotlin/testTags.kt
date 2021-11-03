/*
 * Copyright 2010-2021 JetBrains s.r.o. and Kotlin Programming Language contributors.
 * Use of this source code is governed by the Apache 2.0 license that can be found in the license/LICENSE.txt file.
 */

package org.jetbrains.kotlin.gradle.testbase

import org.junit.jupiter.api.Tag

/**
 * Add it to test classes performing simple KGP checks (deprecated).
 */
@Target(AnnotationTarget.CLASS, AnnotationTarget.ANNOTATION_CLASS, AnnotationTarget.FUNCTION)
@Retention(AnnotationRetention.RUNTIME)
@Tag("SimpleKGP")
annotation class SimpleGradlePluginTests

/**
 * Add it to test classes performing Gradle or Kotlin daemon checks.
 */
@Target(AnnotationTarget.CLASS, AnnotationTarget.ANNOTATION_CLASS, AnnotationTarget.FUNCTION)
@Retention(AnnotationRetention.RUNTIME)
@Tag("DaemonsKGP")
annotation class DaemonsGradlePluginTests

/**
 * Add it to tests covering Kotlin Gradle Plugin/JVM platform.
 */
@Target(AnnotationTarget.CLASS, AnnotationTarget.ANNOTATION_CLASS, AnnotationTarget.FUNCTION)
@Retention(AnnotationRetention.RUNTIME)
@Tag("JvmKGP")
annotation class JvmGradlePluginTests

/**
 * Add it to tests covering Kotlin Gradle Plugin/JS platform.
 */
@Target(AnnotationTarget.CLASS, AnnotationTarget.ANNOTATION_CLASS, AnnotationTarget.FUNCTION)
@Retention(AnnotationRetention.RUNTIME)
@Tag("JsKGP")
annotation class JsGradlePluginTests

@Target(AnnotationTarget.CLASS, AnnotationTarget.ANNOTATION_CLASS, AnnotationTarget.FUNCTION)
@Retention(AnnotationRetention.RUNTIME)
@Tag("OtherKGP")
annotation class OtherGradlePluginTests
