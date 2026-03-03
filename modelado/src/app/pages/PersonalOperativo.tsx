import { useState } from "react";
import { Link } from "react-router";
import { useData } from "../context/DataContext";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "../components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../components/ui/table";
import { Badge } from "../components/ui/badge";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "../components/ui/alert-dialog";
import { Plus, Search, Edit, Trash2, Laptop, User, ChevronLeft, ChevronRight } from "lucide-react";
import { toast } from "sonner";

export function PersonalOperativo() {
  const { personalOperativo, deletePersonalOperativo, segmentosRed } = useData();
  const [filtros, setFiltros] = useState({
    nombre: "",
    departamento: "all",
    segmento: "all",
  });
  const [paginaActual, setPaginaActual] = useState(1);
  const itemsPorPagina = 10;

  // Mapa de relación entre departamentos y segmentos
  const departamentoSegmentoMap: Record<string, string> = {
    "Tecnologías de la Información": "Segmento A - Administración",
    "Finanzas": "Segmento B - Finanzas",
    "Recursos Humanos": "Segmento C - Recursos Humanos",
  };

  // Mapa inverso de segmentos a departamentos
  const segmentoDepartamentoMap: Record<string, string> = {
    "Segmento A - Administración": "Tecnologías de la Información",
    "Segmento B - Finanzas": "Finanzas",
    "Segmento C - Recursos Humanos": "Recursos Humanos",
  };

  // Función para cambiar departamento y sincronizar segmento
  const handleDepartamentoChange = (departamento: string) => {
    const segmentoAsociado = departamento !== "all" ? departamentoSegmentoMap[departamento] || "all" : "all";
    setFiltros({ ...filtros, departamento, segmento: segmentoAsociado });
    setPaginaActual(1); // Reset a la primera página
  };

  // Función para cambiar segmento y sincronizar departamento
  const handleSegmentoChange = (segmento: string) => {
    const departamentoAsociado = segmento !== "all" ? segmentoDepartamentoMap[segmento] || "all" : "all";
    setFiltros({ ...filtros, segmento, departamento: departamentoAsociado });
    setPaginaActual(1); // Reset a la primera página
  };

  const filteredPersonal = personalOperativo.filter((p) => {
    // Filtro por nombre
    if (filtros.nombre && !p.nombre.toLowerCase().includes(filtros.nombre.toLowerCase())) {
      return false;
    }

    // Filtro por departamento
    if (filtros.departamento !== "all" && p.departamento !== filtros.departamento) {
      return false;
    }

    // Filtro por segmento
    if (filtros.segmento !== "all" && p.segmento !== filtros.segmento) {
      return false;
    }

    return true;
  });

  // Calcular paginación
  const totalPaginas = Math.ceil(filteredPersonal.length / itemsPorPagina);
  const indexInicio = (paginaActual - 1) * itemsPorPagina;
  const indexFin = indexInicio + itemsPorPagina;
  const personalPaginado = filteredPersonal.slice(indexInicio, indexFin);

  const departamentosUnicos = [...new Set(personalOperativo.map((p) => p.departamento))];

  const limpiarFiltros = () => {
    setFiltros({
      nombre: "",
      departamento: "all",
      segmento: "all",
    });
    setPaginaActual(1);
  };

  const handleDelete = (id: string, nombre: string, equiposCount: number) => {
    // RN-01: Validar que no tenga equipos asignados antes de eliminar
    if (equiposCount > 0) {
      toast.error(
        "No se puede dar de baja al personal con dispositivos asignados. Debe transferir o liberar los equipos primero.",
        { duration: 5000 }
      );
      return;
    }
    
    try {
      deletePersonalOperativo(id);
      toast.success(`Personal ${nombre} eliminado correctamente`);
    } catch (error) {
      toast.error(
        error instanceof Error ? error.message : "Error al eliminar el personal"
      );
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Gestión de Personal Operativo</h1>
          <p className="text-muted-foreground mt-1">
            Administración integral del directorio de trabajadores y sus equipos
          </p>
        </div>
        <Link to="/personal-operativo/nuevo">
          <Button className="flex items-center gap-2">
            <Plus className="w-4 h-4" />
            Nuevo Personal
          </Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Directorio de Personal Operativo</CardTitle>
          <CardDescription>
            {filteredPersonal.length} de {personalOperativo.length} registros
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Búsqueda por nombre */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <Input
              placeholder="Buscar por nombre..."
              value={filtros.nombre}
              onChange={(e) => setFiltros({ ...filtros, nombre: e.target.value })}
              className="pl-10 bg-input-background"
            />
          </div>

          {/* Filtros - Responsive */}
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            {/* Contenedor de filtros de selección */}
            <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
              {/* Filtro de Departamento */}
              <div className="flex flex-col sm:flex-row sm:items-center gap-2 flex-1 sm:flex-initial">
                <Label className="text-sm whitespace-nowrap">Departamento:</Label>
                <Select
                  value={filtros.departamento}
                  onValueChange={handleDepartamentoChange}
                >
                  <SelectTrigger className="w-full sm:w-52">
                    <SelectValue>
                      {filtros.departamento === "all"
                        ? "Todos"
                        : filtros.departamento}
                    </SelectValue>
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Todos</SelectItem>
                    {departamentosUnicos.map((depto) => (
                      <SelectItem key={depto} value={depto}>
                        {depto}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {/* Filtro de Segmento */}
              <div className="flex flex-col sm:flex-row sm:items-center gap-2 flex-1 sm:flex-initial">
                <Label className="text-sm whitespace-nowrap">Segmento:</Label>
                <Select
                  value={filtros.segmento}
                  onValueChange={handleSegmentoChange}
                >
                  <SelectTrigger className="w-full sm:w-52">
                    <SelectValue>
                      {filtros.segmento === "all"
                        ? "Todos"
                        : filtros.segmento}
                    </SelectValue>
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Todos</SelectItem>
                    {segmentosRed.map((segmento) => (
                      <SelectItem key={segmento.id} value={segmento.nombre}>
                        {segmento.nombre}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            {/* Botón Limpiar Filtros */}
            <Button
              variant="outline"
              size="sm"
              onClick={limpiarFiltros}
              className="w-full sm:w-auto"
            >
              Limpiar Filtros
            </Button>
          </div>

          {/* Tabla con scroll horizontal en móviles */}
          <div className="border rounded-lg overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="hidden sm:table-cell">ID</TableHead>
                  <TableHead>No. Cobro</TableHead>
                  <TableHead>Nombre</TableHead>
                  <TableHead className="hidden md:table-cell">Departamento</TableHead>
                  <TableHead className="hidden lg:table-cell">Puesto</TableHead>
                  <TableHead>Equipos</TableHead>
                  <TableHead className="text-right">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {personalPaginado.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                      No se encontraron registros
                    </TableCell>
                  </TableRow>
                ) : (
                  personalPaginado.map((personal) => (
                    <TableRow key={personal.id}>
                      <TableCell className="hidden sm:table-cell font-mono text-xs text-muted-foreground">
                        {personal.id}
                      </TableCell>
                      <TableCell className="font-medium whitespace-nowrap">{personal.numeroCobro}</TableCell>
                      <TableCell className="min-w-[200px]">
                        <div className="flex flex-col gap-1">
                          <div className="flex items-center gap-2">
                            <User className="w-4 h-4 text-muted-foreground flex-shrink-0" />
                            <span className="font-medium">{personal.nombre}</span>
                          </div>
                          {/* Mostrar departamento en móviles */}
                          <span className="text-xs text-muted-foreground md:hidden">
                            {personal.departamento}
                          </span>
                          {/* Mostrar puesto en móviles y tablets */}
                          <span className="text-xs text-muted-foreground lg:hidden">
                            {personal.puesto}
                          </span>
                        </div>
                      </TableCell>
                      <TableCell className="hidden md:table-cell">{personal.departamento}</TableCell>
                      <TableCell className="hidden lg:table-cell">{personal.puesto}</TableCell>
                      <TableCell>
                        <Badge variant="secondary" className="flex items-center gap-1 w-fit">
                          <Laptop className="w-3 h-3" />
                          {personal.equiposAsignados?.length || 0}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Link to={`/personal-operativo/editar/${personal.id}`}>
                            <Button variant="ghost" size="icon" className="h-8 w-8">
                              <Edit className="w-4 h-4" />
                            </Button>
                          </Link>
                          <AlertDialog>
                            <AlertDialogTrigger asChild>
                              <Button variant="ghost" size="icon" className="h-8 w-8">
                                <Trash2 className="w-4 h-4 text-destructive" />
                              </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                              <AlertDialogHeader>
                                <AlertDialogTitle>¿Eliminar personal?</AlertDialogTitle>
                                <AlertDialogDescription>
                                  {personal.equiposAsignados && personal.equiposAsignados.length > 0 ? (
                                    <span className="text-destructive font-semibold">
                                      Este personal tiene {personal.equiposAsignados.length} dispositivo(s) asignado(s). 
                                      Según la regla de negocio RN-01, debe liberar o transferir todos los equipos 
                                      antes de proceder con la baja del personal.
                                    </span>
                                  ) : (
                                    <>
                                      Esta acción eliminará permanentemente el registro de{" "}
                                      <strong>{personal.nombre}</strong>. Esta acción no se puede deshacer.
                                    </>
                                  )}
                                </AlertDialogDescription>
                              </AlertDialogHeader>
                              <AlertDialogFooter>
                                <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                <AlertDialogAction
                                  onClick={() => handleDelete(
                                    personal.id, 
                                    personal.nombre, 
                                    personal.equiposAsignados?.length || 0
                                  )}
                                  className="bg-destructive hover:bg-destructive/90"
                                  disabled={personal.equiposAsignados && personal.equiposAsignados.length > 0}
                                >
                                  Eliminar
                                </AlertDialogAction>
                              </AlertDialogFooter>
                            </AlertDialogContent>
                          </AlertDialog>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>

          {/* Paginación - Responsive */}
          <div className="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4">
            <Button
              variant="outline"
              size="sm"
              onClick={() => setPaginaActual(paginaActual - 1)}
              disabled={paginaActual === 1}
              className="w-full sm:w-auto"
            >
              <ChevronLeft className="w-4 h-4 mr-1" />
              Anterior
            </Button>
            <span className="text-sm text-muted-foreground order-first sm:order-none">
              Página {paginaActual} de {totalPaginas || 1}
            </span>
            <Button
              variant="outline"
              size="sm"
              onClick={() => setPaginaActual(paginaActual + 1)}
              disabled={paginaActual === totalPaginas || totalPaginas === 0}
              className="w-full sm:w-auto"
            >
              Siguiente
              <ChevronRight className="w-4 h-4 ml-1" />
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}