/*
 * Copyright 2010-2021 JetBrains s.r.o. and Kotlin Programming Language contributors.
 * Use of this source code is governed by the Apache 2.0 license that can be found in the license/LICENSE.txt file.
 */

package org.jetbrains.kotlin.ir.backend.js.ic

import org.jetbrains.kotlin.backend.common.overrides.DefaultFakeOverrideClassFilter
import org.jetbrains.kotlin.backend.common.serialization.*
import org.jetbrains.kotlin.backend.common.serialization.encodings.BinarySymbolData
import org.jetbrains.kotlin.ir.backend.js.JsMappingState
import org.jetbrains.kotlin.ir.backend.js.JsStatementOrigins
import org.jetbrains.kotlin.ir.backend.js.lower.serialization.ir.JsIrLinker
import org.jetbrains.kotlin.ir.declarations.IrDeclaration
import org.jetbrains.kotlin.ir.declarations.IrFile
import org.jetbrains.kotlin.ir.expressions.IrStatementOriginImpl
import org.jetbrains.kotlin.ir.serialization.CarrierDeserializer
import org.jetbrains.kotlin.ir.symbols.IrSymbol
import org.jetbrains.kotlin.ir.util.IdSignature
import org.jetbrains.kotlin.library.SerializedIrFile
import org.jetbrains.kotlin.library.impl.DeclarationId
import org.jetbrains.kotlin.library.impl.DeclarationIrTableMemoryReader
import org.jetbrains.kotlin.library.impl.IrArrayMemoryReader
import org.jetbrains.kotlin.library.impl.IrLongArrayMemoryReader
import org.jetbrains.kotlin.protobuf.ExtensionRegistryLite
import org.jetbrains.kotlin.backend.common.serialization.proto.IrFile as ProtoIrFile

class IcFileDeserializer(
    val linker: JsIrLinker,
    private val file: IrFile,
    originalFileReader: IrLibraryFileFromBytes,
    fileProto: ProtoIrFile,
    deserializeBodies: Boolean,
    allowErrorNodes: Boolean,
    deserializeInlineFunctions: Boolean,
    val moduleDeserializer: IrModuleDeserializer,
    val originalEnqueue: IdSignature.(IcFileDeserializer) -> Unit,
    val icFileData: SerializedIcDataForFile,
    val mappingState: JsMappingState,
    val enqueue: IdSignature.(IcFileDeserializer) -> Unit,
) {

    private val deserializedSymbols = mutableMapOf<IdSignature, IrSymbol>()
    val originalSymbolDeserializer =
        IrSymbolDeserializer(
            linker.symbolTable,
            originalFileReader,
            file.symbol,
            fileProto.actualList,
            { idSig ->
                idSig.enqueue(this)
                if (idSig.hasTopLevel) {
                    idSig.topLevelSignature().originalEnqueue(this)
                }
            },
            linker::handleExpectActualMapping,
            enqueueAllDeclarations = true,
            deserializedSymbols = deserializedSymbols,
            deserializePublicSymbol = ::deserializePublicSymbol
        )

    private val originalDeclarationDeserializer = IrDeclarationDeserializer(
        linker.builtIns,
        linker.symbolTable,
        linker.symbolTable.irFactory,
        originalFileReader,
        file,
        allowErrorNodes,
        deserializeInlineFunctions,
        deserializeBodies,
        originalSymbolDeserializer,
        linker.fakeOverrideBuilder.platformSpecificClassFilter,
        linker.fakeOverrideBuilder,
        allowRedeclaration = true,
        compatibilityMode = CompatibilityMode.CURRENT
    )

    private fun deserializePublicSymbol(idSig: IdSignature, kind: BinarySymbolData.SymbolKind): IrSymbol {
        // TODO: reference lowered declarations cross-module
        if (kind == BinarySymbolData.SymbolKind.FILE_SYMBOL) return file.symbol

        val actualModuleDeserializer = moduleDeserializer.findModuleDeserializerForTopLevelId(idSig)
            ?: linker.handleSignatureIdNotFoundInModuleWithDependencies(idSig, moduleDeserializer)

        return actualModuleDeserializer.deserializeIrSymbol(idSig, kind)
    }

    val originalFileDeserializer =
        IrFileDeserializer(file, originalFileReader, fileProto, originalSymbolDeserializer, originalDeclarationDeserializer)

    private val originalSignatureQueue = ArrayDeque<IdSignature>() // Top-level signatures to be deserialized from original KLIB

    // Returns whether this file should be queued for deserialization
    fun enqueueForDeserialization(idSig: IdSignature): Boolean {
        originalSignatureQueue.addLast(idSig)
        return originalSignatureQueue.size == 1
    }

    fun deserializePendingSignatures() {
        while (!originalSignatureQueue.isEmpty()) {
            val signature = originalSignatureQueue.removeFirst()
            deserializeAnyDeclaration(signature)
        }
    }

    // Explicitly exported declarations (e.g. top-level initializers) must be deserialized before all other declarations.
    // Thus we schedule their deserialization in deserializer's constructor.
    val explicitlyExportedToCompiler: Collection<IdSignature> = fileProto.explicitlyExportedToCompilerList.map {
        val symbolData = originalSymbolDeserializer.parseSymbolData(it)
        originalSymbolDeserializer.deserializeIdSignature(symbolData.signatureId)
    }

    fun allOriginalDeclarationSignatures(): Collection<IdSignature> = originalFileDeserializer.reversedSignatureIndex.keys

    // IC data processing starts here

    private val icFileReader = IrLibraryFileFromBytes(FileReaderFromSerializedIrFile(icFileData.file))

    val symbolDeserializer = IrSymbolDeserializer(
        linker.symbolTable,
        icFileReader,
        file.symbol,
        emptyList(),
        { idSig -> idSig.enqueue(this) },
        { _, s -> s },
        enqueueAllDeclarations = true,
        deserializedSymbols = deserializedSymbols,
        ::deserializePublicSymbol
    )

    private val declarationDeserializer = IrDeclarationDeserializer(
        linker.builtIns,
        linker.symbolTable,
        linker.symbolTable.irFactory,
        icFileReader,
        file,
        allowErrorNodes = true,
        deserializeInlineFunctions = true,
        deserializeBodies = true,
        symbolDeserializer,
        DefaultFakeOverrideClassFilter,
        linker.fakeOverrideBuilder,
        skipMutableState = true,
        additionalStatementOriginIndex = additionalStatementOriginIndex,
        allowErrorStatementOrigins = true,
        allowRedeclaration = true,
        compatibilityMode = CompatibilityMode.CURRENT,
        allowErrorLoopIndices = true,
    )

    private val protoFile: ProtoIrFile by lazy {
        ProtoIrFile.parseFrom(icFileData.file.fileData.codedInputStream, ExtensionRegistryLite.newInstance())
    }

    private val carrierDeserializer by lazy {
        CarrierDeserializer(declarationDeserializer, icFileData.carriers)
    }

    val reversedSignatureIndex: Map<IdSignature, Int> =
        protoFile.declarationIdList.associateBy { symbolDeserializer.deserializeIdSignature(it) }

    val visited = HashSet<IdSignature>()

    val mappingsDeserializer by lazy {
        mappingState.mappingsDeserializer(icFileData.mappings, { code ->
            val symbolData = symbolDeserializer.parseSymbolData(code)
            symbolDeserializer.deserializeIdSignature(symbolData.signatureId)
        }) {
            deserializeIrSymbol(it)
        }
    }

    private val containerSigToOrder by lazy {
        mutableMapOf<IdSignature, ByteArray>().also { map ->
            val containerIds = IrLongArrayMemoryReader(icFileData.order.containerSignatures).array
            val declarationIds = IrArrayMemoryReader(icFileData.order.declarationSignatures)

            containerIds.forEachIndexed { index, id ->
                val symbolData = symbolDeserializer.parseSymbolData(id)
                val containerSig = symbolDeserializer.deserializeIdSignature(symbolData.signatureId)

                map[containerSig] = declarationIds.tableItemBytes(index)
            }
        }
    }

    fun loadClassOrder(classSignature: IdSignature): List<IrSymbol>? {
        val bytes = containerSigToOrder[classSignature] ?: return null

        return IrLongArrayMemoryReader(bytes).array.map { deserializeIrSymbol(it) }
    }

    fun deserializeDeclaration(idSig: IdSignature): IrDeclaration? {
        cachedDeclaration(idSig)?.let { return it }

        val idSigIndex = reversedSignatureIndex[idSig] ?: return null
        val declarationProto = icFileReader.declaration(idSigIndex)
        return declarationDeserializer.deserializeDeclaration(declarationProto)
    }

    // Return declaration iff it was already deserialized
    private fun cachedDeclaration(idSig: IdSignature): IrDeclaration? {
        val symbol = deserializedSymbols[idSig] // Same map is used for both symbol deserializers

        if (symbol != null && symbol.isBound) return symbol.owner as? IrDeclaration

        return null
    }

    fun deserializeAnyDeclaration(idSig: IdSignature): IrDeclaration? {
        cachedDeclaration(idSig)?.let { return it }

        // TODO fast path?
        val maybeTopLevel = if (idSig.hasTopLevel) idSig.topLevelSignature() else idSig

        if (maybeTopLevel in originalFileDeserializer.reversedSignatureIndex.keys) {
            originalFileDeserializer.deserializeFileImplicitDataIfFirstUse()
            return originalFileDeserializer.deserializeDeclaration(maybeTopLevel)
        }

        reversedSignatureIndex[maybeTopLevel]?.let { idSigIndex ->
            val declarationProto = icFileReader.declaration(idSigIndex)
            return declarationDeserializer.deserializeDeclaration(declarationProto)
        }

        // TODO: error?
        return null
    }

    private fun deserializeIrSymbol(code: Long): IrSymbol {
        return symbolDeserializer.deserializeIrSymbol(code)
    }

    fun deserializeIrSymbol(idSig: IdSignature, symbolKind: BinarySymbolData.SymbolKind): IrSymbol {
        idSig.enqueue(this)
        return symbolDeserializer.deserializeIrSymbol(idSig, symbolKind)
    }

    fun injectCarriers(declaration: IrDeclaration, signature: IdSignature) {
        carrierDeserializer.injectCarriers(declaration, signature)
    }

    companion object {
        private val additionalStatementOrigins = JsStatementOrigins::class.nestedClasses.toList()
        private val additionalStatementOriginIndex =
            additionalStatementOrigins.mapNotNull { it.objectInstance as? IrStatementOriginImpl }.associateBy { it.debugName }
    }
}

private class FileReaderFromSerializedIrFile(val irFile: SerializedIrFile) : IrLibraryBytesSource() {
    private val declarationReader = DeclarationIrTableMemoryReader(irFile.declarations)
    private val typeReader = IrArrayMemoryReader(irFile.types)
    private val signatureReader = IrArrayMemoryReader(irFile.signatures)
    private val stringReader = IrArrayMemoryReader(irFile.strings)
    private val bodyReader = IrArrayMemoryReader(irFile.bodies)

    override fun irDeclaration(index: Int): ByteArray = declarationReader.tableItemBytes(DeclarationId(index))

    override fun type(index: Int): ByteArray = typeReader.tableItemBytes(index)

    override fun signature(index: Int): ByteArray = signatureReader.tableItemBytes(index)

    override fun string(index: Int): ByteArray = stringReader.tableItemBytes(index)

    override fun body(index: Int): ByteArray = bodyReader.tableItemBytes(index)

    override fun debugInfo(index: Int): ByteArray? = null
}