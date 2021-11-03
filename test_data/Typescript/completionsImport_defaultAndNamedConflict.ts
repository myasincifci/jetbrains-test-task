/// <reference path="fourslash.ts" />

// @noLib: true

// @Filename: /someModule.ts
//// export const someModule = 0;
//// export default 1;

// @Filename: /index.ts
//// someMo/**/

verify.completions({
  marker: "",
  exact: [
    completion.globalThisEntry,
    completion.undefinedVarEntry,
    {
      name: "someModule",
      source: "/someModule",
      sourceDisplay: "./someModule",
      text: "(property) default: 1",
      kind: "property",
      kindModifiers: "export",
      hasAction: true,
      sortText: completion.SortText.AutoImportSuggestions
    },
    {
      name: "someModule",
      source: "/someModule",
      sourceDisplay: "./someModule",
      text: "const someModule: 0",
      kind: "const",
      kindModifiers: "export",
      hasAction: true,
      sortText: completion.SortText.AutoImportSuggestions
    },
    ...completion.statementKeywordsWithTypes
  ],
  preferences: {
    includeCompletionsForModuleExports: true
  }
});

verify.applyCodeActionFromCompletion("", {
  name: "someModule",
  source: "/someModule",
  data: { exportName: "default", fileName: "/someModule.ts" },
  description: `Import default 'someModule' from module "./someModule"`,
  newFileContent: `import someModule from "./someModule";

someMo`
});
