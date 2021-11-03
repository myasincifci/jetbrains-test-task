﻿namespace ShareX.UploadersLib
{
    partial class OCRSpaceForm
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(OCRSpaceForm));
            this.cbLanguages = new System.Windows.Forms.ComboBox();
            this.lblLanguage = new System.Windows.Forms.Label();
            this.txtResult = new System.Windows.Forms.TextBox();
            this.lblResult = new System.Windows.Forms.Label();
            this.btnStartOCR = new System.Windows.Forms.Button();
            this.pbProgress = new System.Windows.Forms.ProgressBar();
            this.btnOpenInBrowser = new System.Windows.Forms.Button();
            this.cbDefaultSite = new System.Windows.Forms.ComboBox();
            this.lblExternalSite = new System.Windows.Forms.Label();
            this.SuspendLayout();
            // 
            // cbLanguages
            // 
            this.cbLanguages.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cbLanguages.FormattingEnabled = true;
            resources.ApplyResources(this.cbLanguages, "cbLanguages");
            this.cbLanguages.Name = "cbLanguages";
            this.cbLanguages.SelectedIndexChanged += new System.EventHandler(this.cbLanguages_SelectedIndexChanged);
            // 
            // lblLanguage
            // 
            resources.ApplyResources(this.lblLanguage, "lblLanguage");
            this.lblLanguage.Name = "lblLanguage";
            // 
            // txtResult
            // 
            resources.ApplyResources(this.txtResult, "txtResult");
            this.txtResult.Name = "txtResult";
            // 
            // lblResult
            // 
            resources.ApplyResources(this.lblResult, "lblResult");
            this.lblResult.Name = "lblResult";
            // 
            // btnStartOCR
            // 
            resources.ApplyResources(this.btnStartOCR, "btnStartOCR");
            this.btnStartOCR.Name = "btnStartOCR";
            this.btnStartOCR.UseVisualStyleBackColor = true;
            this.btnStartOCR.Click += new System.EventHandler(this.btnStartOCR_Click);
            // 
            // pbProgress
            // 
            resources.ApplyResources(this.pbProgress, "pbProgress");
            this.pbProgress.MarqueeAnimationSpeed = 50;
            this.pbProgress.Name = "pbProgress";
            this.pbProgress.Style = System.Windows.Forms.ProgressBarStyle.Marquee;
            // 
            // btnOpenInBrowser
            // 
            resources.ApplyResources(this.btnOpenInBrowser, "btnOpenInBrowser");
            this.btnOpenInBrowser.Name = "btnOpenInBrowser";
            this.btnOpenInBrowser.UseVisualStyleBackColor = true;
            this.btnOpenInBrowser.Click += new System.EventHandler(this.btnOpenInBrowser_Click);
            // 
            // cbDefaultSite
            // 
            this.cbDefaultSite.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cbDefaultSite.FormattingEnabled = true;
            resources.ApplyResources(this.cbDefaultSite, "cbDefaultSite");
            this.cbDefaultSite.Name = "cbDefaultSite";
            this.cbDefaultSite.SelectedIndexChanged += new System.EventHandler(this.cbDefaultSite_SelectedIndexChanged);
            // 
            // lblExternalSite
            // 
            resources.ApplyResources(this.lblExternalSite, "lblExternalSite");
            this.lblExternalSite.Name = "lblExternalSite";
            // 
            // OCRSpaceForm
            // 
            resources.ApplyResources(this, "$this");
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Dpi;
            this.Controls.Add(this.lblExternalSite);
            this.Controls.Add(this.cbDefaultSite);
            this.Controls.Add(this.btnOpenInBrowser);
            this.Controls.Add(this.lblResult);
            this.Controls.Add(this.txtResult);
            this.Controls.Add(this.lblLanguage);
            this.Controls.Add(this.cbLanguages);
            this.Controls.Add(this.pbProgress);
            this.Controls.Add(this.btnStartOCR);
            this.Name = "OCRSpaceForm";
            this.SizeGripStyle = System.Windows.Forms.SizeGripStyle.Hide;
            this.Shown += new System.EventHandler(this.OCRSpaceResultForm_Shown);
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.ComboBox cbLanguages;
        private System.Windows.Forms.Label lblLanguage;
        private System.Windows.Forms.TextBox txtResult;
        private System.Windows.Forms.Label lblResult;
        private System.Windows.Forms.Button btnStartOCR;
        private System.Windows.Forms.ProgressBar pbProgress;
        private System.Windows.Forms.Button btnOpenInBrowser;
        private System.Windows.Forms.ComboBox cbDefaultSite;
        private System.Windows.Forms.Label lblExternalSite;
    }
}