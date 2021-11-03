﻿using System;
using System.Collections.Generic;
using System.Drawing;
using System.Numerics;

namespace Algorithms.Other
{
    /// <summary>
    /// Flood fill, also called seed fill, is an algorithm that determines and
    /// alters the area connected to a given node in a multi-dimensional array with
    /// some matching attribute. It is used in the "bucket" fill tool of paint
    /// programs to fill connected, similarly-colored areas with a different color.
    /// (description adapted from https://en.wikipedia.org/wiki/Flood_fill)
    /// (see also: https://www.techiedelight.com/flood-fill-algorithm/).
    /// </summary>
    public static class FloodFill
    {
        private static List<(int xOffset, int yOffset)> neighbors = new List<(int xOffset, int yOffset)> { (-1, -1), (-1, 0), (-1, 1), (0, -1), (0, 1), (1, -1), (1, 0), (1, 1) };

        /// <summary>
        /// Implements the flood fill algorithm through a breadth-first approach using a queue.
        /// </summary>
        /// <param name="bitmap">The bitmap to which the algorithm is applied.</param>
        /// <param name="location">The start location on the bitmap.</param>
        /// <param name="targetColor">The old color to be replaced.</param>
        /// <param name="replacementColor">The new color to replace the old one.</param>
        public static void BreadthFirstSearch(Bitmap bitmap, (int x, int y) location, Color targetColor, Color replacementColor)
        {
            if (location.x < 0 || location.x >= bitmap.Width || location.y < 0 || location.y >= bitmap.Height)
            {
                throw new ArgumentOutOfRangeException(nameof(location), $"{nameof(location)} should point to a pixel within the bitmap");
            }

            var queue = new List<(int x, int y)>();
            queue.Add(location);

            while (queue.Count > 0)
            {
                BreadthFirstFill(bitmap, location, targetColor, replacementColor, queue);
            }
        }

        /// <summary>
        /// Implements the flood fill algorithm through a depth-first approach through recursion.
        /// </summary>
        /// <param name="bitmap">The bitmap to which the algorithm is applied.</param>
        /// <param name="location">The start location on the bitmap.</param>
        /// <param name="targetColor">The old color to be replaced.</param>
        /// <param name="replacementColor">The new color to replace the old one.</param>
        public static void DepthFirstSearch(Bitmap bitmap, (int x, int y) location, Color targetColor, Color replacementColor)
        {
            if (location.x < 0 || location.x >= bitmap.Width || location.y < 0 || location.y >= bitmap.Height)
            {
                throw new ArgumentOutOfRangeException(nameof(location), $"{nameof(location)} should point to a pixel within the bitmap");
            }

            DepthFirstFill(bitmap, location, targetColor, replacementColor);
        }

        private static void BreadthFirstFill(Bitmap bitmap, (int x, int y) location, Color targetColor, Color replacementColor, List<(int x, int y)> queue)
        {
            (int x, int y) currentLocation = queue[0];
            queue.RemoveAt(0);

            if (bitmap.GetPixel(currentLocation.x, currentLocation.y) == targetColor)
            {
                bitmap.SetPixel(currentLocation.x, currentLocation.y, replacementColor);

                for (int i = 0; i < neighbors.Count; i++)
                {
                    int x = currentLocation.x + neighbors[i].xOffset;
                    int y = currentLocation.y + neighbors[i].yOffset;
                    if (x >= 0 && x < bitmap.Width && y >= 0 && y < bitmap.Height)
                    {
                        queue.Add((x, y));
                    }
                }
            }
        }

        private static void DepthFirstFill(Bitmap bitmap, (int x, int y) location, Color targetColor, Color replacementColor)
        {
            if (bitmap.GetPixel(location.x, location.y) == targetColor)
            {
                bitmap.SetPixel(location.x, location.y, replacementColor);

                for (int i = 0; i < neighbors.Count; i++)
                {
                    int x = location.x + neighbors[i].xOffset;
                    int y = location.y + neighbors[i].yOffset;
                    if (x >= 0 && x < bitmap.Width && y >= 0 && y < bitmap.Height)
                    {
                        DepthFirstFill(bitmap, (x, y), targetColor, replacementColor);
                    }
                }
            }
        }
    }
}
