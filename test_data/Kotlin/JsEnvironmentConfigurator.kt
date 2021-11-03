/*
 * Copyright 2010-2020 JetBrains s.r.o. and Kotlin Programming Language contributors.
 * Use of this source code is governed by the Apache 2.0 license that can be found in the license/LICENSE.txt file.
 */

package org.jetbrains.kotlin.test.services.configuration

import com.intellij.openapi.project.Project
import org.jetbrains.kotlin.config.CommonConfigurationKeys
import org.jetbrains.kotlin.config.CompilerConfiguration
import org.jetbrains.kotlin.js.config.*
import org.jetbrains.kotlin.resolve.CompilerEnvironment
import org.jetbrains.kotlin.serialization.js.JsModuleDescriptor
import org.jetbrains.kotlin.serialization.js.KotlinJavascriptSerializationUtil
import org.jetbrains.kotlin.serialization.js.ModuleKind
import org.jetbrains.kotlin.test.TargetBackend
import org.jetbrains.kotlin.test.directives.JsEnvironmentConfigurationDirectives
import org.jetbrains.kotlin.test.directives.JsEnvironmentConfigurationDirectives.MODULE_KIND
import org.jetbrains.kotlin.test.directives.JsEnvironmentConfigurationDirectives.NO_INLINE
import org.jetbrains.kotlin.test.directives.JsEnvironmentConfigurationDirectives.ERROR_POLICY
import org.jetbrains.kotlin.test.directives.JsEnvironmentConfigurationDirectives.SOURCE_MAP_EMBED_SOURCES
import org.jetbrains.kotlin.test.directives.JsEnvironmentConfigurationDirectives.EXPECT_ACTUAL_LINKER
import org.jetbrains.kotlin.test.directives.JsEnvironmentConfigurationDirectives.TYPED_ARRAYS
import org.jetbrains.kotlin.test.directives.model.DirectivesContainer
import org.jetbrains.kotlin.test.model.DependencyDescription
import org.jetbrains.kotlin.test.model.TestModule
import org.jetbrains.kotlin.test.services.*
import org.jetbrains.kotlin.test.util.joinToArrayString
import org.jetbrains.kotlin.util.capitalizeDecapitalize.decapitalizeAsciiOnly
import org.jetbrains.kotlin.utils.KotlinJavascriptMetadataUtils
import java.io.File

class JsEnvironmentConfigurator(testServices: TestServices) : EnvironmentConfigurator(testServices) {
    override val directiveContainers: List<DirectivesContainer>
        get() = listOf(JsEnvironmentConfigurationDirectives)

    companion object {
        const val TEST_DATA_DIR_PATH = "js/js.translator/testData"
        const val OLD_MODULE_SUFFIX = "_old"

        private const val OUTPUT_DIR_NAME = "outputDir"
        private const val DCE_OUTPUT_DIR_NAME = "dceOutputDir"
        private const val PIR_OUTPUT_DIR_NAME = "pirOutputDir"
        private const val MINIFICATION_OUTPUT_DIR_NAME = "minOutputDir"

        object ExceptionThrowingReporter : JsConfig.Reporter() {
            override fun error(message: String) {
                throw AssertionError("Error message reported: $message")
            }
        }

        private val METADATA_CACHE = (JsConfig.JS_STDLIB + JsConfig.JS_KOTLIN_TEST).flatMap { path ->
            KotlinJavascriptMetadataUtils.loadMetadata(path).map { metadata ->
                val parts = KotlinJavascriptSerializationUtil.readModuleAsProto(metadata.body, metadata.version)
                JsModuleDescriptor(metadata.moduleName, parts.kind, parts.importedModules, parts)
            }
        }

        fun getJsArtifactSimpleName(testServices: TestServices, moduleName: String): String {
            val testName = testServices.testInfo.methodName.removePrefix("test").decapitalizeAsciiOnly()
            val outputFileSuffix = if (moduleName == ModuleStructureExtractor.DEFAULT_MODULE_NAME) "" else "-$moduleName"
            return testName + outputFileSuffix
        }

        fun getJsModuleArtifactPath(testServices: TestServices, moduleName: String): String {
            return getJsArtifactsOutputDir(testServices).absolutePath + "/" + getJsArtifactSimpleName(testServices, moduleName) + "_v5"
        }

        fun getJsArtifactsOutputDir(testServices: TestServices): File {
            return testServices.temporaryDirectoryManager.getOrCreateTempDirectory(OUTPUT_DIR_NAME)
        }

        fun getDceJsArtifactsOutputDir(testServices: TestServices): File {
            return testServices.temporaryDirectoryManager.getOrCreateTempDirectory(DCE_OUTPUT_DIR_NAME)
        }

        fun getPirJsArtifactsOutputDir(testServices: TestServices): File {
            return testServices.temporaryDirectoryManager.getOrCreateTempDirectory(PIR_OUTPUT_DIR_NAME)
        }

        fun getMinificationJsArtifactsOutputDir(testServices: TestServices): File {
            return testServices.temporaryDirectoryManager.getOrCreateTempDirectory(MINIFICATION_OUTPUT_DIR_NAME)
        }


        private fun getPrefixPostfixFile(module: TestModule, prefix: Boolean): File? {
            val suffix = if (prefix) ".prefix" else ".postfix"
            val originalFile = module.files.first().originalFile
            return originalFile.parentFile.resolve(originalFile.name + suffix).takeIf { it.exists() }
        }

        fun getPrefixFile(module: TestModule): File? = getPrefixPostfixFile(module, prefix = true)

        fun getPostfixFile(module: TestModule): File? = getPrefixPostfixFile(module, prefix = false)

        fun createJsConfig(project: Project, configuration: CompilerConfiguration): JsConfig {
            return JsConfig(
                project, configuration, CompilerEnvironment, METADATA_CACHE, (JsConfig.JS_STDLIB + JsConfig.JS_KOTLIN_TEST).toSet()
            )
        }
    }


    private fun TestModule.allTransitiveDependencies(): Set<DependencyDescription> {
        val modules = testServices.moduleStructure.modules
        return regularDependencies.toSet() +
                regularDependencies.flatMap { modules.single { module -> module.name == it.moduleName }.allTransitiveDependencies() }
    }

    override fun configureCompilerConfiguration(configuration: CompilerConfiguration, module: TestModule) {
        val registeredDirectives = module.directives
        val moduleKinds = registeredDirectives[MODULE_KIND]
        val moduleKind = when (moduleKinds.size) {
            0 -> testServices.moduleStructure.allDirectives[MODULE_KIND].singleOrNull() ?: ModuleKind.PLAIN
            1 -> moduleKinds.single()
            else -> error("Too many module kinds passed ${moduleKinds.joinToArrayString()}")
        }
        configuration.put(JSConfigurationKeys.MODULE_KIND, moduleKind)

        val noInline = registeredDirectives.contains(NO_INLINE)
        configuration.put(CommonConfigurationKeys.DISABLE_INLINE, noInline)

        val dependencies = module.regularDependencies.map { getJsModuleArtifactPath(testServices, it.moduleName) + ".meta.js" }
        val allDependencies = module.allTransitiveDependencies().map { getJsModuleArtifactPath(testServices, it.moduleName) + ".meta.js" }
        val friends = module.friendDependencies.map { getJsModuleArtifactPath(testServices, it.moduleName) + ".meta.js" }

        val libraries = when (module.targetBackend) {
            null -> JsConfig.JS_STDLIB + JsConfig.JS_KOTLIN_TEST
            TargetBackend.JS_IR_ES6 -> dependencies
            TargetBackend.JS_IR -> dependencies
            TargetBackend.JS -> JsConfig.JS_STDLIB + JsConfig.JS_KOTLIN_TEST + dependencies
            else -> error("Unsupported target backend: ${module.targetBackend}")
        }
        configuration.put(JSConfigurationKeys.LIBRARIES, libraries)
        configuration.put(JSConfigurationKeys.TRANSITIVE_LIBRARIES, allDependencies)
        configuration.put(JSConfigurationKeys.FRIEND_PATHS, friends)

        configuration.put(CommonConfigurationKeys.MODULE_NAME, module.name.removeSuffix(OLD_MODULE_SUFFIX))
        configuration.put(JSConfigurationKeys.TARGET, EcmaVersion.v5)

        val errorIgnorancePolicy = registeredDirectives[ERROR_POLICY].singleOrNull() ?: ErrorTolerancePolicy.DEFAULT
        configuration.put(JSConfigurationKeys.ERROR_TOLERANCE_POLICY, errorIgnorancePolicy)
        if (errorIgnorancePolicy.allowErrors) {
            configuration.put(JSConfigurationKeys.DEVELOPER_MODE, true)
        }

        val multiModule = testServices.moduleStructure.modules.size > 1
        configuration.put(JSConfigurationKeys.META_INFO, multiModule)

        val sourceDirs = module.files.map { it.originalFile.parent }.distinct()
        configuration.put(JSConfigurationKeys.SOURCE_MAP_SOURCE_ROOTS, sourceDirs)
        configuration.put(JSConfigurationKeys.SOURCE_MAP, true)

        val sourceMapSourceEmbedding = registeredDirectives[SOURCE_MAP_EMBED_SOURCES].singleOrNull() ?: SourceMapSourceEmbedding.NEVER
        configuration.put(JSConfigurationKeys.SOURCE_MAP_EMBED_SOURCES, sourceMapSourceEmbedding)

        configuration.put(JSConfigurationKeys.TYPED_ARRAYS_ENABLED, TYPED_ARRAYS in registeredDirectives)

        configuration.put(JSConfigurationKeys.GENERATE_REGION_COMMENTS, true)

        configuration.put(
            JSConfigurationKeys.FILE_PATHS_PREFIX_MAP,
            mapOf(
                // tmpDir.absolutePath to "<TMP>", // TODO check do we need this on js ir tests
                File(".").absolutePath.removeSuffix(".") to ""
            )
        )

        configuration.put(CommonConfigurationKeys.EXPECT_ACTUAL_LINKER, EXPECT_ACTUAL_LINKER in registeredDirectives)
    }
}

