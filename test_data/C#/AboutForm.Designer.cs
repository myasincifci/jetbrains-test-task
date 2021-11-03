﻿namespace ShareX
{
    partial class AboutForm
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

            if (easterEgg != null) easterEgg.Dispose();

            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(AboutForm));
            this.lblProductName = new System.Windows.Forms.Label();
            this.lblJaex = new System.Windows.Forms.Label();
            this.lblMike = new System.Windows.Forms.Label();
            this.rtbCredits = new System.Windows.Forms.RichTextBox();
            this.rtbShareXInfo = new System.Windows.Forms.RichTextBox();
            this.pbMikeURL = new System.Windows.Forms.PictureBox();
            this.pbJaexURL = new System.Windows.Forms.PictureBox();
            this.lblTeam = new System.Windows.Forms.Label();
            this.pbLogo = new System.Windows.Forms.PictureBox();
            this.btnClose = new System.Windows.Forms.Button();
            this.btnShareXLicense = new System.Windows.Forms.Button();
            this.btnLicenses = new System.Windows.Forms.Button();
            this.uclUpdate = new ShareX.HelpersLib.UpdateCheckerLabel();
            this.cLogo = new ShareX.HelpersLib.Canvas();
            this.lblBuild = new System.Windows.Forms.Label();
            ((System.ComponentModel.ISupportInitialize)(this.pbMikeURL)).BeginInit();
            ((System.ComponentModel.ISupportInitialize)(this.pbJaexURL)).BeginInit();
            ((System.ComponentModel.ISupportInitialize)(this.pbLogo)).BeginInit();
            this.SuspendLayout();
            // 
            // lblProductName
            // 
            resources.ApplyResources(this.lblProductName, "lblProductName");
            this.lblProductName.BackColor = System.Drawing.Color.Transparent;
            this.lblProductName.Name = "lblProductName";
            // 
            // lblJaex
            // 
            resources.ApplyResources(this.lblJaex, "lblJaex");
            this.lblJaex.Name = "lblJaex";
            // 
            // lblMike
            // 
            resources.ApplyResources(this.lblMike, "lblMike");
            this.lblMike.Name = "lblMike";
            // 
            // rtbCredits
            // 
            resources.ApplyResources(this.rtbCredits, "rtbCredits");
            this.rtbCredits.BackColor = System.Drawing.SystemColors.Window;
            this.rtbCredits.BorderStyle = System.Windows.Forms.BorderStyle.None;
            this.rtbCredits.ForeColor = System.Drawing.SystemColors.ControlText;
            this.rtbCredits.Name = "rtbCredits";
            this.rtbCredits.ReadOnly = true;
            this.rtbCredits.LinkClicked += new System.Windows.Forms.LinkClickedEventHandler(this.rtb_LinkClicked);
            // 
            // rtbShareXInfo
            // 
            this.rtbShareXInfo.BackColor = System.Drawing.SystemColors.Window;
            this.rtbShareXInfo.BorderStyle = System.Windows.Forms.BorderStyle.None;
            this.rtbShareXInfo.ForeColor = System.Drawing.SystemColors.ControlText;
            resources.ApplyResources(this.rtbShareXInfo, "rtbShareXInfo");
            this.rtbShareXInfo.Name = "rtbShareXInfo";
            this.rtbShareXInfo.ReadOnly = true;
            this.rtbShareXInfo.LinkClicked += new System.Windows.Forms.LinkClickedEventHandler(this.rtb_LinkClicked);
            // 
            // pbMikeURL
            // 
            this.pbMikeURL.BackColor = System.Drawing.Color.Transparent;
            this.pbMikeURL.Cursor = System.Windows.Forms.Cursors.Hand;
            this.pbMikeURL.Image = global::ShareX.Properties.Resources.GitHub;
            resources.ApplyResources(this.pbMikeURL, "pbMikeURL");
            this.pbMikeURL.Name = "pbMikeURL";
            this.pbMikeURL.TabStop = false;
            this.pbMikeURL.Click += new System.EventHandler(this.pbMikeURL_Click);
            // 
            // pbJaexURL
            // 
            this.pbJaexURL.BackColor = System.Drawing.Color.Transparent;
            this.pbJaexURL.Cursor = System.Windows.Forms.Cursors.Hand;
            this.pbJaexURL.Image = global::ShareX.Properties.Resources.GitHub;
            resources.ApplyResources(this.pbJaexURL, "pbJaexURL");
            this.pbJaexURL.Name = "pbJaexURL";
            this.pbJaexURL.TabStop = false;
            this.pbJaexURL.Click += new System.EventHandler(this.pbJaexURL_Click);
            // 
            // lblTeam
            // 
            resources.ApplyResources(this.lblTeam, "lblTeam");
            this.lblTeam.Name = "lblTeam";
            // 
            // pbLogo
            // 
            resources.ApplyResources(this.pbLogo, "pbLogo");
            this.pbLogo.BackColor = System.Drawing.Color.Transparent;
            this.pbLogo.Name = "pbLogo";
            this.pbLogo.TabStop = false;
            this.pbLogo.MouseDown += new System.Windows.Forms.MouseEventHandler(this.pbLogo_MouseDown);
            // 
            // btnClose
            // 
            resources.ApplyResources(this.btnClose, "btnClose");
            this.btnClose.DialogResult = System.Windows.Forms.DialogResult.Cancel;
            this.btnClose.Name = "btnClose";
            this.btnClose.UseVisualStyleBackColor = true;
            this.btnClose.Click += new System.EventHandler(this.btnClose_Click);
            // 
            // btnShareXLicense
            // 
            resources.ApplyResources(this.btnShareXLicense, "btnShareXLicense");
            this.btnShareXLicense.Name = "btnShareXLicense";
            this.btnShareXLicense.UseVisualStyleBackColor = true;
            this.btnShareXLicense.Click += new System.EventHandler(this.btnShareXLicense_Click);
            // 
            // btnLicenses
            // 
            resources.ApplyResources(this.btnLicenses, "btnLicenses");
            this.btnLicenses.Name = "btnLicenses";
            this.btnLicenses.UseVisualStyleBackColor = true;
            this.btnLicenses.Click += new System.EventHandler(this.btnLicenses_Click);
            // 
            // uclUpdate
            // 
            resources.ApplyResources(this.uclUpdate, "uclUpdate");
            this.uclUpdate.Name = "uclUpdate";
            // 
            // cLogo
            // 
            resources.ApplyResources(this.cLogo, "cLogo");
            this.cLogo.Interval = 100;
            this.cLogo.Name = "cLogo";
            // 
            // lblBuild
            // 
            resources.ApplyResources(this.lblBuild, "lblBuild");
            this.lblBuild.Name = "lblBuild";
            // 
            // AboutForm
            // 
            resources.ApplyResources(this, "$this");
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Dpi;
            this.BackColor = System.Drawing.SystemColors.Window;
            this.CancelButton = this.btnClose;
            this.Controls.Add(this.lblProductName);
            this.Controls.Add(this.lblBuild);
            this.Controls.Add(this.btnLicenses);
            this.Controls.Add(this.btnShareXLicense);
            this.Controls.Add(this.btnClose);
            this.Controls.Add(this.pbLogo);
            this.Controls.Add(this.lblTeam);
            this.Controls.Add(this.rtbShareXInfo);
            this.Controls.Add(this.rtbCredits);
            this.Controls.Add(this.lblJaex);
            this.Controls.Add(this.lblMike);
            this.Controls.Add(this.uclUpdate);
            this.Controls.Add(this.pbMikeURL);
            this.Controls.Add(this.pbJaexURL);
            this.Controls.Add(this.cLogo);
            this.Name = "AboutForm";
            this.SizeGripStyle = System.Windows.Forms.SizeGripStyle.Hide;
            this.Shown += new System.EventHandler(this.AboutForm_Shown);
            ((System.ComponentModel.ISupportInitialize)(this.pbMikeURL)).EndInit();
            ((System.ComponentModel.ISupportInitialize)(this.pbJaexURL)).EndInit();
            ((System.ComponentModel.ISupportInitialize)(this.pbLogo)).EndInit();
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion Windows Form Designer generated code

        private System.Windows.Forms.Label lblProductName;
        private System.Windows.Forms.Label lblJaex;
        private System.Windows.Forms.PictureBox pbJaexURL;
        private System.Windows.Forms.PictureBox pbMikeURL;
        private System.Windows.Forms.Label lblMike;
        private HelpersLib.Canvas cLogo;
        private System.Windows.Forms.RichTextBox rtbCredits;
        private System.Windows.Forms.RichTextBox rtbShareXInfo;
        private HelpersLib.UpdateCheckerLabel uclUpdate;
        private System.Windows.Forms.Label lblTeam;
        private System.Windows.Forms.PictureBox pbLogo;
        private System.Windows.Forms.Button btnClose;
        private System.Windows.Forms.Button btnShareXLicense;
        private System.Windows.Forms.Button btnLicenses;
        private System.Windows.Forms.Label lblBuild;
    }
}