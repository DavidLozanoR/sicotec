import { Link } from "react-router";
import { useAuth } from "../context/AuthContext";
import { useData } from "../context/DataContext";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import {
  Users,
  Settings,
  Search,
  Shield,
  Laptop,
  Server,
  Activity,
  TrendingUp,
} from "lucide-react";

export function Dashboard() {
  const { user } = useAuth();
  const { personalOperativo, servicios, segmentosRed } = useData();

  const totalEquipos = personalOperativo.reduce((acc, p) => acc + (p.equiposAsignados?.length || 0), 0);
  const totalIpsAsignadas = segmentosRed.reduce((acc, s) => acc + s.ipsAsignadas.length, 0);

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-foreground">Dashboard</h1>
        <p className="text-muted-foreground mt-1">
          Bienvenido, {user?.nombre}
        </p>
      </div>

      {/* Estadísticas */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Personal Operativo</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{personalOperativo.length}</div>
            <p className="text-xs text-muted-foreground">Empleados registrados</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Equipos Asignados</CardTitle>
            <Laptop className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalEquipos}</div>
            <p className="text-xs text-muted-foreground">Dispositivos activos</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Servicios Disponibles</CardTitle>
            <Server className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{servicios.length}</div>
            <p className="text-xs text-muted-foreground">Software autorizado</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Segmentos de Red</CardTitle>
            <Activity className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{segmentosRed.length}</div>
            <p className="text-xs text-muted-foreground">{totalIpsAsignadas} IPs asignadas</p>
          </CardContent>
        </Card>
      </div>

      {/* Accesos Rápidos */}
      <div>
        <h2 className="text-xl font-semibold mb-4">Accesos Rápidos</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {user?.permisos.gestionPersonal && (
            <Card className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <Users className="h-8 w-8 text-primary mb-2" />
                <CardTitle>Gestión de Personal Operativo</CardTitle>
                <CardDescription>
                  Administrar el directorio de trabajadores y asignar equipos
                </CardDescription>
              </CardHeader>
              <CardContent>
                <Link to="/personal-operativo">
                  <Button className="w-full">Acceder al Módulo</Button>
                </Link>
              </CardContent>
            </Card>
          )}

          {user?.permisos.configuracionGlobal && (
            <Card className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <Settings className="h-8 w-8 text-primary mb-2" />
                <CardTitle>Configuración Global</CardTitle>
                <CardDescription>
                  Gestionar servicios, red y responsiva de equipos
                </CardDescription>
              </CardHeader>
              <CardContent>
                <Link to="/configuracion-global">
                  <Button className="w-full">Acceder al Módulo</Button>
                </Link>
              </CardContent>
            </Card>
          )}

          {user?.permisos.consultas && (
            <Card className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <Search className="h-8 w-8 text-primary mb-2" />
                <CardTitle>Consultas y Auditoría</CardTitle>
                <CardDescription>
                  Realizar búsquedas avanzadas y generar reportes
                </CardDescription>
              </CardHeader>
              <CardContent>
                <Link to="/consultas">
                  <Button className="w-full">Acceder al Módulo</Button>
                </Link>
              </CardContent>
            </Card>
          )}

          {user?.permisos.usuarios && (
            <Card className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <Shield className="h-8 w-8 text-primary mb-2" />
                <CardTitle>Administración de Usuarios</CardTitle>
                <CardDescription>
                  Gestionar cuentas y permisos del sistema
                </CardDescription>
              </CardHeader>
              <CardContent>
                <Link to="/usuarios">
                  <Button className="w-full">Acceder al Módulo</Button>
                </Link>
              </CardContent>
            </Card>
          )}
        </div>
      </div>

      {/* Actividad Reciente */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <TrendingUp className="h-5 w-5" />
            Resumen del Sistema
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="flex items-center justify-between py-2 border-b">
              <span className="text-sm">Total de Personal Operativo</span>
              <span className="font-semibold">{personalOperativo.length}</span>
            </div>
            <div className="flex items-center justify-between py-2 border-b">
              <span className="text-sm">Equipos Asignados</span>
              <span className="font-semibold">{totalEquipos}</span>
            </div>
            <div className="flex items-center justify-between py-2 border-b">
              <span className="text-sm">Servicios Configurados</span>
              <span className="font-semibold">{servicios.length}</span>
            </div>
            <div className="flex items-center justify-between py-2">
              <span className="text-sm">Segmentos de Red Activos</span>
              <span className="font-semibold">{segmentosRed.length}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}