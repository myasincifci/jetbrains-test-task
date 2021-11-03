/// <reference path="fourslash.ts" />

// @module: commonjs

// @Filename: a.d.ts
//// declare namespace foo { class Bar {} }
//// declare module 'path1' {
////   import Bar = foo.Bar;
////   export default Bar;
//// }
//// declare module 'path2longer' {
////   import Bar = foo.Bar;
////   export {Bar};
//// }
////

// @Filename: b.ts
//// Ba/**/

verify.completions({
  marker: "",
  exact: [
    completion.globalThisEntry,
    ...completion.globalsVars,
    {
      name: "foo",
      sortText: completion.SortText.GlobalsOrKeywords
    },
    completion.undefinedVarEntry,
    {
      name: "Bar",
      source: "path1",
      hasAction: true,
      sortText: completion.SortText.AutoImportSuggestions
    },
    {
      name: "Bar",
      source: "path2longer",
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
  name: "Bar",
  source: "path2longer",
  description: `Import 'Bar' from module "path2longer"`,
  newFileContent: `import { Bar } from "path2longer";\n\nBa`,
  preferences: {
    includeCompletionsForModuleExports: true
  }
});
