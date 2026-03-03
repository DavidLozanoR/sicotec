import { useState } from "react";
import { useNavigate } from "react-router";
import { UserList } from "../components/UserList";
import { UserDialog } from "../components/UserDialog";
import { CreateUserModal } from "../components/CreateUserModal";
import { TerminologyModal } from "../components/TerminologyModal";

export interface User {
  id: string;
  name: string;
  department: string;
  email: string;
  role: "Jefe" | "Usuario";
  createdBy?: string;
  createdAt?: string;
}

const DEPARTMENTS = [
  "Todos",
  "Administración",
  "Recursos Humanos",
  "Tecnología",
  "Operaciones",
  "Finanzas",
  "Marketing",
];

export default function UsersPage() {
  const navigate = useNavigate();
  const [currentUser] = useState<User>({
    id: "admin1",
    name: "Administrador Principal",
    email: "admin@inea.gob.mx",
    department: "Administración",
    role: "Jefe",
  });

  const [users, setUsers] = useState<User[]>([
    {
      id: "1",
      name: "María García López",
      department: "Administración",
      email: "maria.garcia@inea.gob.mx",
      role: "Jefe",
      createdAt: "2024-01-15",
    },
    {
      id: "2",
      name: "Juan Carlos Hernández",
      department: "Recursos Humanos",
      email: "juan.hernandez@inea.gob.mx",
      role: "Usuario",
      createdAt: "2024-02-10",
    },
    {
      id: "3",
      name: "Ana Martínez Pérez",
      department: "Tecnología",
      email: "ana.martinez@inea.gob.mx",
      role: "Usuario",
      createdAt: "2024-03-05",
    },
    {
      id: "4",
      name: "Roberto Sánchez Díaz",
      department: "Operaciones",
      email: "roberto.sanchez@inea.gob.mx",
      role: "Usuario",
      createdAt: "2024-03-20",
    },
    {
      id: "5",
      name: "Laura González Torres",
      department: "Finanzas",
      email: "laura.gonzalez@inea.gob.mx",
      role: "Usuario",
      createdAt: "2024-04-12",
    },
    {
      id: "6",
      name: "Carlos Ramírez Flores",
      department: "Marketing",
      email: "carlos.ramirez@inea.gob.mx",
      role: "Usuario",
      createdAt: "2024-05-08",
    },
  ]);

  const [viewDialogOpen, setViewDialogOpen] = useState(false);
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [terminologyOpen, setTerminologyOpen] = useState(false);
  const [viewingUser, setViewingUser] = useState<User | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedDepartment, setSelectedDepartment] = useState("Todos");

  const handleViewUser = (user: User) => {
    setViewingUser(user);
    setViewDialogOpen(true);
  };

  const handleCreateUser = (userData: Omit<User, "id" | "createdAt" | "createdBy">) => {
    const newUser: User = {
      ...userData,
      id: Date.now().toString(),
      createdAt: new Date().toISOString().split("T")[0],
      createdBy: currentUser.id,
    };
    setUsers([...users, newUser]);
    setCreateModalOpen(false);
  };

  const filteredUsers = users.filter((user) => {
    const query = searchQuery.toLowerCase();
    const matchesSearch =
      user.name.toLowerCase().includes(query) || user.email.toLowerCase().includes(query);
    const matchesDepartment =
      selectedDepartment === "Todos" || user.department === selectedDepartment;
    return matchesSearch && matchesDepartment;
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
              <li className="active">
                <a href="#" onClick={() => navigate("/usuarios")}>
                  <span className="glyphicon glyphicon-user"></span> Usuarios
                </a>
              </li>
              <li>
                <a href="#" onClick={() => navigate("/dispositivos")}>
                  <span className="glyphicon glyphicon-hdd"></span> Dispositivos
                </a>
              </li>
              <li>
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
            <li className="active">Usuarios</li>
          </ol>

          <div className="row">
            <div className="col-md-8 col-sm-8 col-xs-12">
              <h1>Usuarios Inea</h1>
            </div>
            {currentUser.role === "Jefe" && (
              <div className="col-md-4 col-sm-4 col-xs-12 text-right">
                <button
                  type="button"
                  className="btn btn-primary"
                  onClick={() => setCreateModalOpen(true)}
                  style={{ marginTop: "20px" }}
                >
                  <span className="glyphicon glyphicon-plus"></span> Crear Usuario
                </button>
              </div>
            )}
          </div>
          <hr className="red" />

          <div className="row">
            <div className="col-md-12">
              <p>
                Consulte y administre los usuarios del Instituto Nacional para la Educación de los
                Adultos.
              </p>
            </div>
          </div>

          <div className="row" style={{ marginTop: "30px", marginBottom: "20px" }}>
            <div className="col-sm-6 col-md-4">
              <div className="form-group">
                <label htmlFor="departmentFilter" className="control-label">
                  Filtrar por Departamento
                </label>
                <select
                  id="departmentFilter"
                  className="form-control"
                  value={selectedDepartment}
                  onChange={(e) => setSelectedDepartment(e.target.value)}
                >
                  {DEPARTMENTS.map((dept) => (
                    <option key={dept} value={dept}>
                      {dept}
                    </option>
                  ))}
                </select>
              </div>
            </div>
            <div className="col-sm-6 col-md-8">
              <div className="form-group">
                <label htmlFor="searchInput" className="control-label">
                  Buscar Usuario
                </label>
                <div className="input-group">
                  <span className="input-group-addon">
                    <span className="glyphicon glyphicon-search"></span>
                  </span>
                  <input
                    id="searchInput"
                    type="text"
                    className="form-control"
                    placeholder="Buscar por nombre o correo..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                  />
                </div>
              </div>
            </div>
          </div>

          <UserList
            users={filteredUsers}
            onView={handleViewUser}
            searchQuery={searchQuery}
            selectedDepartment={selectedDepartment}
          />

          {viewDialogOpen && viewingUser && (
            <UserDialog
              open={viewDialogOpen}
              onClose={() => setViewDialogOpen(false)}
              user={viewingUser}
            />
          )}

          {createModalOpen && (
            <CreateUserModal
              open={createModalOpen}
              onClose={() => setCreateModalOpen(false)}
              onSave={handleCreateUser}
            />
          )}

          <TerminologyModal
            open={terminologyOpen}
            onClose={() => setTerminologyOpen(false)}
            department={selectedDepartment}
          />
        </div>
      </main>
    </>
  );
}
