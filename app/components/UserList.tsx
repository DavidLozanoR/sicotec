import { User } from "../App";

interface UserListProps {
  users: User[];
  onView: (user: User) => void;
  searchQuery: string;
  selectedDepartment: string;
}

export function UserList({ users, onView, searchQuery, selectedDepartment }: UserListProps) {
  if (users.length === 0) {
    return (
      <div className="row">
        <div className="col-md-12">
          <div className="alert alert-info" style={{ textAlign: "center", padding: "40px" }}>
            <span className="glyphicon glyphicon-info-sign" style={{ fontSize: "48px", display: "block", marginBottom: "20px" }}></span>
            <h3 style={{ marginTop: 0 }}>No se encontraron usuarios</h3>
            <p>
              {searchQuery || selectedDepartment !== "Todos"
                ? "No hay usuarios que coincidan con los filtros seleccionados"
                : "No hay usuarios registrados en el sistema"}
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="row">
      <div className="col-md-12">
        {/* Tabla para escritorio y tablet */}
        <div className="table-responsive hidden-xs">
          <table className="table table-striped table-bordered">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Departamento</th>
                <th style={{ width: "120px", textAlign: "center" }}>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {users.map((user) => (
                <tr key={user.id}>
                  <td>
                    <strong>{user.name}</strong>
                  </td>
                  <td>
                    <span className="glyphicon glyphicon-briefcase" style={{ marginRight: "8px", color: "#888" }}></span>
                    {user.department}
                  </td>
                  <td style={{ textAlign: "center" }}>
                    <button
                      type="button"
                      className="btn btn-primary btn-sm"
                      onClick={() => onView(user)}
                      title="Ver detalles"
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
          {users.map((user) => (
            <div
              key={user.id}
              className="panel panel-default"
              style={{ marginBottom: "15px" }}
            >
              <div className="panel-body">
                <h4 style={{ marginTop: 0, marginBottom: "10px" }}>
                  <strong>{user.name}</strong>
                </h4>
                <p style={{ marginBottom: "10px", color: "#666" }}>
                  <span className="glyphicon glyphicon-briefcase" style={{ marginRight: "8px" }}></span>
                  {user.department}
                </p>
                <button
                  type="button"
                  className="btn btn-primary btn-block btn-sm"
                  onClick={() => onView(user)}
                >
                  <span className="glyphicon glyphicon-eye-open"></span> Ver Detalles
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
