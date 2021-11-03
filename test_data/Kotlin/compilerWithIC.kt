/*
 * Copyright 2010-2021 JetBrains s.r.o. and Kotlin Programming Language contributors.
 * Use of this source code is governed by the Apache 2.0 license that can be found in the license/LICENSE.txt file.
 */

package org.jetbrains.kotlin.ir.backend.js

import org.jetbrains.kotlin.backend.common.phaser.PhaseConfig
import org.jetbrains.kotlin.backend.common.phaser.invokeToplevel
import org.jetbrains.kotlin.config.CompilerConfiguration
import org.jetbrains.kotlin.ir.ObsoleteDescriptorBasedAPI
import org.jetbrains.kotlin.ir.backend.js.ic.ModuleCache
import org.jetbrains.kotlin.ir.backend.js.ic.PersistentCacheConsumer
import org.jetbrains.kotlin.ir.backend.js.lower.generateJsTests
import org.jetbrains.kotlin.ir.backend.js.lower.moveBodilessDeclarationsToSeparatePlace
import org.jetbrains.kotlin.ir.backend.js.lower.serialization.ir.JsIrLinker
import org.jetbrains.kotlin.ir.backend.js.transformers.irToJs.IrModuleToJsTransformer
import org.jetbrains.kotlin.ir.backend.js.transformers.irToJs.IrModuleToJsTransformerTmp
import org.jetbrains.kotlin.ir.backend.js.transformers.irToJs.generateWrappedModuleBody
import org.jetbrains.kotlin.ir.backend.js.utils.serialization.JsIrAstDeserializer
import org.jetbrains.kotlin.ir.declarations.IrFactory
import org.jetbrains.kotlin.ir.declarations.IrModuleFragment
import org.jetbrains.kotlin.ir.descriptors.IrBuiltInsOverDescriptors
import org.jetbrains.kotlin.ir.util.ExternalDependenciesGenerator
import org.jetbrains.kotlin.ir.util.noUnboundLeft
import org.jetbrains.kotlin.js.config.RuntimeDiagnostic
import org.jetbrains.kotlin.name.FqName
import org.jetbrains.kotlin.serialization.js.ModuleKind
import java.io.ByteArrayInputStream

@Suppress("UNUSED_PARAMETER")
@OptIn(ObsoleteDescriptorBasedAPI::class)
fun compileWithIC(
    module: IrModuleFragment,
    configuration: CompilerConfiguration,
    deserializer: JsIrLinker,
    dependencies: Collection<IrModuleFragment>,
    mainArguments: List<String>? = null,
    exportedDeclarations: Set<FqName> = emptySet(),
    generateFullJs: Boolean = true,
    generateDceJs: Boolean = false,
    dceDriven: Boolean = false,
    dceRuntimeDiagnostic: RuntimeDiagnostic? = null,
    es6mode: Boolean = false,
    multiModule: Boolean = false,
    relativeRequirePath: Boolean = false,
    propertyLazyInitialization: Boolean = false,
    verifySignatures: Boolean = true,
    baseClassIntoMetadata: Boolean = false,
    lowerPerModule: Boolean = false,
    safeExternalBoolean: Boolean = false,
    safeExternalBooleanDiagnostic: RuntimeDiagnostic? = null,
    filesToLower: Set<String>?,
    cacheConsumer: PersistentCacheConsumer,
) {

    val mainModule = module
    val allModules = dependencies
    val moduleDescriptor = module.descriptor
    val irBuiltIns = module.irBuiltins
    val symbolTable = (irBuiltIns as IrBuiltInsOverDescriptors).symbolTable

    val context = JsIrBackendContext(
        moduleDescriptor,
        irBuiltIns,
        symbolTable,
        module,
        exportedDeclarations,
        configuration,
        es6mode = es6mode,
        dceRuntimeDiagnostic = dceRuntimeDiagnostic,
        propertyLazyInitialization = propertyLazyInitialization,
        baseClassIntoMetadata = baseClassIntoMetadata,
        safeExternalBoolean = safeExternalBoolean,
        safeExternalBooleanDiagnostic = safeExternalBooleanDiagnostic
    )

    // Load declarations referenced during `context` initialization
    val irProviders = listOf(deserializer)
    ExternalDependenciesGenerator(symbolTable, irProviders).generateUnboundSymbolsAsDependencies()

    deserializer.postProcess()
    symbolTable.noUnboundLeft("Unbound symbols at the end of linker")

    allModules.forEach {
        moveBodilessDeclarationsToSeparatePlace(context, it)
    }

    generateJsTests(context, mainModule)

    jsPhases.invokeToplevel(PhaseConfig(jsPhases), context, allModules)

    val transformer = IrModuleToJsTransformerTmp(
        context,
        mainArguments,
        fullJs = generateFullJs,
        dceJs = generateDceJs,
        multiModule = multiModule,
        relativeRequirePath = relativeRequirePath,
    )

    val dirtyFiles = filesToLower?.let { dirties ->
        module.files.filter { it.fileEntry.name in dirties }
    } ?: module.files

    val ast = transformer.generateBinaryAst(dirtyFiles)

    ast.entries.forEach { (path, bytes) -> cacheConsumer.commitBinaryAst(path, bytes) }
}


@Suppress("UNUSED_PARAMETER")
fun generateJsFromAst(
    mainModule: String,
    caches: Map<String, ModuleCache>
): CompilerResult {
    val deserializer = JsIrAstDeserializer()
    val fragments = caches.values.map { it.asts.values.mapNotNull { it.ast?.let { deserializer.deserialize(ByteArrayInputStream(it))} } }
    return CompilerResult(generateWrappedModuleBody("main", ModuleKind.PLAIN, fragments), null)
}