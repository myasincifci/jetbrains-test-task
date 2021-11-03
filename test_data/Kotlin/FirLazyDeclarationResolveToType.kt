/*
 * Copyright 2010-2021 JetBrains s.r.o. and Kotlin Programming Language contributors.
 * Use of this source code is governed by the Apache 2.0 license that can be found in the license/LICENSE.txt file.
 */

package org.jetbrains.kotlin.analysis.low.level.api.fir.lazy.resolve

import org.jetbrains.kotlin.fir.declarations.*
import org.jetbrains.kotlin.fir.resolve.ScopeSession
import org.jetbrains.kotlin.fir.types.FirResolvedTypeRef
import org.jetbrains.kotlin.analysis.low.level.api.fir.file.builder.ModuleFileCache

enum class ResolveType {
    NoResolve,
    BodyResolveWithChildren,
    CallableBodyResolve,
    CallableReturnType,
    AnnotationType,
    AnnotationsArguments,
    ClassSuperTypes,
    CallableContracts,
    DeclarationStatus,
    ValueParametersTypes,
    TypeParametersTypes,
//    ResolveForMemberScope,
//    ResolveForSuperMembers,
}

internal fun <D : FirDeclaration> FirLazyDeclarationResolver.lazyResolveDeclaration(
    firDeclaration: D,
    moduleFileCache: ModuleFileCache,
    toResolveType: ResolveType,
    scopeSession: ScopeSession,
    checkPCE: Boolean = false,
): D {
    return when (toResolveType) {
        ResolveType.NoResolve -> return firDeclaration
        ResolveType.CallableReturnType -> {
            require(firDeclaration is FirCallableDeclaration) {
                "CallableReturnType type cannot be applied to ${firDeclaration::class.qualifiedName}"
            }
            var currentDeclaration = firDeclaration as FirCallableDeclaration
            if (currentDeclaration.resolvePhase < FirResolvePhase.TYPES) {
                if (currentDeclaration.returnTypeRef !is FirResolvedTypeRef) {
                    currentDeclaration = lazyResolveDeclaration(
                        firDeclarationToResolve = currentDeclaration,
                        moduleFileCache = moduleFileCache,
                        toPhase = FirResolvePhase.TYPES,
                        scopeSession = scopeSession,
                        checkPCE = checkPCE,
                    )
                }
            }
            if (currentDeclaration.returnTypeRef !is FirResolvedTypeRef) {
                currentDeclaration = lazyResolveDeclaration(
                    firDeclarationToResolve = currentDeclaration,
                    moduleFileCache = moduleFileCache,
                    toPhase = FirResolvePhase.IMPLICIT_TYPES_BODY_RESOLVE,
                    scopeSession = scopeSession,
                    checkPCE = checkPCE,
                )
            }

            check(currentDeclaration.returnTypeRef is FirResolvedTypeRef)
            @Suppress("UNCHECKED_CAST")
            currentDeclaration as D
        }
        ResolveType.BodyResolveWithChildren, ResolveType.CallableBodyResolve -> {
            require(firDeclaration is FirCallableDeclaration || toResolveType != ResolveType.CallableBodyResolve) {
                "BodyResolveWithChildren and CallableBodyResolve types cannot be applied to ${firDeclaration::class.qualifiedName}"
            }
            lazyResolveDeclaration(
                firDeclarationToResolve = firDeclaration,
                moduleFileCache = moduleFileCache,
                toPhase = FirResolvePhase.BODY_RESOLVE,
                scopeSession = scopeSession,
                checkPCE = checkPCE,
            )
        }
        ResolveType.AnnotationType, ResolveType.AnnotationsArguments -> {
            if (firDeclaration is FirFile) {
                resolveFileAnnotations(
                    firFile = firDeclaration,
                    annotations = firDeclaration.annotations,
                    moduleFileCache = moduleFileCache,
                    scopeSession = scopeSession,
                    checkPCE = checkPCE
                )
                firDeclaration
            } else {
                val toPhase =
                    if (toResolveType == ResolveType.AnnotationType) FirResolvePhase.TYPES else FirResolvePhase.ARGUMENTS_OF_ANNOTATIONS
                lazyResolveDeclaration(
                    firDeclarationToResolve = firDeclaration,
                    moduleFileCache = moduleFileCache,
                    toPhase = toPhase,
                    scopeSession = scopeSession,
                    checkPCE = checkPCE,
                )
            }
        }
        ResolveType.ClassSuperTypes -> {
            require(firDeclaration is FirClassLikeDeclaration) {
                "ClassSuperTypes type cannot be applied to ${firDeclaration::class.qualifiedName}"
            }
            lazyResolveDeclaration(
                firDeclarationToResolve = firDeclaration,
                moduleFileCache = moduleFileCache,
                toPhase = FirResolvePhase.SUPER_TYPES,
                scopeSession = scopeSession,
                checkPCE = checkPCE,
            )
        }
        ResolveType.CallableContracts -> {
            require(firDeclaration is FirCallableDeclaration) {
                "CallableContracts type cannot be applied to ${firDeclaration::class.qualifiedName}"
            }
            lazyResolveDeclaration(
                firDeclarationToResolve = firDeclaration,
                moduleFileCache = moduleFileCache,
                toPhase = FirResolvePhase.CONTRACTS,
                scopeSession = scopeSession,
                checkPCE = checkPCE,
            )
        }
        ResolveType.DeclarationStatus -> {
            require(firDeclaration !is FirFile) {
                "DeclarationStatus type cannot be applied to ${firDeclaration::class.qualifiedName}"
            }
            lazyResolveDeclaration(
                firDeclarationToResolve = firDeclaration,
                moduleFileCache = moduleFileCache,
                toPhase = FirResolvePhase.STATUS,
                scopeSession = scopeSession,
                checkPCE = checkPCE,
            )
        }
        ResolveType.ValueParametersTypes, ResolveType.TypeParametersTypes -> {
            require(firDeclaration !is FirFile) {
                "ValueParametersTypes and TypeParametersTypes types cannot be applied to ${firDeclaration::class.qualifiedName}"
            }
            lazyResolveDeclaration(
                firDeclarationToResolve = firDeclaration,
                moduleFileCache = moduleFileCache,
                toPhase = FirResolvePhase.TYPES,
                scopeSession = scopeSession,
                checkPCE = checkPCE,
            )
        }
    }
}
