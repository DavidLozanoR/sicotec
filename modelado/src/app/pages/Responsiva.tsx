import { useState } from "react";
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "../components/ui/dialog";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../components/ui/table";
import { Badge } from "../components/ui/badge";
import { Search, FileText, ArrowRightLeft, Laptop } from "lucide-react";
import { toast } from "sonner";

export function Responsiva() {
  const { personalOperativo, cambiarResponsable, equiposGlobales } = useData();
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedPersonal, setSelectedPersonal] = useState<string | null>(null);
  const [selectedEquipoId, setSelectedEquipoId] = useState<string | null>(null);
  const [newResponsableId, setNewResponsableId] = useState<string>("");
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  // Filtrar personal que tiene equipos
  const personalConEquipos = personalOperativo.filter((p) => (p.equiposAsignados?.length || 0) > 0);

  const filteredPersonal = personalConEquipos.filter(
    (p) =>
      p.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
      p.numeroCobro.includes(searchTerm) ||
      p.departamento.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const handleOpenDialog = (personalId: string, equipoAsignadoId: string) => {
    setSelectedPersonal(personalId);
    setSelectedEquipoId(equipoAsignadoId);
    setNewResponsableId("");
    setIsDialogOpen(true);
  };

  const handleCambiarResponsable = () => {
    if (!selectedPersonal || !selectedEquipoId || !newResponsableId) {
      toast.error("Seleccione el nuevo responsable");
      return;
    }

    if (selectedPersonal === newResponsableId) {
      toast.error("El nuevo responsable debe ser diferente al actual");
      return;
    }

    const oldPersonal = personalOperativo.find((p) => p.id === selectedPersonal);
    const newPersonal = personalOperativo.find((p) => p.id === newResponsableId);
    const asignacion = oldPersonal?.equiposAsignados?.find((e) => e.id === selectedEquipoId);
    const equipoGlobal = equiposGlobales.find((e) => e.id === asignacion?.equipoId);

    if (!oldPersonal || !newPersonal || !asignacion || !equipoGlobal) {
      toast.error("Error al procesar el cambio");
      return;
    }

    // Ejecutar el cambio de responsable
    cambiarResponsable(selectedEquipoId, selectedPersonal, newResponsableId);
    
    toast.success(
      `Equipo ${equipoGlobal.marca} ${equipoGlobal.modelo} transferido de ${oldPersonal.nombre} a ${newPersonal.nombre}`
    );
    
    setIsDialogOpen(false);
    setSelectedPersonal(null);
    setSelectedEquipoId(null);
    setNewResponsableId("");
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Responsiva de Equipos</h1>
        <p className="text-muted-foreground mt-1">
          Gestión de transferencias y trazabilidad de activos
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <FileText className="w-5 h-5" />
            Buscar Responsable Actual
          </CardTitle>
          <CardDescription>
            Busque al personal operativo para visualizar y gestionar sus equipos asignados
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <Input
              placeholder="Buscar por nombre, número de cobro o departamento..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10 bg-input-background"
            />
          </div>
        </CardContent>
      </Card>

      <div className="grid gap-4">
        {filteredPersonal.length === 0 ? (
          <Card>
            <CardContent className="py-12 text-center text-muted-foreground">
              {searchTerm
                ? "No se encontraron resultados"
                : "Ingrese un término de búsqueda"}
            </CardContent>
          </Card>
        ) : (
          filteredPersonal.map((personal) => (
            <Card key={personal.id}>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle>{personal.nombre}</CardTitle>
                    <CardDescription>
                      {personal.puesto} - {personal.departamento}
                    </CardDescription>
                  </div>
                  <Badge variant="secondary" className="text-sm">
                    {personal.equiposAsignados?.length || 0}{" "}
                    {personal.equiposAsignados?.length === 1 ? "equipo" : "equipos"}
                  </Badge>
                </div>
              </CardHeader>
              <CardContent>
                <div className="border rounded-lg">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Marca/Modelo</TableHead>
                        <TableHead>Serie</TableHead>
                        <TableHead>IP</TableHead>
                        <TableHead>Acción</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {personal.equiposAsignados?.map((asignacion) => {
                        const equipo = equiposGlobales.find((e) => e.id === asignacion.equipoId);
                        if (!equipo) return null;
                        return (
                          <TableRow key={asignacion.id}>
                            <TableCell>
                              <Badge>{equipo.tipo}</Badge>
                            </TableCell>
                            <TableCell>
                              <div className="flex items-center gap-2">
                                <Laptop className="w-4 h-4 text-muted-foreground" />
                                <span>
                                  {equipo.marca} {equipo.modelo}
                                </span>
                              </div>
                            </TableCell>
                            <TableCell className="text-sm font-mono">{equipo.serie}</TableCell>
                            <TableCell className="text-sm font-mono">
                              {asignacion.ip || "-"}
                            </TableCell>
                            <TableCell>
                              <Button
                                size="sm"
                                variant="outline"
                                onClick={() => handleOpenDialog(personal.id, asignacion.id)}
                                className="flex items-center gap-2"
                              >
                                <ArrowRightLeft className="w-4 h-4" />
                                Cambiar Responsable
                              </Button>
                            </TableCell>
                          </TableRow>
                        );
                      })}
                    </TableBody>
                  </Table>
                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Cambiar Responsable de Equipo</DialogTitle>
            <DialogDescription>
              Seleccione el nuevo responsable para transferir el equipo
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            {selectedPersonal && selectedEquipoId && (
              <div className="p-4 bg-muted rounded-lg space-y-2">
                <p className="text-sm font-medium">Equipo a transferir:</p>
                {(() => {
                  const personal = personalOperativo.find((p) => p.id === selectedPersonal);
                  const asignacion = personal?.equiposAsignados?.find((e) => e.id === selectedEquipoId);
                  const equipo = equiposGlobales.find((e) => e.id === asignacion?.equipoId);
                  if (!equipo) return null;
                  return (
                    <div className="text-sm">
                      <p>
                        <strong>Tipo:</strong> {equipo.tipo}
                      </p>
                      <p>
                        <strong>Equipo:</strong> {equipo.marca} {equipo.modelo}
                      </p>
                      <p>
                        <strong>Serie:</strong> {equipo.serie}
                      </p>
                      <p className="mt-2 text-muted-foreground">
                        <strong>Responsable actual:</strong> {personal?.nombre}
                      </p>
                    </div>
                  );
                })()}
              </div>
            )}

            <div className="space-y-2">
              <Label htmlFor="newResponsable">Nuevo Responsable *</Label>
              <Select value={newResponsableId} onValueChange={setNewResponsableId}>
                <SelectTrigger>
                  <SelectValue placeholder="Seleccione el nuevo responsable" />
                </SelectTrigger>
                <SelectContent>
                  {personalOperativo
                    .filter((p) => p.id !== selectedPersonal)
                    .map((personal) => (
                      <SelectItem key={personal.id} value={personal.id}>
                        {personal.nombre} - {personal.departamento}
                      </SelectItem>
                    ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsDialogOpen(false)}>
              Cancelar
            </Button>
            <Button onClick={handleCambiarResponsable}>Transferir Equipo</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}