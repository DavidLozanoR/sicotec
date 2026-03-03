import { useState } from "react";
import { useNavigate } from "react-router";

export default function LoginPage() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const navigate = useNavigate();

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Bare bones - solo redirigir al dashboard
    navigate("/dashboard");
  };

  return (
    <div style={{ minHeight: "100vh", backgroundColor: "#f5f5f5", paddingTop: "100px" }}>
      <div className="container">
        <div className="row">
          <div className="col-md-4 col-md-offset-4">
            <div className="panel panel-default">
              <div className="panel-heading" style={{ backgroundColor: "#9D2449", color: "white" }}>
                <h3 className="panel-title" style={{ fontSize: "18px", textAlign: "center" }}>
                  <span className="glyphicon glyphicon-lock"></span> Sistema INEA
                </h3>
              </div>
              <div className="panel-body" style={{ padding: "30px" }}>
                <div style={{ textAlign: "center", marginBottom: "30px" }}>
                  <h2 style={{ marginTop: 0, color: "#333" }}>Iniciar Sesión</h2>
                  <p style={{ color: "#666" }}>Ingrese sus credenciales para acceder</p>
                </div>

                <form onSubmit={handleSubmit}>
                  <div className="form-group">
                    <label htmlFor="username" className="control-label">
                      Usuario
                    </label>
                    <div className="input-group">
                      <span className="input-group-addon">
                        <span className="glyphicon glyphicon-user"></span>
                      </span>
                      <input
                        type="text"
                        className="form-control"
                        id="username"
                        placeholder="Ingrese su usuario"
                        value={username}
                        onChange={(e) => setUsername(e.target.value)}
                        required
                      />
                    </div>
                  </div>

                  <div className="form-group">
                    <label htmlFor="password" className="control-label">
                      Contraseña
                    </label>
                    <div className="input-group">
                      <span className="input-group-addon">
                        <span className="glyphicon glyphicon-lock"></span>
                      </span>
                      <input
                        type="password"
                        className="form-control"
                        id="password"
                        placeholder="Ingrese su contraseña"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        required
                      />
                    </div>
                  </div>

                  <button
                    type="submit"
                    className="btn btn-primary btn-block btn-lg"
                    style={{ marginTop: "30px" }}
                  >
                    Ingresar al Sistema
                  </button>
                </form>
              </div>
            </div>

            <div style={{ textAlign: "center", marginTop: "20px" }}>
              <p style={{ color: "#666", fontSize: "14px" }}>
                Instituto Nacional para la Educación de los Adultos
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
