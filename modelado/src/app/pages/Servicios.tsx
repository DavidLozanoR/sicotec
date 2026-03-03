import { useState } from "react";
import { useData, Servicio as ServicioType } from "../context/DataContext";
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
  DialogTrigger,
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
import { Plus, Edit, Trash2, Tag } from "lucide-react";
import { toast } from "sonner";

export function Servicios() {
  const { servicios, addServicio, updateServicio, deleteServicio } = useData();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingServicio, setEditingServicio] = useState<ServicioType | null>(null);
  const [formData, setFormData] = useState({
    nombre: "",
    categoria: "Programa" as "Sistema Operativo" | "Programa" | "Driver" | "Otro",
    versiones: "",
    versionVigente: "",
  });

  const handleOpenDialog = (servicio?: ServicioType) => {
    if (servicio) {
      setEditingServicio(servicio);
      setFormData({
        nombre: servicio.nombre,
        categoria: servicio.categoria,
        versiones: servicio.versiones.join(", "),
        versionVigente: servicio.versionVigente,
      });
    } else {
      setEditingServicio(null);
      setFormData({
        nombre: "",
        categoria: "Programa",
        versiones: "",
        versionVigente: "",
      });
    }
    setIsDialogOpen(true);
  };

  const handleSubmit = () => {
    if (!formData.nombre || !formData.versionVigente) {
      toast.error("Complete los campos obligatorios");
      return;
    }

    const versionesArray = formData.versiones
      .split(",")
      .map((v) => v.trim())
      .filter((v) => v.length > 0);

    if (editingServicio) {
      updateServicio(editingServicio.id, {
        nombre: formData.nombre,
        categoria: formData.categoria,
        versiones: versionesArray,
        versionVigente: formData.versionVigente,
      });
      toast.success("Servicio actualizado correctamente");
    } else {
      addServicio({
        nombre: formData.nombre,
        categoria: formData.categoria,
        versiones: versionesArray,
        versionVigente: formData.versionVigente,
      });
      toast.success("Servicio agregado correctamente");
    }

    setIsDialogOpen(false);
  };

  const handleDelete = (id: string) => {
    deleteServicio(id);
    toast.success("Servicio eliminado correctamente");
  };

  const getCategoriaColor = (categoria: string) => {
    const colors: { [key: string]: string } = {
      "Sistema Operativo": "bg-blue-100 text-blue-800",
      Programa: "bg-green-100 text-green-800",
      Driver: "bg-purple-100 text-purple-800",
      Otro: "bg-gray-100 text-gray-800",
    };
    return colors[categoria] || colors.Otro;
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Catálogo de Servicios</h1>
          <p className="text-muted-foreground mt-1">
            Gestión del software autorizado y versiones vigentes
          </p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild>
            <Button onClick={() => handleOpenDialog()} className="flex items-center gap-2">
              <Plus className="w-4 h-4" />
              Nuevo Servicio
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>
                {editingServicio ? "Editar Servicio" : "Nuevo Servicio"}
              </DialogTitle>
              <DialogDescription>
                {editingServicio
                  ? "Actualice la información del servicio"
                  : "Agregue un nuevo servicio al catálogo"}
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <Label htmlFor="nombre">Nombre del Servicio *</Label>
                <Input
                  id="nombre"
                  value={formData.nombre}
                  onChange={(e) => setFormData({ ...formData, nombre: e.target.value })}
                  placeholder="Ej: Microsoft Office"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="categoria">Categoría *</Label>
                <Select
                  value={formData.categoria}
                  onValueChange={(value: any) => setFormData({ ...formData, categoria: value })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Sistema Operativo">Sistema Operativo</SelectItem>
                    <SelectItem value="Programa">Programa</SelectItem>
                    <SelectItem value="Driver">Driver</SelectItem>
                    <SelectItem value="Otro">Otro</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="versiones">Versiones Disponibles</Label>
                <Input
                  id="versiones"
                  value={formData.versiones}
                  onChange={(e) => setFormData({ ...formData, versiones: e.target.value })}
                  placeholder="Ej: 2019, 2021, 365 (separadas por comas)"
                />
                <p className="text-xs text-muted-foreground">
                  Ingrese las versiones separadas por comas
                </p>
              </div>
              <div className="space-y-2">
                <Label htmlFor="versionVigente">Versión Vigente *</Label>
                <Input
                  id="versionVigente"
                  value={formData.versionVigente}
                  onChange={(e) =>
                    setFormData({ ...formData, versionVigente: e.target.value })
                  }
                  placeholder="Ej: 365"
                />
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setIsDialogOpen(false)}>
                Cancelar
              </Button>
              <Button onClick={handleSubmit}>
                {editingServicio ? "Actualizar" : "Agregar"}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Servicios Registrados</CardTitle>
          <CardDescription>{servicios.length} servicios en el catálogo</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="border rounded-lg">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nombre</TableHead>
                  <TableHead>Categoría</TableHead>
                  <TableHead>Versiones Disponibles</TableHead>
                  <TableHead>Versión Vigente</TableHead>
                  <TableHead className="text-right">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {servicios.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                      No hay servicios registrados
                    </TableCell>
                  </TableRow>
                ) : (
                  servicios.map((servicio) => (
                    <TableRow key={servicio.id}>
                      <TableCell className="font-medium">{servicio.nombre}</TableCell>
                      <TableCell>
                        <Badge variant="outline" className={getCategoriaColor(servicio.categoria)}>
                          {servicio.categoria}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <div className="flex flex-wrap gap-1">
                          {servicio.versiones.map((version, idx) => (
                            <Badge key={idx} variant="secondary" className="text-xs">
                              <Tag className="w-3 h-3 mr-1" />
                              v{version}
                            </Badge>
                          ))}
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge variant="default" className="bg-accent text-accent-foreground">
                          v{servicio.versionVigente}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-2">
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleOpenDialog(servicio)}
                          >
                            <Edit className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDelete(servicio.id)}
                          >
                            <Trash2 className="w-4 h-4 text-destructive" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
