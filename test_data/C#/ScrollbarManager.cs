﻿#region License Information (GPL v3)

/*
    ShareX - A program that allows you to take screenshots and share any file type
    Copyright (c) 2007-2021 ShareX Team

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

    Optionally you can also view the license at <http://www.gnu.org/licenses/>.
*/

#endregion License Information (GPL v3)

using System.Windows.Forms;

namespace ShareX.ScreenCaptureLib
{
    internal class ScrollbarManager
    {
        public bool Visible => horizontalScrollbar.Visible || verticalScrollbar.Visible;

        private RegionCaptureForm form;
        private ImageEditorScrollbar horizontalScrollbar, verticalScrollbar;

        public ScrollbarManager(RegionCaptureForm regionCaptureForm, ShapeManager shapeManager)
        {
            form = regionCaptureForm;
            horizontalScrollbar = new ImageEditorScrollbar(Orientation.Horizontal, form);
            shapeManager.DrawableObjects.Add(horizontalScrollbar);
            verticalScrollbar = new ImageEditorScrollbar(Orientation.Vertical, form);
            shapeManager.DrawableObjects.Add(verticalScrollbar);
        }

        public void Update()
        {
            horizontalScrollbar.Update();
            verticalScrollbar.Update();
        }
    }
}