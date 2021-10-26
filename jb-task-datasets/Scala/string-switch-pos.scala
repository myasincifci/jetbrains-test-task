import scala.tools.partest._

object Test extends DirectTest {
  override def extraSettings: String = "-usejavacp -stop:cleanup -Vprint:patmat,cleanup -Vprint-pos"

  override def code =
    """class Switch {
      |  def switch(s: String, cond: Boolean) = s match {
      |    case "AaAa"         => 1
      |    case "asdf"         => 2
      |    case "BbBb" if cond => 3
      |    case "BbBb"         => 4
      |    case "CcCc" | "Cc2" => 5
      |  }
      |}
    """.stripMargin.trim

  override def show(): Unit = compile()
}
