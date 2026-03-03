import React, { useState } from "react";
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
import { Search, Filter, FileText, Download, ChevronDown, ChevronUp } from "lucide-react";

export function Consultas() {
  const { personalOperativo, servicios, segmentosRed, equiposGlobales } = useData();
  const [filtros, setFiltros] = useState({
    departamento: "all",
    tipoEquipo: "all",
    servicio: "all",
    versionActualizada: "all", // "all", "si", "no"
    segmento: "all",
    nombrePersonal: "",
    puesto: "",
    tipoAsignacion: "all", // "all", "asignado", "responsable"
  });
  const [expandedRows, setExpandedRows] = useState<Set<string>>(new Set());

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
  };

  // Función para cambiar segmento y sincronizar departamento
  const handleSegmentoChange = (segmento: string) => {
    const departamentoAsociado = segmento !== "all" ? segmentoDepartamentoMap[segmento] || "all" : "all";
    setFiltros({ ...filtros, segmento, departamento: departamentoAsociado });
  };

  const toggleRow = (personalId: string) => {
    const newExpanded = new Set(expandedRows);
    if (newExpanded.has(personalId)) {
      newExpanded.delete(personalId);
    } else {
      newExpanded.add(personalId);
    }
    setExpandedRows(newExpanded);
  };

  const aplicarFiltros = () => {
    return personalOperativo.filter((personal) => {
      // Filtro por nombre
      if (
        filtros.nombrePersonal &&
        !personal.nombre.toLowerCase().includes(filtros.nombrePersonal.toLowerCase())
      ) {
        return false;
      }

      // Filtro por departamento
      if (
        filtros.departamento &&
        filtros.departamento !== "all" &&
        personal.departamento !== filtros.departamento
      ) {
        return false;
      }

      // Filtro por segmento
      if (filtros.segmento && filtros.segmento !== "all" && personal.segmento !== filtros.segmento) {
        return false;
      }

      // Filtro por tipo de equipo
      if (filtros.tipoEquipo && filtros.tipoEquipo !== "all") {
        const tieneEquipo = personal.equiposAsignados?.some((asignacion) => {
          const equipo = equiposGlobales.find((e) => e.id === asignacion.equipoId);
          return equipo?.tipo === filtros.tipoEquipo;
        });
        if (!tieneEquipo) return false;
      }

      // Filtro por servicio
      if (filtros.servicio && filtros.servicio !== "all") {
        const tieneServicio = personal.equiposAsignados?.some((asignacion) =>
          asignacion.servicios.some((s) => s.servicioId === filtros.servicio)
        );
        if (!tieneServicio) return false;
      }

      // Filtro por versión actualizada
      if (filtros.versionActualizada && filtros.versionActualizada !== "all") {
        if (filtros.versionActualizada === "no") {
          // Mostrar solo personal que tiene equipos con servicios desactualizados
          const tieneServiciosDesactualizados = personal.equiposAsignados?.some((asignacion) => {
            return asignacion.servicios.some((servAsig) => {
              const servicio = servicios.find((s) => s.id === servAsig.servicioId);
              return servicio && servAsig.version !== servicio.versionVigente;
            });
          });
          if (!tieneServiciosDesactualizados) return false;
        } else if (filtros.versionActualizada === "si") {
          // Mostrar solo personal que tiene todos los servicios actualizados
          const todoActualizado = personal.equiposAsignados?.every((asignacion) => {
            if (asignacion.servicios.length === 0) return true; // Sin servicios = actualizado
            return asignacion.servicios.every((servAsig) => {
              const servicio = servicios.find((s) => s.id === servAsig.servicioId);
              return servicio && servAsig.version === servicio.versionVigente;
            });
          });
          if (!todoActualizado) return false;
        }
      }

      // Filtro por puesto
      if (
        filtros.puesto &&
        !personal.puesto.toLowerCase().includes(filtros.puesto.toLowerCase())
      ) {
        return false;
      }

      // Filtro por tipo de asignación
      if (filtros.tipoAsignacion && filtros.tipoAsignacion !== "all") {
        const tieneTipoAsignacion = personal.equiposAsignados?.some((asignacion) => {
          return asignacion.tipoAsignacion === filtros.tipoAsignacion;
        });
        if (!tieneTipoAsignacion) return false;
      }

      return true;
    });
  };

  const resultados = aplicarFiltros();

  const limpiarFiltros = () => {
    setFiltros({
      departamento: "all",
      tipoEquipo: "all",
      servicio: "all",
      versionActualizada: "all",
      segmento: "all",
      nombrePersonal: "",
      puesto: "",
      tipoAsignacion: "all",
    });
  };

  const exportarResultados = () => {
    // Simulación de exportación
    const data = resultados.map((p) => ({
      NumeroCobro: p.numeroCobro,
      Nombre: p.nombre,
      Departamento: p.departamento,
      Puesto: p.puesto,
      Correo: p.correo,
      Segmento: p.segmento,
      TotalEquipos: p.equiposAsignados?.length || 0,
    }));
    
    console.log("Exportando datos:", data);
    alert(`Se exportarían ${data.length} registros. (Funcionalidad de demostración)`);
  };

  const tiposEquipo = ["Desktop", "Laptop", "Impresora", "Otro"];
  const departamentosUnicos = [...new Set(personalOperativo.map((p) => p.departamento))];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Consultas y Auditoría</h1>
        <p className="text-muted-foreground mt-1">
          Búsquedas avanzadas y reportes cruzados del sistema
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Filter className="w-5 h-5" />
            Filtros de Búsqueda
          </CardTitle>
          <CardDescription>
            Configure los criterios para realizar consultas cruzadas
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label htmlFor="nombrePersonal">Nombre del Personal</Label>
              <Input
                id="nombrePersonal"
                value={filtros.nombrePersonal}
                onChange={(e) =>
                  setFiltros({ ...filtros, nombrePersonal: e.target.value })
                }
                placeholder="Buscar por nombre..."
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="departamento">Departamento</Label>
              <Select
                value={filtros.departamento}
                onValueChange={handleDepartamentoChange}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Todos los departamentos" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos</SelectItem>
                  {departamentosUnicos.map((dept) => (
                    <SelectItem key={dept} value={dept}>
                      {dept}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="segmento">Segmento de Red</Label>
              <Select
                value={filtros.segmento}
                onValueChange={handleSegmentoChange}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Todos los segmentos" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos</SelectItem>
                  {segmentosRed.map((seg) => (
                    <SelectItem key={seg.id} value={seg.nombre}>
                      {seg.nombre}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="tipoEquipo">Tipo de Equipo</Label>
              <Select
                value={filtros.tipoEquipo}
                onValueChange={(value) => setFiltros({ ...filtros, tipoEquipo: value })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Todos los tipos" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos</SelectItem>
                  {tiposEquipo.map((tipo) => (
                    <SelectItem key={tipo} value={tipo}>
                      {tipo}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="servicio">Servicio Instalado</Label>
              <Select
                value={filtros.servicio}
                onValueChange={(value) => setFiltros({ ...filtros, servicio: value })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Todos los servicios" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos</SelectItem>
                  {servicios.map((serv) => (
                    <SelectItem key={serv.id} value={serv.id}>
                      {serv.nombre}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="versionActualizada">Versión Actualizada</Label>
              <Select
                value={filtros.versionActualizada}
                onValueChange={(value) => setFiltros({ ...filtros, versionActualizada: value })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Todos" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos</SelectItem>
                  <SelectItem value="si">Sí</SelectItem>
                  <SelectItem value="no">No</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="puesto">Puesto</Label>
              <Input
                id="puesto"
                value={filtros.puesto}
                onChange={(e) => setFiltros({ ...filtros, puesto: e.target.value })}
                placeholder="Buscar por puesto..."
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="tipoAsignacion">Tipo de Asignación</Label>
              <Select
                value={filtros.tipoAsignacion}
                onValueChange={(value) => setFiltros({ ...filtros, tipoAsignacion: value })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Todos" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos</SelectItem>
                  <SelectItem value="asignado">Asignado</SelectItem>
                  <SelectItem value="responsable">Responsable</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="flex gap-2 pt-2">
            <Button variant="outline" onClick={limpiarFiltros}>
              Limpiar Filtros
            </Button>
            <Button onClick={exportarResultados} variant="secondary">
              <Download className="w-4 h-4 mr-2" />
              Exportar Resultados
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center justify-between">
            <span className="flex items-center gap-2">
              <FileText className="w-5 h-5" />
              Resultados de la Consulta
            </span>
            <Badge variant="secondary" className="text-sm">
              {resultados.length} {resultados.length === 1 ? "registro" : "registros"}
            </Badge>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="border rounded-lg">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-10"></TableHead>
                  <TableHead>No. Cobro</TableHead>
                  <TableHead>Nombre</TableHead>
                  <TableHead>Departamento</TableHead>
                  <TableHead>Puesto</TableHead>
                  <TableHead>Segmento</TableHead>
                  <TableHead>Equipos</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {resultados.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                      <Search className="w-12 h-12 mx-auto mb-3 opacity-50" />
                      <p>No se encontraron resultados con los filtros aplicados</p>
                    </TableCell>
                  </TableRow>
                ) : (
                  <>
                    {resultados.flatMap((personal) => {
                      const isExpanded = expandedRows.has(personal.id);
                      // Calcular equipos según el filtro de tipo de asignación
                      const equiposFiltrados = personal.equiposAsignados?.filter((asignacion) => {
                        if (filtros.tipoAsignacion !== "all") {
                          return asignacion.tipoAsignacion === filtros.tipoAsignacion;
                        }
                        return true;
                      }) || [];
                      const equiposCount = equiposFiltrados.length;

                      const mainRow = (
                        <TableRow key={personal.id} className="cursor-pointer hover:bg-muted/50">
                          <TableCell onClick={() => equiposCount > 0 && toggleRow(personal.id)}>
                            {equiposCount > 0 && (
                              isExpanded ? (
                                <ChevronUp className="w-4 h-4 text-muted-foreground" />
                              ) : (
                                <ChevronDown className="w-4 h-4 text-muted-foreground" />
                              )
                            )}
                          </TableCell>
                          <TableCell className="font-medium">{personal.numeroCobro}</TableCell>
                          <TableCell>{personal.nombre}</TableCell>
                          <TableCell>{personal.departamento}</TableCell>
                          <TableCell>{personal.puesto}</TableCell>
                          <TableCell className="text-sm">{personal.segmento}</TableCell>
                          <TableCell>
                            <Badge variant="secondary">{equiposCount}</Badge>
                          </TableCell>
                        </TableRow>
                      );

                      if (isExpanded && equiposCount > 0) {
                        const expandedRow = (
                          <TableRow key={`${personal.id}-expanded`}>
                            <TableCell colSpan={7} className="bg-muted/30 p-0">
                              <div className="p-4 space-y-3">
                                <h4 className="font-semibold text-sm">Equipos Asignados:</h4>
                                {personal.equiposAsignados
                                  ?.filter((asignacion) => {
                                    // Filtrar por tipo de asignación si está seleccionado
                                    if (filtros.tipoAsignacion !== "all") {
                                      return asignacion.tipoAsignacion === filtros.tipoAsignacion;
                                    }
                                    return true;
                                  })
                                  .map((asignacion) => {
                                    const equipo = equiposGlobales.find((e) => e.id === asignacion.equipoId);
                                    if (!equipo) return null;

                                    return (
                                      <div key={asignacion.equipoId} className="border rounded-lg p-3 bg-background">
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                                          <div>
                                            <p className="text-xs text-muted-foreground">Tipo</p>
                                            <p className="font-medium">{equipo.tipo}</p>
                                          </div>
                                          <div>
                                            <p className="text-xs text-muted-foreground">Marca</p>
                                            <p className="font-medium">{equipo.marca}</p>
                                          </div>
                                          <div>
                                            <p className="text-xs text-muted-foreground">Modelo</p>
                                            <p className="font-medium">{equipo.modelo}</p>
                                          </div>
                                          <div>
                                            <p className="text-xs text-muted-foreground">No. Serie</p>
                                            <p className="font-medium">{equipo.serie}</p>
                                          </div>
                                          <div>
                                            <p className="text-xs text-muted-foreground">IP Asignada</p>
                                            <p className="font-medium">{asignacion.ip || "N/A"}</p>
                                          </div>
                                          <div>
                                            <p className="text-xs text-muted-foreground">Tipo Asignación</p>
                                            <Badge variant={asignacion.tipoAsignacion === "responsable" ? "outline" : "secondary"}>
                                              {asignacion.tipoAsignacion === "responsable" ? "Responsable" : "Asignado"}
                                            </Badge>
                                          </div>
                                          <div>
                                            <p className="text-xs text-muted-foreground">Versión</p>
                                            {(() => {
                                              // Verificar si todos los servicios tienen versión actualizada
                                              const tieneServiciosDesactualizados = asignacion.servicios.some((servAsig) => {
                                                const servicio = servicios.find((s) => s.id === servAsig.servicioId);
                                                return servicio && servAsig.version !== servicio.versionVigente;
                                              });
                                              
                                              return (
                                                <Badge variant={tieneServiciosDesactualizados ? "destructive" : "default"}>
                                                  {tieneServiciosDesactualizados ? "Desactualizada" : "Actualizada"}
                                                </Badge>
                                              );
                                            })()}
                                          </div>
                                        </div>
                                        
                                        {asignacion.servicios && asignacion.servicios.length > 0 && (
                                          <div className="mt-3 pt-3 border-t">
                                            <p className="text-xs text-muted-foreground mb-2">Servicios Instalados:</p>
                                            <div className="flex flex-wrap gap-2">
                                              {asignacion.servicios.map((servAsig) => {
                                                const servicio = servicios.find((s) => s.id === servAsig.servicioId);
                                                if (!servicio) return null;
                                                 
                                                const esVersionActualizada = servAsig.version === servicio.versionVigente;
                                                
                                                return (
                                                  <Badge
                                                    key={servAsig.servicioId}
                                                    variant="outline"
                                                    className={esVersionActualizada ? "border-green-500" : "border-orange-500"}
                                                  >
                                                    {servicio.nombre} v{servAsig.version}
                                                    {!esVersionActualizada && " ⚠️"}
                                                  </Badge>
                                                );
                                              })}
                                            </div>
                                          </div>
                                        )}
                                        
                                        {asignacion.notas && (
                                          <div className="mt-3 pt-3 border-t">
                                            <p className="text-xs text-muted-foreground mb-1">Notas:</p>
                                            <p className="text-sm">{asignacion.notas}</p>
                                          </div>
                                        )}
                                      </div>
                                    );
                                  })}
                              </div>
                            </TableCell>
                          </TableRow>
                        );
                        return [mainRow, expandedRow];
                      }

                      return [mainRow];
                    })}
                  </>
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {resultados.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Resumen Estadístico</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div className="p-4 border rounded-lg">
                <p className="text-sm text-muted-foreground mb-1">Total Personal</p>
                <p className="text-2xl font-bold">{resultados.length}</p>
              </div>
              <div className="p-4 border rounded-lg">
                <p className="text-sm text-muted-foreground mb-1">Total Equipos</p>
                <p className="text-2xl font-bold">
                  {resultados.reduce((acc, p) => acc + (p.equiposAsignados?.length || 0), 0)}
                </p>
              </div>
              <div className="p-4 border rounded-lg">
                <p className="text-sm text-muted-foreground mb-1">Departamentos</p>
                <p className="text-2xl font-bold">
                  {new Set(resultados.map((p) => p.departamento)).size}
                </p>
              </div>
              <div className="p-4 border rounded-lg">
                <p className="text-sm text-muted-foreground mb-1">Segmentos</p>
                <p className="text-2xl font-bold">
                  {new Set(resultados.map((p) => p.segmento)).size}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}