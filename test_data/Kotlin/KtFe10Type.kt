/*
 * Copyright 2010-2021 JetBrains s.r.o. and Kotlin Programming Language contributors.
 * Use of this source code is governed by the Apache 2.0 license that can be found in the license/LICENSE.txt file.
 */

package org.jetbrains.kotlin.analysis.api.descriptors.types.base

import org.jetbrains.kotlin.analysis.api.ValidityTokenOwner
import org.jetbrains.kotlin.analysis.api.components.KtTypeRendererOptions
import org.jetbrains.kotlin.analysis.api.descriptors.KtFe10AnalysisSession
import org.jetbrains.kotlin.analysis.api.descriptors.utils.KtFe10TypeRenderer
import org.jetbrains.kotlin.analysis.api.tokens.ValidityToken
import org.jetbrains.kotlin.types.KotlinType
import org.jetbrains.kotlin.types.UnwrappedType

interface KtFe10Type : ValidityTokenOwner {
    val type: UnwrappedType

    val analysisSession: KtFe10AnalysisSession

    override val token: ValidityToken
        get() = analysisSession.token
}

internal fun KotlinType.asStringForDebugging(): String {
    val builder = StringBuilder()
    val renderer = KtFe10TypeRenderer(KtTypeRendererOptions.DEFAULT, isDebugText = true)
    renderer.render(this, builder)
    return builder.toString()
}