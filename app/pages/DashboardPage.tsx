import { useNavigate } from "react-router";

export default function DashboardPage() {
  const navigate = useNavigate();

  const modules = [
    {
      title: "Gestión de Usuarios",
      description: "Administre usuarios y personal del sistema INEA",
      icon: "glyphicon-user",
      color: "#4D92DF",
      path: "/usuarios",
    },
    {
      title: "Gestión de Dispositivos",
      description: "Controle dispositivos, red y dominio ineadom.gob.mx",
      icon: "glyphicon-hdd",
      color: "#8CC63F",
      path: "/dispositivos",
    },
    {
      title: "Gestión de Software",
      description: "Administre software y versiones instaladas",
      icon: "glyphicon-cog",
      color: "#F68D2E",
      path: "/software",
    },
    {
      title: "Reportes y Estadísticas",
      description: "Genere reportes del sistema y analice datos",
      icon: "glyphicon-stats",
      color: "#9D2449",
      path: "/reportes",
    },
  ];

  return (
    <>
      <nav className="navbar navbar-inverse sub-navbar navbar-fixed-top">
        <div className="container">
          <div className="navbar-header">
            <a className="navbar-brand" href="#">
              INEA - Sistema de Gestión
            </a>
          </div>
          <div className="navbar-right" style={{ padding: "15px" }}>
            <button
              type="button"
              className="btn btn-danger btn-sm"
              onClick={() => navigate("/")}
            >
              <span className="glyphicon glyphicon-log-out"></span> Cerrar Sesión
            </button>
          </div>
        </div>
      </nav>

      <main className="page" style={{ marginTop: "70px" }}>
        <div className="container">
          <ol className="breadcrumb">
            <li>
              <a href="#">
                <i className="icon icon-home"></i>
              </a>
            </li>
            <li className="active">Panel Principal</li>
          </ol>

          <div className="row">
            <div className="col-md-12">
              <h1>Panel Principal</h1>
            </div>
          </div>
          <hr className="red" />

          <div className="row">
            <div className="col-md-12">
              <p style={{ fontSize: "16px", marginBottom: "40px" }}>
                Bienvenido al Sistema de Gestión del Instituto Nacional para la Educación de los
                Adultos. Seleccione un módulo para continuar.
              </p>
            </div>
          </div>

          {/* Botones de los módulos */}
          <div className="row">
            {modules.map((module, index) => (
              <div key={index} className="col-md-6 col-sm-6" style={{ marginBottom: "30px" }}>
                <div
                  className="panel panel-default"
                  style={{
                    cursor: "pointer",
                    transition: "all 0.3s ease",
                    border: `2px solid ${module.color}`,
                    minHeight: "220px",
                  }}
                  onClick={() => navigate(module.path)}
                  onMouseEnter={(e) => {
                    e.currentTarget.style.transform = "translateY(-5px)";
                    e.currentTarget.style.boxShadow = "0 8px 16px rgba(0,0,0,0.2)";
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.transform = "translateY(0)";
                    e.currentTarget.style.boxShadow = "none";
                  }}
                >
                  <div
                    className="panel-heading"
                    style={{ backgroundColor: module.color, color: "white", padding: "20px" }}
                  >
                    <h3
                      className="panel-title"
                      style={{ fontSize: "20px", fontWeight: "bold", margin: 0 }}
                    >
                      <span
                        className={`glyphicon ${module.icon}`}
                        style={{ marginRight: "15px", fontSize: "24px" }}
                      ></span>
                      {module.title}
                    </h3>
                  </div>
                  <div className="panel-body" style={{ padding: "25px", minHeight: "120px" }}>
                    <p style={{ fontSize: "15px", marginBottom: "20px", color: "#666" }}>
                      {module.description}
                    </p>
                    <button
                      className="btn btn-default btn-block"
                      style={{
                        borderColor: module.color,
                        color: module.color,
                        fontWeight: "bold",
                      }}
                      onMouseEnter={(e) => {
                        e.currentTarget.style.backgroundColor = module.color;
                        e.currentTarget.style.color = "white";
                      }}
                      onMouseLeave={(e) => {
                        e.currentTarget.style.backgroundColor = "transparent";
                        e.currentTarget.style.color = module.color;
                      }}
                    >
                      Acceder al Módulo
                      <span
                        className="glyphicon glyphicon-chevron-right"
                        style={{ marginLeft: "10px" }}
                      ></span>
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </main>
    </>
  );
}
