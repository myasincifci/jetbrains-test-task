﻿namespace ShareX
{
    partial class QRCodeForm
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            this.components = new System.ComponentModel.Container();
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(QRCodeForm));
            this.cmsQR = new System.Windows.Forms.ContextMenuStrip(this.components);
            this.tsmiCopy = new System.Windows.Forms.ToolStripMenuItem();
            this.tsmiSaveAs = new System.Windows.Forms.ToolStripMenuItem();
            this.tsmiUpload = new System.Windows.Forms.ToolStripMenuItem();
            this.tss1 = new System.Windows.Forms.ToolStripSeparator();
            this.tsmiDecode = new System.Windows.Forms.ToolStripMenuItem();
            this.txtQRCode = new System.Windows.Forms.TextBox();
            this.pbQRCode = new System.Windows.Forms.PictureBox();
            this.tcMain = new System.Windows.Forms.TabControl();
            this.tpEncode = new System.Windows.Forms.TabPage();
            this.tpDecode = new System.Windows.Forms.TabPage();
            this.btnDecodeFromFile = new System.Windows.Forms.Button();
            this.lblDecodeResult = new System.Windows.Forms.Label();
            this.btnDecodeFromScreen = new System.Windows.Forms.Button();
            this.rtbDecodeResult = new System.Windows.Forms.RichTextBox();
            this.pDecodeResult = new System.Windows.Forms.Panel();
            this.cmsQR.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.pbQRCode)).BeginInit();
            this.tcMain.SuspendLayout();
            this.tpEncode.SuspendLayout();
            this.tpDecode.SuspendLayout();
            this.pDecodeResult.SuspendLayout();
            this.SuspendLayout();
            // 
            // cmsQR
            // 
            this.cmsQR.Items.AddRange(new System.Windows.Forms.ToolStripItem[] {
            this.tsmiCopy,
            this.tsmiSaveAs,
            this.tsmiUpload,
            this.tss1,
            this.tsmiDecode});
            this.cmsQR.Name = "cmsQR";
            this.cmsQR.ShowImageMargin = false;
            resources.ApplyResources(this.cmsQR, "cmsQR");
            // 
            // tsmiCopy
            // 
            this.tsmiCopy.Name = "tsmiCopy";
            resources.ApplyResources(this.tsmiCopy, "tsmiCopy");
            this.tsmiCopy.Click += new System.EventHandler(this.tsmiCopy_Click);
            // 
            // tsmiSaveAs
            // 
            this.tsmiSaveAs.Name = "tsmiSaveAs";
            resources.ApplyResources(this.tsmiSaveAs, "tsmiSaveAs");
            this.tsmiSaveAs.Click += new System.EventHandler(this.tsmiSaveAs_Click);
            // 
            // tsmiUpload
            // 
            this.tsmiUpload.Name = "tsmiUpload";
            resources.ApplyResources(this.tsmiUpload, "tsmiUpload");
            this.tsmiUpload.Click += new System.EventHandler(this.tsmiUpload_Click);
            // 
            // tss1
            // 
            this.tss1.Name = "tss1";
            resources.ApplyResources(this.tss1, "tss1");
            // 
            // tsmiDecode
            // 
            this.tsmiDecode.Name = "tsmiDecode";
            resources.ApplyResources(this.tsmiDecode, "tsmiDecode");
            this.tsmiDecode.Click += new System.EventHandler(this.tsmiDecode_Click);
            // 
            // txtQRCode
            // 
            resources.ApplyResources(this.txtQRCode, "txtQRCode");
            this.txtQRCode.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.txtQRCode.Name = "txtQRCode";
            this.txtQRCode.TextChanged += new System.EventHandler(this.txtQRCode_TextChanged);
            // 
            // pbQRCode
            // 
            resources.ApplyResources(this.pbQRCode, "pbQRCode");
            this.pbQRCode.ContextMenuStrip = this.cmsQR;
            this.pbQRCode.Name = "pbQRCode";
            this.pbQRCode.TabStop = false;
            // 
            // tcMain
            // 
            this.tcMain.Controls.Add(this.tpEncode);
            this.tcMain.Controls.Add(this.tpDecode);
            resources.ApplyResources(this.tcMain, "tcMain");
            this.tcMain.Name = "tcMain";
            this.tcMain.SelectedIndex = 0;
            // 
            // tpEncode
            // 
            this.tpEncode.BackColor = System.Drawing.SystemColors.Window;
            this.tpEncode.Controls.Add(this.txtQRCode);
            this.tpEncode.Controls.Add(this.pbQRCode);
            resources.ApplyResources(this.tpEncode, "tpEncode");
            this.tpEncode.Name = "tpEncode";
            // 
            // tpDecode
            // 
            this.tpDecode.BackColor = System.Drawing.SystemColors.Window;
            this.tpDecode.Controls.Add(this.pDecodeResult);
            this.tpDecode.Controls.Add(this.btnDecodeFromFile);
            this.tpDecode.Controls.Add(this.lblDecodeResult);
            this.tpDecode.Controls.Add(this.btnDecodeFromScreen);
            resources.ApplyResources(this.tpDecode, "tpDecode");
            this.tpDecode.Name = "tpDecode";
            // 
            // btnDecodeFromFile
            // 
            resources.ApplyResources(this.btnDecodeFromFile, "btnDecodeFromFile");
            this.btnDecodeFromFile.Name = "btnDecodeFromFile";
            this.btnDecodeFromFile.UseVisualStyleBackColor = true;
            this.btnDecodeFromFile.Click += new System.EventHandler(this.btnDecodeFromFile_Click);
            // 
            // lblDecodeResult
            // 
            resources.ApplyResources(this.lblDecodeResult, "lblDecodeResult");
            this.lblDecodeResult.Name = "lblDecodeResult";
            // 
            // btnDecodeFromScreen
            // 
            resources.ApplyResources(this.btnDecodeFromScreen, "btnDecodeFromScreen");
            this.btnDecodeFromScreen.Name = "btnDecodeFromScreen";
            this.btnDecodeFromScreen.UseVisualStyleBackColor = true;
            this.btnDecodeFromScreen.Click += new System.EventHandler(this.btnDecodeFromScreen_Click);
            // 
            // rtbDecodeResult
            // 
            this.rtbDecodeResult.BorderStyle = System.Windows.Forms.BorderStyle.None;
            resources.ApplyResources(this.rtbDecodeResult, "rtbDecodeResult");
            this.rtbDecodeResult.Name = "rtbDecodeResult";
            this.rtbDecodeResult.LinkClicked += new System.Windows.Forms.LinkClickedEventHandler(this.rtbDecodeResult_LinkClicked);
            // 
            // pDecodeResult
            // 
            resources.ApplyResources(this.pDecodeResult, "pDecodeResult");
            this.pDecodeResult.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.pDecodeResult.Controls.Add(this.rtbDecodeResult);
            this.pDecodeResult.Name = "pDecodeResult";
            // 
            // QRCodeForm
            // 
            resources.ApplyResources(this, "$this");
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.BackColor = System.Drawing.SystemColors.Window;
            this.Controls.Add(this.tcMain);
            this.Name = "QRCodeForm";
            this.Shown += new System.EventHandler(this.QRCodeForm_Shown);
            this.Resize += new System.EventHandler(this.QRCodeForm_Resize);
            this.cmsQR.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)(this.pbQRCode)).EndInit();
            this.tcMain.ResumeLayout(false);
            this.tpEncode.ResumeLayout(false);
            this.tpEncode.PerformLayout();
            this.tpDecode.ResumeLayout(false);
            this.tpDecode.PerformLayout();
            this.pDecodeResult.ResumeLayout(false);
            this.ResumeLayout(false);

        }

        #endregion

        private System.Windows.Forms.TextBox txtQRCode;
        private System.Windows.Forms.ContextMenuStrip cmsQR;
        private System.Windows.Forms.ToolStripMenuItem tsmiCopy;
        private System.Windows.Forms.ToolStripMenuItem tsmiSaveAs;
        private System.Windows.Forms.PictureBox pbQRCode;
        private System.Windows.Forms.TabControl tcMain;
        private System.Windows.Forms.TabPage tpEncode;
        private System.Windows.Forms.TabPage tpDecode;
        private System.Windows.Forms.Button btnDecodeFromScreen;
        private System.Windows.Forms.Label lblDecodeResult;
        private System.Windows.Forms.Button btnDecodeFromFile;
        private System.Windows.Forms.ToolStripMenuItem tsmiDecode;
        private System.Windows.Forms.ToolStripMenuItem tsmiUpload;
        private System.Windows.Forms.ToolStripSeparator tss1;
        private System.Windows.Forms.RichTextBox rtbDecodeResult;
        private System.Windows.Forms.Panel pDecodeResult;
    }
}