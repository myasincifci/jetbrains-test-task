/// <reference path="fourslash.ts" />

// @jsx: react
// @module: esnext
// @esModuleInterop: true
// @moduleResolution: node

// @Filename: /node_modules/react/index.d.ts
////export = React;
////export as namespace React;
////declare namespace React {
////    class Component {}
////}

// @Filename: /node_modules/react-native/index.d.ts
////import * as React from "react";
////export class Text extends React.Component {};

// @Filename: /a.tsx
////import React from "react";
////<[|Text|]></Text>;

goTo.file("/a.tsx");
verify.codeFix({
    index: 0,
    description: [ts.Diagnostics.Import_0_from_module_1.message, "Text", "react-native"],
    newFileContent:
`import React from "react";
import { Text } from "react-native";
<Text></Text>;`
});
