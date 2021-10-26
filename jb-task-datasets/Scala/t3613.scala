class Boopy {
  private val s = new Schnuck
  def observer : PartialFunction[ Any, Unit ] = s.observer

  private class Schnuck extends javax.swing.AbstractListModel[AnyRef] { model =>
    val observer : PartialFunction[ Any, Unit ] = {
      case "Boopy" => fireIntervalAdded( model, 0, 1 )
    }
    def getSize = 0
    def getElementAt(idx: Int): AnyRef = null
  }

}

object Test {
  def main(args: Array[String]): Unit = {
    val x = new Boopy
    val o = x.observer
    o( "Boopy" ) // --> throws runtime error
  }
}
