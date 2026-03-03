import { useState } from "react";

const DEPARTMENTS = [
  "Administración",
  "Recursos Humanos",
  "Tecnología",
  "Operaciones",
  "Finanzas",
  "Marketing",
];

interface CreateUserModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (user: {
    name: string;
    email: string;
    department: string;
    role: "Jefe" | "Usuario";
  }) => void;
}

export function CreateUserModal({ open, onClose, onSave }: CreateUserModalProps) {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    department: DEPARTMENTS[0],
    role: "Usuario" as "Jefe" | "Usuario",
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave(formData);
    setFormData({
      name: "",
      email: "",
      department: DEPARTMENTS[0],
      role: "Usuario",
    });
  };

  if (!open) return null;

  return (
    <>
      <div className="modal fade in" style={{ display: "block" }} role="dialog">
        <div className="modal-dialog">
          <div className="modal-content">
            <form onSubmit={handleSubmit}>
              <div className="modal-header">
                <button type="button" className="close" onClick={onClose}>
                  <span>&times;</span>
                </button>
                <h4 className="modal-title">
                  <span className="glyphicon glyphicon-plus"></span> Crear Nuevo Usuario
                </h4>
              </div>
              <div className="modal-body">
                <p style={{ marginBottom: "20px", color: "#666" }}>
                  Complete el formulario para dar de alta un nuevo usuario en el sistema.
                </p>

                <div className="form-group">
                  <label className="control-label" htmlFor="userName">
                    Nombre Completo <span style={{ color: "#D0021B" }}>*</span>
                  </label>
                  <input
                    type="text"
                    className="form-control"
                    id="userName"
                    value={formData.name}
                    onChange={(e) =>
                      setFormData({ ...formData, name: e.target.value })
                    }
                    placeholder="Ingrese el nombre completo"
                    required
                  />
                </div>

                <div className="form-group">
                  <label className="control-label" htmlFor="userEmail">
                    Correo Electrónico <span style={{ color: "#D0021B" }}>*</span>
                  </label>
                  <input
                    type="email"
                    className="form-control"
                    id="userEmail"
                    value={formData.email}
                    onChange={(e) =>
                      setFormData({ ...formData, email: e.target.value })
                    }
                    placeholder="usuario@inea.gob.mx"
                    required
                  />
                </div>

                <div className="form-group">
                  <label className="control-label" htmlFor="userDepartment">
                    Departamento <span style={{ color: "#D0021B" }}>*</span>
                  </label>
                  <select
                    className="form-control"
                    id="userDepartment"
                    value={formData.department}
                    onChange={(e) =>
                      setFormData({ ...formData, department: e.target.value })
                    }
                    required
                  >
                    {DEPARTMENTS.map((dept) => (
                      <option key={dept} value={dept}>
                        {dept}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="form-group">
                  <label className="control-label" htmlFor="userRole">
                    Rol <span style={{ color: "#D0021B" }}>*</span>
                  </label>
                  <select
                    className="form-control"
                    id="userRole"
                    value={formData.role}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        role: e.target.value as "Jefe" | "Usuario",
                      })
                    }
                    required
                  >
                    <option value="Usuario">Usuario</option>
                    <option value="Jefe">Jefe (Administrador)</option>
                  </select>
                  <p className="help-block" style={{ fontSize: "12px", marginTop: "5px" }}>
                    El rol "Jefe" tiene permisos para crear y administrar usuarios.
                  </p>
                </div>
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-default" onClick={onClose}>
                  Cancelar
                </button>
                <button type="submit" className="btn btn-primary">
                  Crear Usuario
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div className="modal-backdrop fade in" onClick={onClose}></div>
    </>
  );
}
