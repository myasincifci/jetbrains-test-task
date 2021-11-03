/*
 * Copyright 2010-2021 JetBrains s.r.o. and Kotlin Programming Language contributors.
 * Use of this source code is governed by the Apache 2.0 license that can be found in the license/LICENSE.txt file.
 */

package org.jetbrains.kotlin.analysis.low.level.api.fir.transformers

import org.jetbrains.kotlin.fir.declarations.FirResolvePhase
import org.jetbrains.kotlin.fir.resolve.ScopeSession
import org.jetbrains.kotlin.fir.resolve.transformers.FirProviderInterceptor
import org.jetbrains.kotlin.fir.resolve.transformers.body.resolve.FirTowerDataContextCollector
import org.jetbrains.kotlin.analysis.low.level.api.fir.api.FirDeclarationDesignationWithFile
import org.jetbrains.kotlin.analysis.low.level.api.fir.file.builder.ModuleFileCache
import org.jetbrains.kotlin.analysis.low.level.api.fir.lazy.resolve.FirLazyDeclarationResolver

internal object LazyTransformerFactory {
    fun createLazyTransformer(
        phase: FirResolvePhase,
        designation: FirDeclarationDesignationWithFile,
        scopeSession: ScopeSession,
        moduleFileCache: ModuleFileCache,
        lazyDeclarationResolver: FirLazyDeclarationResolver,
        towerDataContextCollector: FirTowerDataContextCollector?,
        firProviderInterceptor: FirProviderInterceptor?,
        checkPCE: Boolean,
    ): FirLazyTransformerForIDE = when (phase) {
        FirResolvePhase.SEALED_CLASS_INHERITORS -> FirLazyTransformerForIDE.DUMMY
        FirResolvePhase.SUPER_TYPES -> FirDesignatedSupertypeResolverTransformerForIDE(
            designation = designation,
            session = designation.firFile.moduleData.session,
            scopeSession = scopeSession,
            moduleFileCache = moduleFileCache,
            firLazyDeclarationResolver = lazyDeclarationResolver,
            firProviderInterceptor = firProviderInterceptor,
            checkPCE = checkPCE,
        )
        FirResolvePhase.TYPES -> FirDesignatedTypeResolverTransformerForIDE(
            designation,
            designation.firFile.moduleData.session,
            scopeSession,
        )
        FirResolvePhase.STATUS -> FirDesignatedStatusResolveTransformerForIDE(
            designation,
            designation.firFile.moduleData.session,
            scopeSession,
        )
        FirResolvePhase.ARGUMENTS_OF_ANNOTATIONS -> FirDesignatedAnnotationArgumentsResolveTransformerForIDE(
            designation,
            designation.firFile.moduleData.session,
            scopeSession,
        )
        FirResolvePhase.CONTRACTS -> FirDesignatedContractsResolveTransformerForIDE(
            designation,
            designation.firFile.moduleData.session,
            scopeSession,
        )
        FirResolvePhase.IMPLICIT_TYPES_BODY_RESOLVE -> FirDesignatedImplicitTypesTransformerForIDE(
            designation,
            designation.firFile.moduleData.session,
            scopeSession,
            towerDataContextCollector
        )
        FirResolvePhase.BODY_RESOLVE -> FirDesignatedBodyResolveTransformerForIDE(
            designation,
            designation.firFile.moduleData.session,
            scopeSession,
            towerDataContextCollector,
            firProviderInterceptor,
        )
        else -> error("Non-lazy phase $phase")
    }
}