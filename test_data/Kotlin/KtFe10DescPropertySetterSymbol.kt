/*
 * Copyright 2010-2021 JetBrains s.r.o. and Kotlin Programming Language contributors.
 * Use of this source code is governed by the Apache 2.0 license that can be found in the license/LICENSE.txt file.
 */

package org.jetbrains.kotlin.analysis.api.descriptors.symbols.descriptorBased

import org.jetbrains.kotlin.analysis.api.descriptors.KtFe10AnalysisSession
import org.jetbrains.kotlin.analysis.api.descriptors.symbols.descriptorBased.base.*
import org.jetbrains.kotlin.analysis.api.descriptors.symbols.pointers.KtFe10NeverRestoringSymbolPointer
import org.jetbrains.kotlin.analysis.api.symbols.KtPropertySetterSymbol
import org.jetbrains.kotlin.analysis.api.symbols.KtValueParameterSymbol
import org.jetbrains.kotlin.analysis.api.symbols.markers.KtTypeAndAnnotations
import org.jetbrains.kotlin.analysis.api.symbols.pointers.KtPsiBasedSymbolPointer
import org.jetbrains.kotlin.analysis.api.symbols.pointers.KtSymbolPointer
import org.jetbrains.kotlin.analysis.api.types.KtType
import org.jetbrains.kotlin.analysis.api.withValidityAssertion
import org.jetbrains.kotlin.descriptors.PropertySetterDescriptor
import org.jetbrains.kotlin.descriptors.SyntheticPropertyDescriptor
import org.jetbrains.kotlin.descriptors.hasBody
import org.jetbrains.kotlin.name.CallableId
import org.jetbrains.kotlin.resolve.calls.inference.returnTypeOrNothing

internal class KtFe10DescPropertySetterSymbol(
    override val descriptor: PropertySetterDescriptor,
    override val analysisSession: KtFe10AnalysisSession
) : KtPropertySetterSymbol(), KtFe10DescMemberSymbol<PropertySetterDescriptor> {
    override val annotatedType: KtTypeAndAnnotations
        get() = withValidityAssertion { descriptor.returnTypeOrNothing.toKtTypeAndAnnotations(analysisSession) }

    override val isDefault: Boolean
        get() = withValidityAssertion { descriptor.isDefault }

    override val isInline: Boolean
        get() = withValidityAssertion { descriptor.isInline }

    override val isOverride: Boolean
        get() = withValidityAssertion { descriptor.isExplicitOverride }

    override val hasBody: Boolean
        get() = withValidityAssertion { descriptor.hasBody() }

    override val valueParameters: List<KtValueParameterSymbol>
        get() = withValidityAssertion { descriptor.valueParameters.map { KtFe10DescValueParameterSymbol(it, analysisSession) } }

    override val hasStableParameterNames: Boolean
        get() = withValidityAssertion { descriptor.ktHasStableParameterNames }

    override val callableIdIfNonLocal: CallableId?
        get() = withValidityAssertion {
            val propertyDescriptor = descriptor.correspondingProperty
            return if (propertyDescriptor is SyntheticPropertyDescriptor) {
                propertyDescriptor.getMethod.callableId
            } else {
                null
            }
        }

    override val parameter: KtValueParameterSymbol
        get() = withValidityAssertion { KtFe10DescValueParameterSymbol(descriptor.valueParameters.single(), analysisSession) }

    override val receiverType: KtTypeAndAnnotations?
        get() = withValidityAssertion { descriptor.extensionReceiverParameter?.type?.toKtTypeAndAnnotations(analysisSession) }

    override val dispatchType: KtType?
        get() = withValidityAssertion { descriptor.dispatchReceiverParameter?.type?.toKtType(analysisSession) }

    override fun createPointer(): KtSymbolPointer<KtPropertySetterSymbol> {
        withValidityAssertion {
            return KtPsiBasedSymbolPointer.createForSymbolFromSource(this) ?: KtFe10NeverRestoringSymbolPointer()
        }
    }
}