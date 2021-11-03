﻿using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using DataStructures.Probabilistic;
using NUnit.Framework;

namespace DataStructures.Tests.Probabilistic
{
    public class BloomFilterTests
    {
        static string[] TestNames = new[] { "kal;jsnfka", "alkjsdfn;lakm", "aljfopiawjf", "afowjeaofeij", "oajwsefoaiwje", "aoiwjfaoiejmf", "aoijfoawiejf" };
        public class SimpleObject
        {
            public string Name { get; set; }
            public int Number { get; set; }

            public SimpleObject(string name, int number)
            {
                Name = name;
                Number = number;
            }
        }

        public class SimpleObjectOverridenHash
        {
            private const uint FnvPrime = 16777619;
            private const uint FnvOffsetBasis = 2166136261;
            public string Name { get; set; }
            public int Number { get; set; }

            public SimpleObjectOverridenHash(string name, int number)
            {
                Name = name;
                Number = number;
            }

            public override int GetHashCode()
            {
                var bytes = Encoding.UTF8.GetBytes(Name).Concat(BitConverter.GetBytes(Number));
                var hash = FnvOffsetBasis;
                foreach (var @byte in bytes)
                {
                    hash = hash * FnvPrime;
                    hash ^= @byte;
                }

                return (int)hash;
            }

            public override bool Equals(object? obj)
            {
                return obj is SimpleObjectOverridenHash asSimpleObj && asSimpleObj.Name == Name && asSimpleObj.Number == Number;
            }
        }

        [Test]
        public void TestBloomFilterInsertOptimalSize()
        {
            var filter = new BloomFilter<int>(1000);
            var set = new HashSet<int>();
            var rand = new Random();
            var falsePositives = 0;
            for (var i = 0; i < 1000; i++)
            {
                var k = rand.Next(0, 1000);
                if (!set.Contains(k) && filter.Search(k))
                {
                    falsePositives++;
                }
                filter.Insert(k);
                set.Add(k);
                Assert.IsTrue(filter.Search(k));
            }

            Assert.True(.05 > falsePositives / 1000.0); // be a bit generous in our fault tolerance here
        }

        [Test]
        public void TestBloomFilterInsert()
        {
            var filter = new BloomFilter<SimpleObject>(100000, 3);
            var rand = new Random();
            for (var i = 0; i < 1000; i++)
            {
                var simpleObject = new SimpleObject(TestNames[rand.Next(TestNames.Length)], rand.Next(15));
                filter.Insert(simpleObject);
                Assert.IsTrue(filter.Search(simpleObject));
            }
        }

        [Test]
        public void TestBloomFilterSearchOverridenHash()
        {
            var filter = new BloomFilter<SimpleObjectOverridenHash>(100000, 3);
            var simpleObjectInserted = new SimpleObjectOverridenHash("foo", 1);
            var simpleObjectInserted2 = new SimpleObjectOverridenHash("foo", 1);
            var simpleObjectNotInserted = new SimpleObjectOverridenHash("bar", 2);
            filter.Insert(simpleObjectInserted);
            Assert.IsTrue(filter.Search(simpleObjectInserted));
            Assert.IsTrue(filter.Search(simpleObjectInserted2));
            Assert.IsFalse(filter.Search(simpleObjectNotInserted));
        }

        [Test]
        public void TestBloomFilterSearch()
        {
            var filter = new BloomFilter<SimpleObject>(10000, 3);
            var simpleObjectInserted = new SimpleObject("foo", 1);
            var simpleObjectNotInserted = new SimpleObject("foo", 1);
            filter.Insert(simpleObjectInserted);
            Assert.False(filter.Search(simpleObjectNotInserted));
            Assert.True(filter.Search(simpleObjectInserted));

        }
    }
}
