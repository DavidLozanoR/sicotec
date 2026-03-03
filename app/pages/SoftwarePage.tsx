import { useState } from "react";
import { useNavigate } from "react-router";
import { TerminologyModal } from "../components/TerminologyModal";

interface Software {
  id: string;
  name: string;
  category: string;
  latestVersion: string;
  installedCount: number;
  lastUpdate: string;
  description: string;
}

export default function SoftwarePage() {
  const navigate = useNavigate();
  const [terminologyOpen, setTerminologyOpen] = useState(false);

  const [softwareList] = useState<Software[]>([
    {
      id: "SW001",
      name: "Microsoft Office",
      category: "Productividad",
      latestVersion: "2021",
      installedCount: 45,
      lastUpdate: "2025-01-15",
      description: "Suite de productividad para procesamiento de textos, hojas de cálculo y presentaciones",
    },
    {
      id: "SW002",
      name: "Adobe Acrobat",
      category: "Documentos",
      latestVersion: "DC 2024",
      installedCount: 32,
      lastUpdate: "2024-12-10",
      description: "Software para crear, editar y gestionar documentos PDF",
    },
    {
      id: "SW003",
      name: "Antivirus Kaspersky",
      category: "Seguridad",
      latestVersion: "21.3.10.391",
      installedCount: 50,
      lastUpdate: "2025-02-01",
      description: "Protección antivirus y seguridad informática",
    },
    {
      id: "SW004",
      name: "AutoCAD",
      category: "Diseño",
      latestVersion: "2024",
      installedCount: 12,
      lastUpdate: "2025-01-05",
      description: "Software de diseño asistido por computadora (CAD)",
    },
    {
      id: "SW005",
      name: "Navegador Chrome",
      category: "Internet",
      latestVersion: "121.0.6167.85",
      installedCount: 50,
      lastUpdate: "2025-02-10",
      description: "Navegador web de Google",
    },
  ]);

  const [selectedSoftware, setSelectedSoftware] = useState<Software | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [categoryFilter, setCategoryFilter] = useState("Todos");

  const categories = ["Todos", ...Array.from(new Set(softwareList.map((s) => s.category)))];

  const filteredSoftware = softwareList.filter((software) => {
    const query = searchQuery.toLowerCase();
    const matchesSearch =
      software.name.toLowerCase().includes(query) ||
      software.description.toLowerCase().includes(query);
    const matchesCategory = categoryFilter === "Todos" || software.category === categoryFilter;
    return matchesSearch && matchesCategory;
  });

  return (
    <>
      <nav className="navbar navbar-inverse sub-navbar navbar-fixed-top">
        <div className="container">
          <div className="navbar-header">
            <button
              type="button"
              className="navbar-toggle collapsed"
              data-toggle="collapse"
              data-target="#subenlaces"
            >
              <span className="sr-only">Menú de Navegación</span>
              <span className="icon-bar"></span>
              <span className="icon-bar"></span>
              <span className="icon-bar"></span>
            </button>
            <a className="navbar-brand" href="#" onClick={() => navigate("/dashboard")}>
              INEA - Sistema de Gestión
            </a>
          </div>
          <div className="collapse navbar-collapse" id="subenlaces">
            <ul className="nav navbar-nav navbar-right">
              <li>
                <a href="#" onClick={() => navigate("/usuarios")}>
                  <span className="glyphicon glyphicon-user"></span> Usuarios
                </a>
              </li>
              <li>
                <a href="#" onClick={() => navigate("/dispositivos")}>
                  <span className="glyphicon glyphicon-hdd"></span> Dispositivos
                </a>
              </li>
              <li className="active">
                <a href="#" onClick={() => navigate("/software")}>
                  <span className="glyphicon glyphicon-cog"></span> Software
                </a>
              </li>
              <li>
                <a href="#" onClick={() => navigate("/reportes")}>
                  <span className="glyphicon glyphicon-stats"></span> Reportes
                </a>
              </li>
              <li>
                <a href="#" onClick={() => setTerminologyOpen(true)}>
                  <span className="glyphicon glyphicon-book"></span> Terminologías
                </a>
              </li>
            </ul>
          </div>
        </div>
      </nav>

      <main className="page" style={{ marginTop: "70px" }}>
        <div className="container">
          <ol className="breadcrumb">
            <li>
              <a href="#" onClick={() => navigate("/dashboard")}>
                <i className="icon icon-home"></i>
              </a>
            </li>
            <li>
              <a href="#">INEA</a>
            </li>
            <li className="active">Software</li>
          </ol>

          <div className="row">
            <div className="col-md-12">
              <h1>Gestión de Software</h1>
            </div>
          </div>
          <hr className="red" />

          <div className="row">
            <div className="col-md-12">
              <p>
                Administre el software disponible en el sistema y sus versiones instaladas en los
                dispositivos.
              </p>
            </div>
          </div>

          <div className="row" style={{ marginTop: "30px", marginBottom: "20px" }}>
            <div className="col-sm-6 col-md-4">
              <div className="form-group">
                <label htmlFor="categoryFilter" className="control-label">
                  Filtrar por Categoría
                </label>
                <select
                  id="categoryFilter"
                  className="form-control"
                  value={categoryFilter}
                  onChange={(e) => setCategoryFilter(e.target.value)}
                >
                  {categories.map((cat) => (
                    <option key={cat} value={cat}>
                      {cat}
                    </option>
                  ))}
                </select>
              </div>
            </div>
            <div className="col-sm-6 col-md-8">
              <div className="form-group">
                <label htmlFor="searchSoftware" className="control-label">
                  Buscar Software
                </label>
                <div className="input-group">
                  <span className="input-group-addon">
                    <span className="glyphicon glyphicon-search"></span>
                  </span>
                  <input
                    id="searchSoftware"
                    type="text"
                    className="form-control"
                    placeholder="Buscar por nombre o descripción..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                  />
                </div>
              </div>
            </div>
          </div>

          {filteredSoftware.length === 0 ? (
            <div className="row">
              <div className="col-md-12">
                <div
                  className="alert alert-info"
                  style={{ textAlign: "center", padding: "40px" }}
                >
                  <span
                    className="glyphicon glyphicon-info-sign"
                    style={{ fontSize: "48px", display: "block", marginBottom: "20px" }}
                  ></span>
                  <h3 style={{ marginTop: 0 }}>No se encontró software</h3>
                  <p>No hay software que coincida con la búsqueda.</p>
                </div>
              </div>
            </div>
          ) : (
            <div className="row">
              <div className="col-md-12">
                <div className="table-responsive">
                  <table className="table table-striped table-bordered">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Software</th>
                        <th>Categoría</th>
                        <th>Versión</th>
                        <th style={{ textAlign: "center" }}>Instalaciones</th>
                        <th>Última Actualización</th>
                        <th style={{ width: "100px", textAlign: "center" }}>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      {filteredSoftware.map((software) => (
                        <tr key={software.id}>
                          <td>
                            <strong>{software.id}</strong>
                          </td>
                          <td>
                            <span
                              className="glyphicon glyphicon-cog"
                              style={{ marginRight: "8px", color: "#888" }}
                            ></span>
                            {software.name}
                          </td>
                          <td>
                            <span className="label label-info">{software.category}</span>
                          </td>
                          <td>
                            <code>{software.latestVersion}</code>
                          </td>
                          <td style={{ textAlign: "center" }}>
                            <span className="badge" style={{ backgroundColor: "#4D92DF" }}>
                              {software.installedCount}
                            </span>
                          </td>
                          <td>
                            <span className="glyphicon glyphicon-calendar"></span>{" "}
                            {software.lastUpdate}
                          </td>
                          <td style={{ textAlign: "center" }}>
                            <button
                              type="button"
                              className="btn btn-primary btn-sm"
                              onClick={() => setSelectedSoftware(software)}
                            >
                              <span className="glyphicon glyphicon-eye-open"></span> Ver
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {/* Modal de detalles de software */}
          {selectedSoftware && (
            <>
              <div className="modal fade in" style={{ display: "block" }} role="dialog">
                <div className="modal-dialog modal-lg">
                  <div className="modal-content">
                    <div className="modal-header">
                      <button
                        type="button"
                        className="close"
                        onClick={() => setSelectedSoftware(null)}
                      >
                        <span>&times;</span>
                      </button>
                      <h4 className="modal-title">
                        <span className="glyphicon glyphicon-cog"></span> Detalles del Software
                      </h4>
                    </div>
                    <div className="modal-body">
                      <div className="row">
                        <div className="col-md-8">
                          <h3 style={{ marginTop: 0 }}>{selectedSoftware.name}</h3>
                          <p style={{ fontSize: "15px", color: "#666", marginBottom: "20px" }}>
                            {selectedSoftware.description}
                          </p>
                        </div>
                        <div className="col-md-4">
                          <div
                            style={{
                              textAlign: "center",
                              padding: "20px",
                              backgroundColor: "#f6f6f6",
                              borderRadius: "4px",
                            }}
                          >
                            <span
                              className="glyphicon glyphicon-hdd"
                              style={{ fontSize: "40px", color: "#4D92DF", display: "block" }}
                            ></span>
                            <h2 style={{ margin: "10px 0 5px 0", color: "#4D92DF" }}>
                              {selectedSoftware.installedCount}
                            </h2>
                            <p style={{ margin: 0, color: "#666" }}>Instalaciones</p>
                          </div>
                        </div>
                      </div>

                      <hr />

                      <div className="row">
                        <div className="col-md-6">
                          <dl>
                            <dt>ID del Software:</dt>
                            <dd>{selectedSoftware.id}</dd>
                            <dt>Categoría:</dt>
                            <dd>
                              <span className="label label-info">{selectedSoftware.category}</span>
                            </dd>
                            <dt>Versión Más Reciente:</dt>
                            <dd>
                              <code>{selectedSoftware.latestVersion}</code>
                            </dd>
                          </dl>
                        </div>
                        <div className="col-md-6">
                          <dl>
                            <dt>Total de Instalaciones:</dt>
                            <dd>
                              <span className="badge" style={{ backgroundColor: "#4D92DF" }}>
                                {selectedSoftware.installedCount}
                              </span>{" "}
                              dispositivos
                            </dd>
                            <dt>Última Actualización:</dt>
                            <dd>
                              <span className="glyphicon glyphicon-calendar"></span>{" "}
                              {selectedSoftware.lastUpdate}
                            </dd>
                          </dl>
                        </div>
                      </div>

                      <hr />

                      <div className="alert alert-info" style={{ marginBottom: 0 }}>
                        <p style={{ margin: 0 }}>
                          <span
                            className="glyphicon glyphicon-info-sign"
                            style={{ marginRight: "8px" }}
                          ></span>
                          Para ver en qué dispositivos específicos está instalado este software o
                          actualizar versiones, diríjase al módulo de <strong>Dispositivos</strong>{" "}
                          o <strong>Reportes</strong>.
                        </p>
                      </div>
                    </div>
                    <div className="modal-footer">
                      <button
                        type="button"
                        className="btn btn-default"
                        onClick={() => setSelectedSoftware(null)}
                      >
                        Cerrar
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div
                className="modal-backdrop fade in"
                onClick={() => setSelectedSoftware(null)}
              ></div>
            </>
          )}

          <TerminologyModal
            open={terminologyOpen}
            onClose={() => setTerminologyOpen(false)}
            department="Tecnología"
          />
        </div>
      </main>
    </>
  );
}
