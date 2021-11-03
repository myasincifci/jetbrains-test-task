/*
 * Copyright 2010-2021 JetBrains s.r.o. and Kotlin Programming Language contributors.
 * Use of this source code is governed by the Apache 2.0 license that can be found in the license/LICENSE.txt file.
 */

package org.jetbrains.kotlin.fir.extensions

import org.jetbrains.kotlin.fir.FirSession
import org.jetbrains.kotlin.fir.caches.FirCache
import org.jetbrains.kotlin.fir.caches.FirCachesFactory
import org.jetbrains.kotlin.fir.caches.firCachesFactory
import org.jetbrains.kotlin.fir.caches.getValue
import org.jetbrains.kotlin.fir.declarations.validate
import org.jetbrains.kotlin.fir.render
import org.jetbrains.kotlin.fir.resolve.providers.FirSymbolProvider
import org.jetbrains.kotlin.fir.resolve.providers.FirSymbolProviderInternals
import org.jetbrains.kotlin.fir.symbols.impl.*
import org.jetbrains.kotlin.name.CallableId
import org.jetbrains.kotlin.name.ClassId
import org.jetbrains.kotlin.name.FqName
import org.jetbrains.kotlin.name.Name

class FirExtensionDeclarationsSymbolProvider private constructor(
    session: FirSession,
    cachesFactory: FirCachesFactory,
    private val extensions: List<FirDeclarationGenerationExtension>
) : FirSymbolProvider(session) {
    companion object {
        fun create(session: FirSession): FirExtensionDeclarationsSymbolProvider? {
            val extensions = session.extensionService.declarationGenerators
            if (extensions.isEmpty()) return null
            return FirExtensionDeclarationsSymbolProvider(session, session.firCachesFactory, extensions)
        }
    }

    // ------------------------------------------ caches ------------------------------------------

    private val classCache: FirCache<ClassId, FirClassLikeSymbol<*>?, Nothing?> = cachesFactory.createCache { classId, _ ->
        generateClassLikeDeclaration(classId)
    }

    private val functionCache: FirCache<CallableId, List<FirNamedFunctionSymbol>, Nothing?> = cachesFactory.createCache { callableId, _ ->
        generateTopLevelFunctions(callableId)
    }

    private val propertyCache: FirCache<CallableId, List<FirPropertySymbol>, Nothing?> = cachesFactory.createCache { callableId, _ ->
        generateTopLevelProperties(callableId)
    }

    private val packageCache: FirCache<FqName, Boolean, Nothing?> = cachesFactory.createCache { packageFqName, _ ->
        hasPackage(packageFqName)
    }

    // ------------------------------------------ generators ------------------------------------------

    private fun generateClassLikeDeclaration(classId: ClassId): FirClassLikeSymbol<*>? {
        val generatedClasses = extensions.mapNotNull { it.generateClassLikeDeclaration(classId) }.onEach { it.fir.validate() }
        return when (generatedClasses.size) {
            0 -> null
            1 -> generatedClasses.first()
            else -> error("Multiple plugins generated classes with same classId $classId\n${generatedClasses.joinToString("\n") { it.fir.render() }}")
        }
    }

    private fun generateTopLevelFunctions(callableId: CallableId): List<FirNamedFunctionSymbol> {
        return extensions.flatMap { it.generateFunctions(callableId, owner = null) }.onEach { it.fir.validate() }
    }

    private fun generateTopLevelProperties(callableId: CallableId): List<FirPropertySymbol> {
        return extensions.flatMap { it.generateProperties(callableId, owner = null) }.onEach { it.fir.validate() }
    }

    private fun hasPackage(packageFqName: FqName): Boolean {
        return extensions.any { it.hasPackage(packageFqName) }
    }

    // ------------------------------------------ provider methods ------------------------------------------

    override fun getClassLikeSymbolByClassId(classId: ClassId): FirClassLikeSymbol<*>? {
        return classCache.getValue(classId)
    }

    @FirSymbolProviderInternals
    override fun getTopLevelCallableSymbolsTo(destination: MutableList<FirCallableSymbol<*>>, packageFqName: FqName, name: Name) {
        val callableId = CallableId(packageFqName, name)
        destination += functionCache.getValue(callableId)
        destination += propertyCache.getValue(callableId)
    }

    @FirSymbolProviderInternals
    override fun getTopLevelFunctionSymbolsTo(destination: MutableList<FirNamedFunctionSymbol>, packageFqName: FqName, name: Name) {
        destination += functionCache.getValue(CallableId(packageFqName, name))
    }

    @FirSymbolProviderInternals
    override fun getTopLevelPropertySymbolsTo(destination: MutableList<FirPropertySymbol>, packageFqName: FqName, name: Name) {
        destination += propertyCache.getValue(CallableId(packageFqName, name))
    }

    override fun getPackage(fqName: FqName): FqName? {
        return fqName.takeIf { packageCache.getValue(fqName, null) }
    }
}
