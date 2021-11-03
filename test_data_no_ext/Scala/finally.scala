
object Test extends App {


  // test that finally is not covered by any exception handlers.
  def throwCatchFinally(): Unit = {
    try {
      bar()
    } catch {
      case e: Throwable => println(e)
    }
  }

  // test that finally is not covered by any exception handlers.
  def bar(): Unit = {
    try {
      println("hi")
    }
    catch {
      case e: Throwable => println("SHOULD NOT GET HERE")
    }
    finally {
      println("In Finally")
      throw new RuntimeException("ouch")
    }
  }

  // return in catch (finally is executed)
  def retCatch(): Unit = {
    try {
      throw new Exception
    } catch {
      case e: Throwable =>
        println(e);
        return
    } finally println("in finally")
  }

  // throw in catch (finally is executed, exception propagated)
  def throwCatch(): Unit = {
    try {
      throw new Exception
    } catch {
      case e: Throwable =>
        println(e);
        throw e
    } finally println("in finally")
  }

  // return inside body (finally is executed)
  def retBody(): Unit = {
    try {
      return
    } catch {
      case e: Throwable =>
        println(e);
        throw e
    } finally println("in finally")
  }

  // throw inside body (finally and catch are executed)
  def throwBody(): Unit = {
    try {
      throw new Exception
    } catch {
      case e: Throwable =>
        println(e);
    } finally println("in finally")
  }

  // return inside finally (each finally is executed once)
  def retFinally(): Unit = {
    try {
      try println("body")
      finally {
        println("in finally 1")
        return
      }
    } finally println("in finally 2")
  }


  // throw inside finally (finally is executed once, exception is propagated)
  def throwFinally(): Unit = {
    try {
      try println("body")
      finally {
        println("in finally")
        throw new Exception
      }
    } catch {
      case e: Throwable => println(e)
    }
  }

  // nested finally blocks with return value
  def nestedFinallyBlocks(): Int =
    try {
      try {
        return 10
      } finally {
        try { () } catch { case _: Throwable => () }
        println("in finally 1")
      }
    } finally {
      println("in finally 2")
    }

  def test[A](m: => A, name: String): Unit = {
    println("Running %s".format(name))
    try {
      m
    } catch {
      case e: Throwable => println("CAUGHT: " + e)
    }
    println("-" * 40)
  }

  test(throwCatchFinally(), "throwCatchFinally")
  test(retCatch(), "retCatch")
  test(throwCatch(), "throwCatch")
  test(retBody(), "retBody")
  test(throwBody(), "throwBody")
  test(retFinally(), "retFinally")
  test(throwFinally(), "throwFinally")
  test(nestedFinallyBlocks(), "nestedFinallyBlocks")
}
