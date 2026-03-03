import { Link } from "react-router";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Server, Network, FileText, ArrowRight } from "lucide-react";

export function ConfiguracionGlobal() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Configuración Global</h1>
        <p className="text-muted-foreground mt-1">
          Gestión centralizada de parámetros del sistema
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card className="hover:shadow-lg transition-shadow">
          <CardHeader>
            <Server className="h-10 w-10 text-primary mb-2" />
            <CardTitle>Catálogo de Servicios</CardTitle>
            <CardDescription>
              Administración del software autorizado, versiones y actualizaciones
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Link to="/configuracion-global/servicios">
              <Button className="w-full flex items-center justify-between">
                <span>Gestionar Servicios</span>
                <ArrowRight className="w-4 h-4" />
              </Button>
            </Link>
          </CardContent>
        </Card>

        <Card className="hover:shadow-lg transition-shadow">
          <CardHeader>
            <Network className="h-10 w-10 text-primary mb-2" />
            <CardTitle>Infraestructura de Red</CardTitle>
            <CardDescription>
              Visualización de segmentos, rangos IP y asignaciones activas
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Link to="/configuracion-global/red">
              <Button className="w-full flex items-center justify-between">
                <span>Ver Segmentos</span>
                <ArrowRight className="w-4 h-4" />
              </Button>
            </Link>
          </CardContent>
        </Card>

        <Card className="hover:shadow-lg transition-shadow">
          <CardHeader>
            <FileText className="h-10 w-10 text-primary mb-2" />
            <CardTitle>Responsiva de Equipos</CardTitle>
            <CardDescription>
              Gestión de transferencias y trazabilidad de activos
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Link to="/configuracion-global/responsiva">
              <Button className="w-full flex items-center justify-between">
                <span>Gestionar Responsivas</span>
                <ArrowRight className="w-4 h-4" />
              </Button>
            </Link>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Descripción del Módulo</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div>
            <h3 className="font-semibold mb-2">Catálogo de Servicios</h3>
            <p className="text-sm text-muted-foreground">
              Permite gestionar el catálogo centralizado de software autorizado, incluyendo sistemas
              operativos, programas, drivers y sus versiones vigentes. Esta información alimenta
              las listas desplegables durante la asignación de servicios a equipos.
            </p>
          </div>
          <div>
            <h3 className="font-semibold mb-2">Infraestructura de Red</h3>
            <p className="text-sm text-muted-foreground">
              Proporciona una visualización completa de los segmentos de red organizados por piso,
              mostrando los rangos de direcciones IP, las IPs asignadas a equipos operativos y las
              direcciones disponibles para nuevas asignaciones.
            </p>
          </div>
          <div>
            <h3 className="font-semibold mb-2">Responsiva de Equipos</h3>
            <p className="text-sm text-muted-foreground">
              Facilita la gestión de transferencias de equipos entre personal operativo,
              manteniendo la trazabilidad completa de los activos y permitiendo cambios de
              responsable de manera ágil y documentada.
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
