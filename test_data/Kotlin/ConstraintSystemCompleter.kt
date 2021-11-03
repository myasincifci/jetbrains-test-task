/*
 * Copyright 2010-2020 JetBrains s.r.o. and Kotlin Programming Language contributors.
 * Use of this source code is governed by the Apache 2.0 license that can be found in the license/LICENSE.txt file.
 */

package org.jetbrains.kotlin.fir.resolve.inference

import org.jetbrains.kotlin.fir.FirElement
import org.jetbrains.kotlin.fir.diagnostics.ConeSimpleDiagnostic
import org.jetbrains.kotlin.fir.diagnostics.DiagnosticKind
import org.jetbrains.kotlin.fir.expressions.*
import org.jetbrains.kotlin.fir.resolve.BodyResolveComponents
import org.jetbrains.kotlin.fir.resolve.calls.Candidate
import org.jetbrains.kotlin.fir.resolve.calls.FirNamedReferenceWithCandidate
import org.jetbrains.kotlin.fir.resolve.calls.ResolutionContext
import org.jetbrains.kotlin.fir.resolve.inference.model.ConeFixVariableConstraintPosition
import org.jetbrains.kotlin.fir.returnExpressions
import org.jetbrains.kotlin.fir.types.ConeClassErrorType
import org.jetbrains.kotlin.fir.types.ConeKotlinType
import org.jetbrains.kotlin.fir.types.ConeTypeVariable
import org.jetbrains.kotlin.resolve.calls.inference.components.ConstraintSystemCompletionContext
import org.jetbrains.kotlin.resolve.calls.inference.components.ConstraintSystemCompletionMode
import org.jetbrains.kotlin.resolve.calls.inference.components.TypeVariableDependencyInformationProvider
import org.jetbrains.kotlin.resolve.calls.inference.components.TypeVariableDirectionCalculator
import org.jetbrains.kotlin.resolve.calls.inference.model.NewConstraintSystemImpl
import org.jetbrains.kotlin.resolve.calls.inference.model.NotEnoughInformationForTypeParameter
import org.jetbrains.kotlin.resolve.calls.inference.model.VariableWithConstraints
import org.jetbrains.kotlin.resolve.calls.model.PostponedAtomWithRevisableExpectedType
import org.jetbrains.kotlin.types.model.KotlinTypeMarker
import org.jetbrains.kotlin.types.model.TypeConstructorMarker
import org.jetbrains.kotlin.types.model.TypeVariableMarker
import org.jetbrains.kotlin.utils.addIfNotNull
import org.jetbrains.kotlin.utils.addToStdlib.cast
import org.jetbrains.kotlin.utils.addToStdlib.safeAs

class ConstraintSystemCompleter(private val components: BodyResolveComponents) {
    private val inferenceComponents = components.session.inferenceComponents
    val variableFixationFinder = inferenceComponents.variableFixationFinder
    private val postponedArgumentsInputTypesResolver = inferenceComponents.postponedArgumentInputTypesResolver

    fun complete(
        c: ConstraintSystemCompletionContext,
        completionMode: ConstraintSystemCompletionMode,
        topLevelAtoms: List<FirStatement>,
        candidateReturnType: ConeKotlinType,
        context: ResolutionContext,
        collectVariablesFromContext: Boolean = false,
        analyze: (PostponedResolvedAtom) -> Unit
    ) = with(c) {
        val topLevelTypeVariables = candidateReturnType.extractTypeVariables()

        completion@ while (true) {
            val postponedArguments = getOrderedNotAnalyzedPostponedArguments(topLevelAtoms)

            if (completionMode == ConstraintSystemCompletionMode.UNTIL_FIRST_LAMBDA && hasLambdaToAnalyze(postponedArguments)) return

            // Stage 1
            if (analyzeArgumentWithFixedParameterTypes(postponedArguments, analyze))
                continue

            val someVariableIsReadyForFixation = isAnyVariableReadyForFixation(
                completionMode, topLevelAtoms, candidateReturnType, collectVariablesFromContext, postponedArguments
            )

            if (postponedArguments.isEmpty() && !someVariableIsReadyForFixation)
                break

            val postponedArgumentsWithRevisableType = postponedArguments
                .filterIsInstance<PostponedAtomWithRevisableExpectedType>()
                .filter { it.revisedExpectedType == null }
            val dependencyProvider =
                TypeVariableDependencyInformationProvider(notFixedTypeVariables, postponedArguments, candidateReturnType, this)

            // Stage 2
            val newExpectedTypeWasBuilt = postponedArgumentsInputTypesResolver.collectParameterTypesAndBuildNewExpectedTypes(
                asConstraintSystemCompletionContext(),
                postponedArgumentsWithRevisableType,
                completionMode,
                dependencyProvider,
                topLevelTypeVariables
            )

            if (newExpectedTypeWasBuilt)
                continue

            if (completionMode == ConstraintSystemCompletionMode.FULL) {
                // Stage 3
                for (argument in postponedArguments) {
                    val variableWasFixed = postponedArgumentsInputTypesResolver.fixNextReadyVariableForParameterTypeIfNeeded(
                        asConstraintSystemCompletionContext(),
                        argument,
                        postponedArguments,
                        candidateReturnType,
                        dependencyProvider,
                    ) { // atom provided here is used only inside constraint positions, omitting right now
                        null
                    }

                    if (variableWasFixed)
                        continue@completion
                }

                // Stage 4
                for (argument in postponedArgumentsWithRevisableType) {
                    val argumentWasTransformed =
                        transformToAtomWithNewFunctionalExpectedType(asConstraintSystemCompletionContext(), context, argument)

                    if (argumentWasTransformed)
                        continue@completion
                }
            }

            // Stage 5: analyze the next ready postponed argument
            if (analyzeNextReadyPostponedArgument(postponedArguments, completionMode, analyze))
                continue

            // Stage 6: fix type variables – fix if possible or report not enough information (if completion mode is full)
            val variableWasFixed = fixVariablesOrReportNotEnoughInformation(
                completionMode, topLevelAtoms, candidateReturnType, collectVariablesFromContext, postponedArguments
            )
            if (variableWasFixed)
                continue

            // Stage 7: force analysis of remaining not analyzed postponed arguments and rerun stages if there are
            if (completionMode == ConstraintSystemCompletionMode.FULL) {
                if (analyzeRemainingNotAnalyzedPostponedArgument(postponedArguments, analyze))
                    continue
            }

            break
        }
    }

    private fun ConstraintSystemCompletionContext.analyzeArgumentWithFixedParameterTypes(
        postponedArguments: List<PostponedResolvedAtom>,
        analyze: (PostponedResolvedAtom) -> Unit
    ): Boolean {
        val argumentWithFixedOrPostponedInputTypes = findPostponedArgumentWithFixedOrPostponedInputTypes(postponedArguments)

        if (argumentWithFixedOrPostponedInputTypes != null) {
            analyze(argumentWithFixedOrPostponedInputTypes)
            return true
        }

        return false
    }

    private fun ConstraintSystemCompletionContext.findPostponedArgumentWithFixedOrPostponedInputTypes(postponedArguments: List<PostponedResolvedAtom>) =
        postponedArguments.firstOrNull { argument -> argument.inputTypes.all { containsOnlyFixedOrPostponedVariables(it) } }

    private fun ConstraintSystemCompletionContext.isAnyVariableReadyForFixation(
        completionMode: ConstraintSystemCompletionMode,
        topLevelAtoms: List<FirStatement>,
        topLevelType: ConeKotlinType,
        collectVariablesFromContext: Boolean,
        postponedArguments: List<PostponedResolvedAtom>,
    ): Boolean {
        return variableFixationFinder.findFirstVariableForFixation(
            this,
            getOrderedAllTypeVariables(this, topLevelAtoms, collectVariablesFromContext),
            postponedArguments,
            completionMode,
            topLevelType
        ) != null
    }

    private fun transformToAtomWithNewFunctionalExpectedType(
        c: ConstraintSystemCompletionContext,
        resolutionContext: ResolutionContext,
        argument: PostponedAtomWithRevisableExpectedType,
    ): Boolean = with(c) {
        val revisedExpectedType: ConeKotlinType =
            argument.revisedExpectedType?.takeIf { it.isFunctionOrKFunctionWithAnySuspendability() }?.cast() ?: return false

        when (argument) {
            is ResolvedCallableReferenceAtom ->
                argument.reviseExpectedType(revisedExpectedType)
            is LambdaWithTypeVariableAsExpectedTypeAtom ->
                argument.transformToResolvedLambda(c.getBuilder(), resolutionContext, revisedExpectedType, null /*TODO()*/)
            else -> throw IllegalStateException("Unsupported postponed argument type of $argument")
        }

        return true
    }

    private fun ConstraintSystemCompletionContext.analyzeNextReadyPostponedArgument(
        postponedArguments: List<PostponedResolvedAtom>,
        completionMode: ConstraintSystemCompletionMode,
        analyze: (PostponedResolvedAtom) -> Unit,
    ): Boolean {
        if (completionMode == ConstraintSystemCompletionMode.FULL) {
            val argumentWithTypeVariableAsExpectedType = findPostponedArgumentWithRevisableExpectedType(postponedArguments)

            if (argumentWithTypeVariableAsExpectedType != null) {
                analyze(argumentWithTypeVariableAsExpectedType)
                return true
            }
        }

        return analyzeArgumentWithFixedParameterTypes(postponedArguments, analyze)
    }

    // Avoiding smart cast from filterIsInstanceOrNull looks dirty
    private fun findPostponedArgumentWithRevisableExpectedType(postponedArguments: List<PostponedResolvedAtom>): PostponedResolvedAtom? =
        postponedArguments.firstOrNull { argument -> argument is PostponedAtomWithRevisableExpectedType }

    private fun ConstraintSystemCompletionContext.fixVariablesOrReportNotEnoughInformation(
        completionMode: ConstraintSystemCompletionMode,
        topLevelAtoms: List<FirStatement>,
        topLevelType: ConeKotlinType,
        collectVariablesFromContext: Boolean,
        postponedArguments: List<PostponedResolvedAtom>,
    ): Boolean {
        while (true) {
            val variableForFixation = variableFixationFinder.findFirstVariableForFixation(
                this,
                getOrderedAllTypeVariables(asConstraintSystemCompletionContext(), topLevelAtoms, collectVariablesFromContext),
                postponedArguments,
                completionMode,
                topLevelType
            ) ?: break

            if (!variableForFixation.hasProperConstraint && completionMode == ConstraintSystemCompletionMode.PARTIAL)
                break

            val variableWithConstraints = notFixedTypeVariables.getValue(variableForFixation.variable)

            if (variableForFixation.hasProperConstraint) {
                fixVariable(asConstraintSystemCompletionContext(), topLevelType, variableWithConstraints, postponedArguments)
                return true
            } else {
                processVariableWhenNotEnoughInformation(this, variableWithConstraints, topLevelAtoms)
            }
        }

        return false
    }

    private fun processVariableWhenNotEnoughInformation(
        c: ConstraintSystemCompletionContext,
        variableWithConstraints: VariableWithConstraints,
        topLevelAtoms: List<FirStatement>,
    ) {
        val typeVariable = variableWithConstraints.typeVariable
        val resolvedAtom =
            findResolvedAtomBy(typeVariable, topLevelAtoms) ?: topLevelAtoms.firstOrNull()

        if (resolvedAtom != null) {
            c.addError(
                NotEnoughInformationForTypeParameter(typeVariable, resolvedAtom, c.couldBeResolvedWithUnrestrictedBuilderInference())
            )
        }

        val resultErrorType = when (typeVariable) {
            is ConeTypeParameterBasedTypeVariable ->
                createCannotInferErrorType(
                    "Cannot infer argument for type parameter ${typeVariable.typeParameterSymbol.name}",
                    isUninferredParameter = true,
                )
            is ConeTypeVariableForLambdaParameterType -> createCannotInferErrorType("Cannot infer lambda parameter type")
            else -> createCannotInferErrorType("Cannot infer type variable $typeVariable")
        }

        c.fixVariable(typeVariable, resultErrorType, ConeFixVariableConstraintPosition(typeVariable))
    }

    private fun createCannotInferErrorType(message: String, isUninferredParameter: Boolean = false) =
        ConeClassErrorType(
            ConeSimpleDiagnostic(
                message,
                DiagnosticKind.CannotInferParameterType,
            ),
            isUninferredParameter,
        )

    private fun findResolvedAtomBy(
        typeVariable: TypeVariableMarker,
        topLevelAtoms: List<FirStatement>
    ): FirStatement? {

        fun FirStatement.findFirstAtomContainingVariable(): FirStatement? {

            var result: FirStatement? = null

            fun suggestElement(element: FirElement) {
                if (result == null && element is FirStatement) {
                    result = element
                }
            }

            this@findFirstAtomContainingVariable.processAllContainingCallCandidates(processBlocks = true) { candidate ->
                if (typeVariable in candidate.freshVariables) {
                    suggestElement(candidate.callInfo.callSite)
                }

                for (postponedAtom in candidate.postponedAtoms) {
                    if (postponedAtom is ResolvedLambdaAtom) {
                        if (postponedAtom.typeVariableForLambdaReturnType == typeVariable) {
                            suggestElement(postponedAtom.atom)
                        }
                    }
                }
            }

            return result
        }

        return topLevelAtoms.firstNotNullOfOrNull(FirStatement::findFirstAtomContainingVariable)
    }

    private fun analyzeRemainingNotAnalyzedPostponedArgument(
        postponedArguments: List<PostponedResolvedAtom>,
        analyze: (PostponedResolvedAtom) -> Unit
    ): Boolean {
        val remainingNotAnalyzedPostponedArgument = postponedArguments.firstOrNull { !it.analyzed }

        if (remainingNotAnalyzedPostponedArgument != null) {
            analyze(remainingNotAnalyzedPostponedArgument)
            return true
        }

        return false
    }

    private fun ConstraintSystemCompletionContext.hasLambdaToAnalyze(
        postponedArguments: List<PostponedResolvedAtom>
    ): Boolean {
        return analyzeArgumentWithFixedParameterTypes(postponedArguments) {}
    }

    private fun getOrderedAllTypeVariables(
        c: ConstraintSystemCompletionContext,
        topLevelAtoms: List<FirStatement>,
        collectVariablesFromContext: Boolean
    ): List<TypeConstructorMarker> = with(c) {
        if (collectVariablesFromContext) {
            return c.notFixedTypeVariables.keys.toList()
        }
        val result = LinkedHashSet<TypeConstructorMarker>(c.notFixedTypeVariables.size)
        fun ConeTypeVariable?.toTypeConstructor(): TypeConstructorMarker? =
            this?.typeConstructor?.takeIf { it in c.notFixedTypeVariables.keys }

        // TODO: non-top-level variables?
        fun PostponedAtomWithRevisableExpectedType.collectNotFixedVariables() {
            revisedExpectedType?.lowerBoundIfFlexible()?.asArgumentList()?.let { typeArgumentList ->
                for (typeArgument in typeArgumentList) {
                    val constructor = typeArgument.getType().typeConstructor()
                    if (constructor in notFixedTypeVariables) {
                        result.add(constructor)
                    }
                }
            }
        }

        fun FirStatement.collectAllTypeVariables() {
            this.processAllContainingCallCandidates(processBlocks = true) { candidate ->
                candidate.freshVariables.mapNotNullTo(result) { typeVariable ->
                    typeVariable.toTypeConstructor()
                }

                for (postponedAtom in candidate.postponedAtoms) {
                    when {
                        postponedAtom is ResolvedLambdaAtom -> {
                            result.addIfNotNull(postponedAtom.typeVariableForLambdaReturnType.toTypeConstructor())
                        }
                        postponedAtom is LambdaWithTypeVariableAsExpectedTypeAtom -> {
                            postponedAtom.collectNotFixedVariables()
                        }
                        postponedAtom is ResolvedCallableReferenceAtom -> {
                            if (postponedAtom.mightNeedAdditionalResolution) {
                                postponedAtom.collectNotFixedVariables()
                            }
                        }
                    }
                }
            }
        }

        for (topLevel in topLevelAtoms) {
            topLevel.collectAllTypeVariables()
        }

        require(result.size == c.notFixedTypeVariables.size) {
            val notFoundTypeVariables = c.notFixedTypeVariables.keys.toMutableSet().apply { removeAll(result) }
            "Not all type variables found: $notFoundTypeVariables"
        }

        return result.toList()
    }

    private fun fixVariable(
        c: ConstraintSystemCompletionContext,
        topLevelType: KotlinTypeMarker,
        variableWithConstraints: VariableWithConstraints,
        postponedResolveKtPrimitives: List<PostponedResolvedAtom>
    ) {
        val direction = TypeVariableDirectionCalculator(c, postponedResolveKtPrimitives, topLevelType).getDirection(variableWithConstraints)
        val resultType = inferenceComponents.resultTypeResolver.findResultType(c, variableWithConstraints, direction)
        val variable = variableWithConstraints.typeVariable
        c.fixVariable(variable, resultType, ConeFixVariableConstraintPosition(variable)) // TODO: obtain atom for diagnostics
    }

    private fun getOrderedNotAnalyzedPostponedArguments(topLevelAtoms: List<FirStatement>): List<PostponedResolvedAtom> {
        val notAnalyzedArguments = arrayListOf<PostponedResolvedAtom>()
        for (primitive in topLevelAtoms) {
            primitive.processAllContainingCallCandidates(
                // TODO: remove this argument and relevant parameter
                // Currently, it's used because otherwise problem happens with a lambda in a try-block (see tryWithLambdaInside test)
                processBlocks = true
            ) { candidate ->
                candidate.postponedAtoms.forEach {
                    notAnalyzedArguments.addIfNotNull(it.safeAs<PostponedResolvedAtom>()?.takeUnless { it.analyzed })
                }
            }
        }

        return notAnalyzedArguments
    }
}

fun FirStatement.processAllContainingCallCandidates(processBlocks: Boolean, processor: (Candidate) -> Unit) {
    when (this) {
        is FirFunctionCall -> {
            processCandidateIfApplicable(processor, processBlocks)
            this.arguments.forEach { it.processAllContainingCallCandidates(processBlocks, processor) }
        }

        is FirSafeCallExpression -> {
            this.regularQualifiedAccess.processAllContainingCallCandidates(processBlocks, processor)
        }

        is FirWhenExpression -> {
            processCandidateIfApplicable(processor, processBlocks)
            this.branches.forEach { it.result.processAllContainingCallCandidates(processBlocks, processor) }
        }

        is FirTryExpression -> {
            processCandidateIfApplicable(processor, processBlocks)
            tryBlock.processAllContainingCallCandidates(processBlocks, processor)
            catches.forEach { it.block.processAllContainingCallCandidates(processBlocks, processor) }
        }

        is FirCheckNotNullCall -> {
            processCandidateIfApplicable(processor, processBlocks)
            this.arguments.forEach { it.processAllContainingCallCandidates(processBlocks, processor) }
        }

        is FirQualifiedAccessExpression -> {
            processCandidateIfApplicable(processor, processBlocks)
        }

        is FirVariableAssignment -> {
            processCandidateIfApplicable(processor, processBlocks)
            rValue.processAllContainingCallCandidates(processBlocks, processor)
        }

        is FirWrappedArgumentExpression -> this.expression.processAllContainingCallCandidates(processBlocks, processor)
        is FirBlock -> {
            if (processBlocks) {
                this.returnExpressions().forEach { it.processAllContainingCallCandidates(processBlocks, processor) }
            }
        }

        is FirDelegatedConstructorCall -> {
            processCandidateIfApplicable(processor, processBlocks)
            this.arguments.forEach { it.processAllContainingCallCandidates(processBlocks, processor) }
        }

        is FirElvisExpression -> {
            processCandidateIfApplicable(processor, processBlocks)
            lhs.processAllContainingCallCandidates(processBlocks, processor)
            rhs.processAllContainingCallCandidates(processBlocks, processor)
        }

        is FirAnnotationCall -> {
            processCandidateIfApplicable(processor, processBlocks)
            arguments.forEach { it.processAllContainingCallCandidates(processBlocks, processor) }
        }
    }
}

private fun FirResolvable.processCandidateIfApplicable(
    processor: (Candidate) -> Unit,
    processBlocks: Boolean
) {
    val candidate = (calleeReference as? FirNamedReferenceWithCandidate)?.candidate ?: return
    processor(candidate)

    for (atom in candidate.postponedAtoms) {
        if (atom !is ResolvedLambdaAtom || !atom.analyzed) continue

        atom.returnStatements.forEach {
            it.processAllContainingCallCandidates(processBlocks, processor)
        }
    }
}

val Candidate.csBuilder: NewConstraintSystemImpl get() = system.getBuilder()
