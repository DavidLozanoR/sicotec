import { User } from "../App";

interface UserDialogProps {
  open: boolean;
  onClose: () => void;
  user: User;
}

export function UserDialog({ open, onClose, user }: UserDialogProps) {
  if (!open) return null;

  return (
    <>
      <div className="modal fade in" style={{ display: "block" }} role="dialog">
        <div className="modal-dialog">
          <div className="modal-content">
            <div className="modal-header">
              <button type="button" className="close" onClick={onClose}>
                <span>&times;</span>
              </button>
              <h4 className="modal-title">
                <span className="glyphicon glyphicon-user"></span> Información del Usuario
              </h4>
            </div>
            <div className="modal-body">
              <div className="row">
                <div className="col-xs-12">
                  <div style={{ marginBottom: "20px" }}>
                    <label style={{ display: "block", marginBottom: "5px", fontWeight: "bold", color: "#545454" }}>
                      Nombre Completo:
                    </label>
                    <p style={{ fontSize: "16px", margin: 0 }}>{user.name}</p>
                  </div>

                  <div style={{ marginBottom: "20px" }}>
                    <label style={{ display: "block", marginBottom: "5px", fontWeight: "bold", color: "#545454" }}>
                      Departamento:
                    </label>
                    <p style={{ fontSize: "16px", margin: 0 }}>
                      <span className="glyphicon glyphicon-briefcase" style={{ marginRight: "8px", color: "#888" }}></span>
                      {user.department}
                    </p>
                  </div>

                  <div style={{ marginBottom: "20px" }}>
                    <label style={{ display: "block", marginBottom: "5px", fontWeight: "bold", color: "#545454" }}>
                      Correo Electrónico:
                    </label>
                    <p style={{ fontSize: "16px", margin: 0 }}>
                      <span className="glyphicon glyphicon-envelope" style={{ marginRight: "8px", color: "#888" }}></span>
                      <a href={`mailto:${user.email}`} style={{ color: "#4D92DF" }}>
                        {user.email}
                      </a>
                    </p>
                  </div>

                  <div style={{ marginBottom: "10px" }}>
                    <label style={{ display: "block", marginBottom: "5px", fontWeight: "bold", color: "#545454" }}>
                      ID de Usuario:
                    </label>
                    <p style={{ fontSize: "16px", margin: 0, color: "#888" }}>{user.id}</p>
                  </div>
                </div>
              </div>
            </div>
            <div className="modal-footer">
              <button type="button" className="btn btn-default" onClick={onClose}>
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
      <div className="modal-backdrop fade in" onClick={onClose}></div>
    </>
  );
}
