import { useState } from "react";
import { UserList } from "../UserList";
import { UserDialog } from "../UserDialog";
import { CreateUserModal } from "../CreateUserModal";

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

interface UsersModuleProps {
  setCurrentDepartment: (dept: string) => void;
  currentUser: User;
}

export function UsersModule({ setCurrentDepartment, currentUser }: UsersModuleProps) {
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

  const handleDepartmentChange = (dept: string) => {
    setSelectedDepartment(dept);
    setCurrentDepartment(dept);
  };

  // Filter users based on search query and department
  const filteredUsers = users.filter((user) => {
    const query = searchQuery.toLowerCase();
    const matchesSearch =
      user.name.toLowerCase().includes(query) ||
      user.email.toLowerCase().includes(query);
    const matchesDepartment =
      selectedDepartment === "Todos" || user.department === selectedDepartment;
    return matchesSearch && matchesDepartment;
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
            Consulte y administre los usuarios del Instituto Nacional para la
            Educación de los Adultos.
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
              onChange={(e) => handleDepartmentChange(e.target.value)}
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
    </>
  );
}
