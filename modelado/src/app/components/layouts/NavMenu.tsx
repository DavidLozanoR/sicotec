import { Link, useLocation } from "react-router";
import { useAuth } from "../../context/AuthContext";
import {
  Users,
  Settings,
  Search,
  Shield,
  ChevronDown,
  LayoutDashboard,
} from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "../ui/dropdown-menu";
import { Button } from "../ui/button";

interface NavMenuProps {
  isMobile?: boolean;
}

export function NavMenu({ isMobile = false }: NavMenuProps) {
  const location = useLocation();
  const { user } = useAuth();

  const isActive = (path: string) => {
    return location.pathname === path || location.pathname.startsWith(path + "/");
  };

  const linkClass = (path: string) => {
    const base = isMobile
      ? "flex items-center gap-2 px-4 py-2 rounded-md transition-colors"
      : "flex items-center gap-2 px-3 py-2 rounded-md text-sm transition-colors";
    
    return isActive(path)
      ? `${base} bg-primary text-white`
      : `${base} text-foreground hover:bg-muted`;
  };

  if (isMobile) {
    return (
      <nav className="flex flex-col gap-1">
        <Link to="/" className={linkClass("/")}>
          <LayoutDashboard className="w-4 h-4" />
          <span>Dashboard</span>
        </Link>

        {user?.permisos.gestionPersonal && (
          <Link to="/personal-operativo" className={linkClass("/personal-operativo")}>
            <Users className="w-4 h-4" />
            <span>Gestión de Personal</span>
          </Link>
        )}

        {user?.permisos.configuracionGlobal && (
          <>
            <Link to="/configuracion-global" className={linkClass("/configuracion-global")}>
              <Settings className="w-4 h-4" />
              <span>Configuración Global</span>
            </Link>
            <Link to="/configuracion-global/servicios" className="ml-6 text-sm py-2 px-4 hover:bg-muted rounded-md">
              Servicios
            </Link>
            <Link to="/configuracion-global/red" className="ml-6 text-sm py-2 px-4 hover:bg-muted rounded-md">
              Red
            </Link>
            <Link to="/configuracion-global/responsiva" className="ml-6 text-sm py-2 px-4 hover:bg-muted rounded-md">
              Responsiva
            </Link>
          </>
        )}

        {user?.permisos.consultas && (
          <Link to="/consultas" className={linkClass("/consultas")}>
            <Search className="w-4 h-4" />
            <span>Consultas y Auditoría</span>
          </Link>
        )}

        {user?.permisos.usuarios && (
          <Link to="/usuarios" className={linkClass("/usuarios")}>
            <Shield className="w-4 h-4" />
            <span>Usuarios</span>
          </Link>
        )}
      </nav>
    );
  }

  return (
    <>
      <Link to="/" className={linkClass("/")}>
        <LayoutDashboard className="w-4 h-4" />
        <span>Dashboard</span>
      </Link>

      {user?.permisos.gestionPersonal && (
        <Link to="/personal-operativo" className={linkClass("/personal-operativo")}>
          <Users className="w-4 h-4" />
          <span>Gestión de Personal</span>
        </Link>
      )}

      {user?.permisos.configuracionGlobal && (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button
              variant="ghost"
              className={
                isActive("/configuracion-global")
                  ? "flex items-center gap-1 px-3 py-2 rounded-md text-sm bg-primary text-white hover:bg-primary/90 hover:text-white"
                  : "flex items-center gap-1 px-3 py-2 rounded-md text-sm text-foreground hover:bg-muted"
              }
            >
              <Settings className="w-4 h-4" />
              <span>Configuración</span>
              <ChevronDown className="w-3 h-3" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="start">
            <DropdownMenuItem asChild>
              <Link to="/configuracion-global" className="w-full cursor-pointer">
                Vista General
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link to="/configuracion-global/servicios" className="w-full cursor-pointer">
                Catálogo de Servicios
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link to="/configuracion-global/red" className="w-full cursor-pointer">
                Infraestructura de Red
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link to="/configuracion-global/responsiva" className="w-full cursor-pointer">
                Responsiva de Equipos
              </Link>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      )}

      {user?.permisos.consultas && (
        <Link to="/consultas" className={linkClass("/consultas")}>
          <Search className="w-4 h-4" />
          <span>Consultas</span>
        </Link>
      )}

      {user?.permisos.usuarios && (
        <Link to="/usuarios" className={linkClass("/usuarios")}>
          <Shield className="w-4 h-4" />
          <span>Usuarios</span>
        </Link>
      )}
    </>
  );
}
