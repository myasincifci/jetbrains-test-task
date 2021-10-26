// Copyright (c) Microsoft Corporation.  All Rights Reserved.  See License.txt in the project root for license information.

// Various tests for the:
// Microsoft.FSharp.Collections.HashIdentity module

namespace FSharp.Core.UnitTests.Collections

open System
open FSharp.Core.UnitTests.LibraryTestFx
open Xunit

open System.Collections.Generic

type HashIdentityModule() =

    [<Fact>]
    member this.FromFunction() =

        // value type
        let valueDict = new Dictionary<int,string>(HashIdentity.Structural)
        Assert.AreEqual(valueDict.Count,0)
        valueDict.Add(3,"C")
        Assert.AreEqual(1, valueDict.Count)
        Assert.True(valueDict.ContainsValue("C"))    

        // reference type
        let refDict = new Dictionary<string,int>(HashIdentity.Structural)
        Assert.AreEqual(refDict.Count,0)
        refDict.Add("...",3)
        Assert.AreEqual(1, refDict.Count)
        Assert.True(refDict.ContainsValue(3))

        // empty type
        let eptDict = new Dictionary<int,int>(HashIdentity.Structural)
        Assert.AreEqual(0, eptDict.Count)
        Assert.False(eptDict.ContainsKey(3))    

        ()
 
    [<Fact>]
    member this.Reference() =

 // reference type
        let refDict = new Dictionary<obj,int>(HashIdentity.Reference)
        Assert.AreEqual(refDict.Count,0)
        let obj1 = obj()
        let obj2 = obj()
        refDict.Add(obj1,3)
        Assert.AreEqual(1,refDict.Count)
        Assert.True(refDict.ContainsKey(obj1))
        Assert.False(refDict.ContainsKey(obj2))
        Assert.True(refDict.ContainsValue(3))

        // empty table
        let eptDict = new Dictionary<string,int>(HashIdentity.Reference)
        Assert.AreEqual(0,eptDict.Count)
        Assert.False(eptDict.ContainsKey("3"))    
 
        ()
        
    [<Fact>]
    member this.FromFunctions() =
        
        // value type
        let valueDict = new Dictionary<int,string>(HashIdentity.FromFunctions (fun x -> 1) (fun x y -> x > y))
        Assert.AreEqual(0,valueDict.Count)
        valueDict.Add(3,"C")
        Assert.AreEqual(1,valueDict.Count)
        Assert.True(valueDict.ContainsValue("C"))    

        // reference type
        let refDict = new Dictionary<string,int>(HashIdentity.FromFunctions (fun x -> 1) (fun x y -> x > y))
        Assert.AreEqual(0,refDict.Count)
        refDict.Add("...",3)
        Assert.AreEqual(1,refDict.Count)
        Assert.True(refDict.ContainsValue(3))

        // empty type     
        let eptDict = new Dictionary<int,int>(HashIdentity.FromFunctions (fun x -> 1) (fun x y -> x > y))
        Assert.AreEqual(0,eptDict.Count)
        Assert.False(eptDict.ContainsKey(3))
        
        ()