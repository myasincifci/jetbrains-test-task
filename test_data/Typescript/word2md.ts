// word2md - Word to Markdown conversion tool
//
// word2md converts a Microsoft Word document to Markdown formatted text. The tool uses the
// Word Automation APIs to start an instance of Word and access the contents of the document
// being converted. The tool must be run using the cscript.exe script host and requires Word
// to be installed on the target machine. The name of the document to convert must be specified
// as a command line argument and the resulting Markdown is written to standard output. The
// tool recognizes the specific Word styles used in the TypeScript Language Specification.

namespace Word {
    export interface Collection<T> {
        count: number;
        item(index: number): T;
    }

    export interface Font {
        bold: boolean;
        italic: boolean;
        subscript: boolean;
        superscript: boolean;
    }

    export interface Find {
        font: Font;
        format: boolean;
        replacement: Replacement;
        style: any;
        text: string;
        clearFormatting(): void;
        execute(
            findText: string,
            matchCase: boolean,
            matchWholeWord: boolean,
            matchWildcards: boolean,
            matchSoundsLike: boolean,
            matchAllWordForms: boolean,
            forward: boolean,
            wrap: number,
            format: boolean,
            replaceWith: string,
            replace: number): boolean;
    }

    export interface Replacement {
        font: Font;
        style: any;
        text: string;
        clearFormatting(): void;
    }

    export interface ListFormat {
        listLevelNumber: number;
        listString: string;
    }

    export interface Column {
    }

    export interface Columns extends Collection<Column> {
    }

    export interface Table {
        columns: Columns;
    }

    export interface Tables extends Collection<Table> {
    }

    export interface Range {
        find: Find;
        listFormat: ListFormat;
        tables: Tables;
        text: string;
        textRetrievalMode: {
            includeHiddenText: boolean;
        }
        words: Ranges;
    }

    export interface Ranges extends Collection<Range> {
    }

    export interface Style {
        nameLocal: string;
    }

    export interface Paragraph {
        alignment: number;
        range: Range;
        style: Style;
        next(): Paragraph;
    }

    export interface Paragraphs extends Collection<Paragraph> {
        first: Paragraph;
    }

    export interface Field {
    }

    export interface Fields extends Collection<Field> {
        toggleShowCodes(): void;
    }

    export interface Hyperlink {
        address: string;
        textToDisplay: string;
        range: Range;
    }

    export interface Hyperlinks extends Collection<Hyperlink> {
    }

    export interface Document {
        fields: Fields;
        paragraphs: Paragraphs;
        hyperlinks: Hyperlinks;
        builtInDocumentProperties: Collection<any>;
        close(saveChanges: boolean): void;
        range(): Range;
    }

    export interface Documents extends Collection<Document> {
        open(filename: string): Document;
    }

    export interface Application {
        documents: Documents;
        quit(): void;
    }
}

const sys = (() => {
    const fileStream = new ActiveXObject("ADODB.Stream");
    fileStream.Type = 2 /* text */;
    const binaryStream = new ActiveXObject("ADODB.Stream");
    binaryStream.Type = 1 /* binary */;
    const args: string[] = [];
    for (let i = 0; i < WScript.Arguments.length; i++) {
        args[i] = WScript.Arguments.Item(i);
    }
    return {
        args,
        createObject: (typeName: string) => new ActiveXObject(typeName),
        write(s: string): void {
            WScript.StdOut.Write(s);
        },
        writeFile: (fileName: string, data: string): void => {
            fileStream.Open();
            binaryStream.Open();
            try {
                // Write characters in UTF-8 encoding
                fileStream.Charset = "utf-8";
                fileStream.WriteText(data);
                // We don't want the BOM, skip it by setting the starting location to 3 (size of BOM).
                fileStream.Position = 3;
                fileStream.CopyTo(binaryStream);
                binaryStream.SaveToFile(fileName, 2 /*overwrite*/);
            }
            finally {
                binaryStream.Close();
                fileStream.Close();
            }
        }
    };
})();

interface FindReplaceOptions {
    style?: any;
    font?: {
        bold?: boolean;
        italic?: boolean;
        subscript?: boolean;
    };
}

function convertDocumentToMarkdown(doc: Word.Document): string {

    const columnAlignment: number[] = [];
    let tableColumnCount: number;
    let tableCellIndex: number;
    let lastInTable: boolean;
    let lastStyle: string;
    let result = "";

    function setProperties(target: any, properties: any) {
        for (const name in properties) {
            if (properties.hasOwnProperty(name)) {
                const value = properties[name];
                if (typeof value === "object") {
                    setProperties(target[name], value);
                }
                else {
                    target[name] = value;
                }
            }
        }
    }

    function findReplace(findText: string, findOptions: FindReplaceOptions, replaceText: string, replaceOptions: FindReplaceOptions) {
        const find = doc.range().find;
        find.clearFormatting();
        setProperties(find, findOptions);
        const replace = find.replacement;
        replace.clearFormatting();
        setProperties(replace, replaceOptions);
        find.execute(findText,
            /* matchCase */ false,
            /* matchWholeWord */ false,
            /* matchWildcards */ false,
            /* matchSoundsLike */ false,
            /* matchAllWordForms */ false,
            /* forward */ true,
            0,
            /* format */ true,
            replaceText,
            2
        );
    }

    function fixHyperlinks() {
        const count = doc.hyperlinks.count;
        for (let i = 0; i < count; i++) {
            const hyperlink = doc.hyperlinks.item(i + 1);
            const address = hyperlink.address;
            if (address && address.length > 0) {
                const textToDisplay = hyperlink.textToDisplay;
                hyperlink.textToDisplay = "[" + textToDisplay + "](" + address + ")";
            }
        }
    }

    function write(s: string) {
        result += s;
    }

    function writeTableHeader() {
        for (let i = 0; i < tableColumnCount - 1; i++) {
            switch (columnAlignment[i]) {
                case 1:
                    write("|:---:");
                    break;
                case 2:
                    write("|---:");
                    break;
                default:
                    write("|---");
            }
        }
        write("|\n");
    }

    function trimEndFormattingMarks(text: string) {
        let i = text.length;
        while (i > 0 && text.charCodeAt(i - 1) < 0x20) i--;
        return text.substr(0, i);
    }

    function writeBlockEnd() {
        switch (lastStyle) {
            case "Code":
                write("```\n\n");
                break;
            case "List Paragraph":
            case "Table":
            case "TOC":
                write("\n");
                break;
        }
    }

    function writeParagraph(p: Word.Paragraph) {

        const range = p.range;
        const inTable = range.tables.count > 0;
        const sectionBreak = range.text.indexOf("\x0C") >= 0;

        let level = 1;
        let style = p.style.nameLocal;
        let text = range.text;

        text = trimEndFormattingMarks(text);
        if (text === "/") {
            // An inline image shows up in the text as a "/". When we see a paragraph
            // consisting of nothing but "/", we check to see if the paragraph contains
            // hidden text and, if so, emit that instead. The hidden text is assumed to
            // contain an appropriate markdown image link.
            range.textRetrievalMode.includeHiddenText = true;
            const fullText = range.text;
            range.textRetrievalMode.includeHiddenText = false;
            if (text !== fullText) {
                text = "&emsp;&emsp;" + fullText.substr(1);
            }
        }

        if (inTable) {
            style = "Table";
        }
        else if (style.match(/\s\d$/)) {
            level = +style.substr(style.length - 1);
            style = style.substr(0, style.length - 2);
        }
        if (lastStyle && style !== lastStyle) {
            writeBlockEnd();
        }

        switch (style) {

            case "Heading":
            case "Appendix":
                const section = range.listFormat.listString;
                write("####".substr(0, level) + ' <a name="' + section + '"/>' + section + " " + text + "\n\n");
                break;

            case "Normal":
                if (text.length) {
                    write(text + "\n\n");
                }
                break;

            case "List Paragraph":
                write("        ".substr(0, range.listFormat.listLevelNumber * 2 - 2) + "* " + text + "\n");
                break;

            case "Grammar":
                write("&emsp;&emsp;" + text.replace(/\s\s\s/g, "&emsp;").replace(/\x0B/g, "  \n&emsp;&emsp;&emsp;") + "\n\n");
                break;

            case "Code":
                if (lastStyle !== "Code") {
                    write("```TypeScript\n");
                }
                else {
                    write("\n");
                }
                write(text.replace(/\x0B/g, "  \n") + "\n");
                break;

            case "Table":
                if (!lastInTable) {
                    tableColumnCount = range.tables.item(1).columns.count + 1;
                    tableCellIndex = 0;
                }
                if (tableCellIndex < tableColumnCount) {
                    columnAlignment[tableCellIndex] = p.alignment;
                }
                write("|" + text);
                tableCellIndex++;
                if (tableCellIndex % tableColumnCount === 0) {
                    write("\n");
                    if (tableCellIndex === tableColumnCount) {
                        writeTableHeader();
                    }
                }
                break;

            case "TOC Heading":
                write("## " + text + "\n\n");
                break;

            case "TOC":
                const strings = text.split("\t");
                write("        ".substr(0, level * 2 - 2) + "* [" + strings[0] + " " + strings[1] + "](#" + strings[0] + ")\n");
                break;
        }

        if (sectionBreak) {
            write("<br/>\n\n");
        }
        lastStyle = style;
        lastInTable = inTable;
    }

    function writeDocument() {
        const title = doc.builtInDocumentProperties.item(1) + "";
        if (title.length) {
            write("# " + title + "\n\n");
        }
        for (let p = doc.paragraphs.first; p; p = p.next()) {
            writeParagraph(p);
        }
        writeBlockEnd();
    }

    findReplace("<", {}, "&lt;", {});
    findReplace("&lt;", { style: "Code" }, "<", {});
    findReplace("&lt;", { style: "Code Fragment" }, "<", {});
    findReplace("&lt;", { style: "Terminal" }, "<", {});
    findReplace("", { font: { subscript: true } }, "<sub>^&</sub>", { font: { subscript: false } });
    findReplace("", { style: "Code Fragment" }, "`^&`", { style: -66 /* default font */ });
    findReplace("", { style: "Production" }, "*^&*", { style: -66 /* default font */ });
    findReplace("", { style: "Terminal" }, "`^&`", { style: -66 /* default font */ });
    findReplace("", { font: { bold: true, italic: true } }, "***^&***", { font: { bold: false, italic: false } });
    findReplace("", { font: { italic: true } }, "*^&*", { font: { italic: false } });

    doc.fields.toggleShowCodes();
    findReplace("^19 REF", {}, "[^&](#^&)", {});
    doc.fields.toggleShowCodes();

    fixHyperlinks();

    writeDocument();

    result = result.replace(/\x85/g, "\u2026");
    result = result.replace(/\x96/g, "\u2013");
    result = result.replace(/\x97/g, "\u2014");

    return result;
}

function main(args: string[]) {
    if (args.length !== 2) {
        sys.write("Syntax: word2md <inputfile> <outputfile>\n");
        return;
    }
    const app: Word.Application = sys.createObject("Word.Application");
    const doc = app.documents.open(args[0]);
    sys.writeFile(args[1], convertDocumentToMarkdown(doc));
    doc.close(/* saveChanges */ false);
    app.quit();
}

main(sys.args);
