import { useState } from "react";

interface OperativePersonnel {
  id: string;
  name: string;
  position: string;
  department: string;
  email: string;
  deviceCount: number;
}

interface User {
  id: string;
  role: "Jefe" | "Usuario";
}

interface PersonnelModuleProps {
  currentUser: User;
}

export function PersonnelModule({ currentUser }: PersonnelModuleProps) {
  const [personnel, setPersonnel] = useState<OperativePersonnel[]>([
    {
      id: "OP001",
      name: "Pedro Martínez Ramírez",
      position: "Técnico de Soporte",
      department: "Tecnología",
      email: "pedro.martinez@inea.gob.mx",
      deviceCount: 2,
    },
    {
      id: "OP002",
      name: "Sofía López García",
      position: "Analista de Sistemas",
      department: "Tecnología",
      email: "sofia.lopez@inea.gob.mx",
      deviceCount: 3,
    },
    {
      id: "OP003",
      name: "Miguel Ángel Torres",
      position: "Coordinador de Campo",
      department: "Operaciones",
      email: "miguel.torres@inea.gob.mx",
      deviceCount: 1,
    },
    {
      id: "OP004",
      name: "Carmen Hernández Silva",
      position: "Asesora Educativa",
      department: "Operaciones",
      email: "carmen.hernandez@inea.gob.mx",
      deviceCount: 1,
    },
  ]);

  const [searchQuery, setSearchQuery] = useState("");
  const [selectedPersonnel, setSelectedPersonnel] = useState<OperativePersonnel | null>(null);

  const filteredPersonnel = personnel.filter((person) => {
    const query = searchQuery.toLowerCase();
    return (
      person.name.toLowerCase().includes(query) ||
      person.position.toLowerCase().includes(query) ||
      person.email.toLowerCase().includes(query)
    );
  });

  return (
    <>
      <ol className="breadcrumb">
        <li>
          <a href="#">
            <i className="icon icon-home"></i>
          </a>
        </li>
        <li>
          <a href="#">INEA</a>
        </li>
        <li className="active">Personal Operativo</li>
      </ol>

      <div className="row">
        <div className="col-md-12">
          <h1>Personal Operativo (OP)</h1>
        </div>
      </div>
      <hr className="red" />

      <div className="row">
        <div className="col-md-12">
          <p>
            Consulte el personal operativo y los dispositivos asignados a cada miembro.
          </p>
        </div>
      </div>

      <div className="row" style={{ marginTop: "30px", marginBottom: "20px" }}>
        <div className="col-md-8">
          <div className="form-group">
            <label htmlFor="searchPersonnel" className="control-label">
              Buscar Personal Operativo
            </label>
            <div className="input-group">
              <span className="input-group-addon">
                <span className="glyphicon glyphicon-search"></span>
              </span>
              <input
                id="searchPersonnel"
                type="text"
                className="form-control"
                placeholder="Buscar por nombre, puesto o correo..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
          </div>
        </div>
      </div>

      {filteredPersonnel.length === 0 ? (
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
              <h3 style={{ marginTop: 0 }}>No se encontró personal operativo</h3>
              <p>No hay registros que coincidan con la búsqueda.</p>
            </div>
          </div>
        </div>
      ) : (
        <div className="row">
          <div className="col-md-12">
            {/* Vista para escritorio y tablet */}
            <div className="table-responsive hidden-xs">
              <table className="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Puesto</th>
                    <th>Departamento</th>
                    <th>Dispositivos</th>
                    <th style={{ width: "120px", textAlign: "center" }}>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredPersonnel.map((person) => (
                    <tr key={person.id}>
                      <td>
                        <strong>{person.id}</strong>
                      </td>
                      <td>{person.name}</td>
                      <td>{person.position}</td>
                      <td>
                        <span
                          className="glyphicon glyphicon-briefcase"
                          style={{ marginRight: "8px", color: "#888" }}
                        ></span>
                        {person.department}
                      </td>
                      <td>
                        <span className="badge" style={{ backgroundColor: "#4D92DF" }}>
                          {person.deviceCount}
                        </span>
                      </td>
                      <td style={{ textAlign: "center" }}>
                        <button
                          type="button"
                          className="btn btn-primary btn-sm"
                          onClick={() => setSelectedPersonnel(person)}
                        >
                          <span className="glyphicon glyphicon-eye-open"></span> Ver
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Vista para móvil */}
            <div className="visible-xs">
              {filteredPersonnel.map((person) => (
                <div
                  key={person.id}
                  className="panel panel-default"
                  style={{ marginBottom: "15px" }}
                >
                  <div className="panel-body">
                    <h4 style={{ marginTop: 0, marginBottom: "10px" }}>
                      <strong>{person.name}</strong>
                      <span
                        className="badge pull-right"
                        style={{ backgroundColor: "#4D92DF" }}
                      >
                        {person.deviceCount} dispositivos
                      </span>
                    </h4>
                    <p style={{ marginBottom: "5px" }}>
                      <strong>ID:</strong> {person.id}
                    </p>
                    <p style={{ marginBottom: "5px" }}>
                      <strong>Puesto:</strong> {person.position}
                    </p>
                    <p style={{ marginBottom: "10px", color: "#666" }}>
                      <span
                        className="glyphicon glyphicon-briefcase"
                        style={{ marginRight: "8px" }}
                      ></span>
                      {person.department}
                    </p>
                    <button
                      type="button"
                      className="btn btn-primary btn-block btn-sm"
                      onClick={() => setSelectedPersonnel(person)}
                    >
                      <span className="glyphicon glyphicon-eye-open"></span> Ver Detalles
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {selectedPersonnel && (
        <>
          <div className="modal fade in" style={{ display: "block" }} role="dialog">
            <div className="modal-dialog modal-lg">
              <div className="modal-content">
                <div className="modal-header">
                  <button
                    type="button"
                    className="close"
                    onClick={() => setSelectedPersonnel(null)}
                  >
                    <span>&times;</span>
                  </button>
                  <h4 className="modal-title">
                    <span className="glyphicon glyphicon-user"></span> Detalles del Personal
                    Operativo
                  </h4>
                </div>
                <div className="modal-body">
                  <div className="row">
                    <div className="col-md-6">
                      <div style={{ marginBottom: "15px" }}>
                        <label
                          style={{
                            display: "block",
                            marginBottom: "5px",
                            fontWeight: "bold",
                          }}
                        >
                          ID:
                        </label>
                        <p style={{ margin: 0 }}>{selectedPersonnel.id}</p>
                      </div>
                      <div style={{ marginBottom: "15px" }}>
                        <label
                          style={{
                            display: "block",
                            marginBottom: "5px",
                            fontWeight: "bold",
                          }}
                        >
                          Nombre:
                        </label>
                        <p style={{ margin: 0 }}>{selectedPersonnel.name}</p>
                      </div>
                      <div style={{ marginBottom: "15px" }}>
                        <label
                          style={{
                            display: "block",
                            marginBottom: "5px",
                            fontWeight: "bold",
                          }}
                        >
                          Puesto:
                        </label>
                        <p style={{ margin: 0 }}>{selectedPersonnel.position}</p>
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div style={{ marginBottom: "15px" }}>
                        <label
                          style={{
                            display: "block",
                            marginBottom: "5px",
                            fontWeight: "bold",
                          }}
                        >
                          Departamento:
                        </label>
                        <p style={{ margin: 0 }}>{selectedPersonnel.department}</p>
                      </div>
                      <div style={{ marginBottom: "15px" }}>
                        <label
                          style={{
                            display: "block",
                            marginBottom: "5px",
                            fontWeight: "bold",
                          }}
                        >
                          Correo:
                        </label>
                        <p style={{ margin: 0 }}>
                          <a href={`mailto:${selectedPersonnel.email}`}>
                            {selectedPersonnel.email}
                          </a>
                        </p>
                      </div>
                      <div style={{ marginBottom: "15px" }}>
                        <label
                          style={{
                            display: "block",
                            marginBottom: "5px",
                            fontWeight: "bold",
                          }}
                        >
                          Dispositivos Asignados:
                        </label>
                        <p style={{ margin: 0 }}>
                          <span
                            className="badge"
                            style={{ backgroundColor: "#4D92DF", fontSize: "14px" }}
                          >
                            {selectedPersonnel.deviceCount}
                          </span>
                        </p>
                      </div>
                    </div>
                  </div>

                  <hr />

                  <div className="alert alert-info" style={{ marginBottom: 0 }}>
                    <p style={{ margin: 0 }}>
                      <span
                        className="glyphicon glyphicon-info-sign"
                        style={{ marginRight: "8px" }}
                      ></span>
                      Para ver los dispositivos específicos asignados a este personal, diríjase al
                      módulo de <strong>Dispositivos</strong>.
                    </p>
                  </div>
                </div>
                <div className="modal-footer">
                  <button
                    type="button"
                    className="btn btn-default"
                    onClick={() => setSelectedPersonnel(null)}
                  >
                    Cerrar
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div
            className="modal-backdrop fade in"
            onClick={() => setSelectedPersonnel(null)}
          ></div>
        </>
      )}
    </>
  );
}
