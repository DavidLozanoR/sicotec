import { useState, useEffect } from "react";
import { useNavigate, useParams } from "react-router";
import {
  useData,
  PersonalOperativo as PersonalOperativoType,
  EquipoAsignado,
  ServicioAsignado,
} from "../context/DataContext";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../components/ui/tabs";
import { Textarea } from "../components/ui/textarea";
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
import { Checkbox } from "../components/ui/checkbox";
import { RadioGroup, RadioGroupItem } from "../components/ui/radio-group";
import { ArrowLeft, Plus, Search, Trash2, Save, Monitor, Pencil } from "lucide-react";
import { toast } from "sonner";

// Datos de usuarios iniciales para búsqueda
const USUARIOS_INICIALES = [
  {
    numeroCobro: "003456",
    nombre: "Carlos Ramírez Torres",
    direccion: "Av. Constitución 789",
    correo: "carlos.ramirez@gobierno.gob.mx",
    puesto: "Coordinador de Proyectos",
    departamento: "Planeación",
    extension: "3456",
    cuentaDominio: "cramirez",
  },
  {
    numeroCobro: "004567",
    nombre: "Ana Martínez Sánchez",
    direccion: "Calle Independencia 321",
    correo: "ana.martinez@gobierno.gob.mx",
    puesto: "Jefa de Recursos Humanos",
    departamento: "Recursos Humanos",
    extension: "4567",
    cuentaDominio: "amartinez",
  },
  {
    numeroCobro: "005678",
    nombre: "Luis González Pérez",
    direccion: "Av. Juárez 654",
    correo: "luis.gonzalez@gobierno.gob.mx",
    puesto: "Analista de Datos",
    departamento: "Planeación",
    extension: "5678",
    cuentaDominio: "lgonzalez",
  },
];

export function PersonalOperativoForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const {
    addPersonalOperativo,
    updatePersonalOperativo,
    getPersonalOperativoById,
    servicios,
    segmentosRed,
    equiposGlobales,
    updateEquipoGlobal,
    addEquipoGlobal,
  } = useData();

  const isEditing = !!id;
  const existingPersonal = isEditing ? getPersonalOperativoById(id) : undefined;

  // Estado del formulario
  const [formData, setFormData] = useState<Omit<PersonalOperativoType, "id">>({
    numeroCobro: "",
    nombre: "",
    direccion: "",
    correo: "",
    puesto: "",
    departamento: "",
    extension: "",
    cuentaDominio: "",
    segmento: "",
    equiposAsignados: [],
  });

  const [showSearchDialog, setShowSearchDialog] = useState(false);
  const [showAsignarDialog, setShowAsignarDialog] = useState(false);
  const [showEditarDialog, setShowEditarDialog] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  const [editingEquipoIndex, setEditingEquipoIndex] = useState<number | null>(null);

  // Estado para asignación/edición de equipo
  const [selectedEquipoId, setSelectedEquipoId] = useState("");
  const [selectedIp, setSelectedIp] = useState("");
  const [notasEquipo, setNotasEquipo] = useState("");
  const [tipoAsignacion, setTipoAsignacion] = useState<"asignado" | "responsable">("asignado");
  const [serviciosTemporales, setServiciosTemporales] = useState<ServicioAsignado[]>([]);
  const [modoIngreso, setModoIngreso] = useState<"seleccionar" | "manual">("seleccionar");
  
  // Campos para ingreso manual de equipo
  const [equipoManual, setEquipoManual] = useState({
    tipo: "Desktop" as "Desktop" | "Laptop" | "Impresora" | "Otro",
    marca: "",
    modelo: "",
    serie: "",
    mac: "",
    ip: "",
  });

  useEffect(() => {
    if (existingPersonal) {
      setFormData(existingPersonal);
    }
  }, [existingPersonal]);

  const handleInputChange = (field: keyof typeof formData, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSearchUser = (usuario: typeof USUARIOS_INICIALES[0]) => {
    setFormData((prev) => ({
      ...prev,
      ...usuario,
    }));
    setShowSearchDialog(false);
    toast.success("Datos precargados correctamente");
  };

  const handleOpenAsignarDialog = () => {
    setSelectedEquipoId("");
    setSelectedIp("");
    setNotasEquipo("");
    setTipoAsignacion("asignado");
    setServiciosTemporales([]);
    setModoIngreso("seleccionar");
    setEquipoManual({
      tipo: "Desktop",
      marca: "",
      modelo: "",
      serie: "",
      mac: "",
      ip: "",
    });
    setShowAsignarDialog(true);
  };

  const handleOpenEditarDialog = (index: number) => {
    const asignacion = formData.equiposAsignados[index];
    const equipoGlobal = equiposGlobales.find((e) => e.id === asignacion.equipoId);
    
    if (!equipoGlobal) return;

    setEditingEquipoIndex(index);
    setSelectedEquipoId(asignacion.equipoId);
    setSelectedIp(asignacion.ip);
    setNotasEquipo(asignacion.notas);
    setTipoAsignacion(asignacion.tipoAsignacion);
    setServiciosTemporales(asignacion.servicios);
    setShowEditarDialog(true);
  };

  const handleAsignarEquipo = () => {
    // Validar según el modo
    if (tipoAsignacion === "responsable") {
      toast.error("No se pueden asignar equipos en modo Responsable");
      return;
    }

    let nuevoEquipoId = selectedEquipoId;

    // Si es modo manual, crear el equipo primero
    if (modoIngreso === "manual") {
      if (!equipoManual.marca || !equipoManual.modelo || !equipoManual.serie) {
        toast.error("Complete los campos obligatorios del equipo (marca, modelo, serie)");
        return;
      }
      if (!equipoManual.ip) {
        toast.error("Ingrese una dirección IP");
        return;
      }

      // Crear nuevo equipo global
      const nuevoEquipoGlobal = {
        tipo: equipoManual.tipo,
        marca: equipoManual.marca,
        modelo: equipoManual.modelo,
        serie: equipoManual.serie,
        mac: equipoManual.mac || undefined,
        ipsDisponibles: [equipoManual.ip],
        asignadoA: id || null,
        responsableId: null,
        estado: "Asignado" as const,
      };

      addEquipoGlobal(nuevoEquipoGlobal);
      nuevoEquipoId = Date.now().toString();
      setSelectedIp(equipoManual.ip);
    } else {
      // Modo seleccionar
      if (!selectedEquipoId) {
        toast.error("Seleccione un equipo");
        return;
      }
      if (!selectedIp) {
        toast.error("Seleccione una dirección IP");
        return;
      }
    }

    const nuevaAsignacion: EquipoAsignado = {
      id: Date.now().toString(),
      equipoId: nuevoEquipoId,
      ip: modoIngreso === "manual" ? equipoManual.ip : selectedIp,
      notas: notasEquipo,
      fechaAsignacion: new Date().toISOString().split("T")[0],
      servicios: serviciosTemporales,
      tipoAsignacion: tipoAsignacion,
    };

    setFormData((prev) => ({
      ...prev,
      equiposAsignados: [...prev.equiposAsignados, nuevaAsignacion],
    }));

    // Actualizar el estado del equipo en el inventario (solo si es de la lista)
    if (modoIngreso === "seleccionar") {
      updateEquipoGlobal(nuevoEquipoId, { 
        estado: "Asignado",
        asignadoA: id || null 
      });
    }

    setShowAsignarDialog(false);
    toast.success("Equipo asignado correctamente");
  };

  const handleActualizarEquipo = () => {
    if (editingEquipoIndex === null) return;
    if (!selectedIp) {
      toast.error("Seleccione una dirección IP");
      return;
    }

    const updatedAsignacion: EquipoAsignado = {
      ...formData.equiposAsignados[editingEquipoIndex],
      ip: selectedIp,
      notas: notasEquipo,
      servicios: serviciosTemporales,
      tipoAsignacion: tipoAsignacion,
    };

    const newEquipos = [...formData.equiposAsignados];
    newEquipos[editingEquipoIndex] = updatedAsignacion;

    setFormData((prev) => ({
      ...prev,
      equiposAsignados: newEquipos,
    }));

    // Actualizar el equipo global
    if (tipoAsignacion === "responsable") {
      updateEquipoGlobal(selectedEquipoId, { 
        responsableId: id || null,
        asignadoA: id || null 
      });
    } else {
      updateEquipoGlobal(selectedEquipoId, { 
        responsableId: null,
        asignadoA: id || null 
      });
    }

    setShowEditarDialog(false);
    setEditingEquipoIndex(null);
    toast.success("Equipo actualizado correctamente");
  };

  const handleToggleServicio = (servicioId: string) => {
    const servicio = servicios.find((s) => s.id === servicioId);
    if (!servicio) return;

    const servicioExists = serviciosTemporales.some((s) => s.servicioId === servicioId);

    if (servicioExists) {
      setServiciosTemporales(serviciosTemporales.filter((s) => s.servicioId !== servicioId));
    } else {
      const newServicio: ServicioAsignado = {
        id: Date.now().toString(),
        servicioId,
        version: servicio.versionVigente,
        fechaInstalacion: new Date().toISOString().split("T")[0],
      };
      setServiciosTemporales([...serviciosTemporales, newServicio]);
    }
  };

  const handleRemoveEquipo = (index: number) => {
    const equipoAsignado = formData.equiposAsignados[index];
    if (equipoAsignado) {
      // Actualizar el equipo global a disponible
      updateEquipoGlobal(equipoAsignado.equipoId, { 
        estado: "Disponible", 
        asignadoA: null, 
        responsableId: null 
      });
    }
    
    setFormData((prev) => ({
      ...prev,
      equiposAsignados: prev.equiposAsignados.filter((_, i) => i !== index),
    }));
    toast.success("Equipo removido");
  };

  const handleSubmit = () => {
    if (!formData.nombre || !formData.numeroCobro || !formData.correo) {
      toast.error("Complete los campos obligatorios");
      return;
    }

    if (isEditing && id) {
      updatePersonalOperativo(id, formData);
      toast.success("Personal actualizado correctamente");
    } else {
      addPersonalOperativo(formData);
      toast.success("Personal creado correctamente");
    }

    navigate("/personal-operativo");
  };

  const filteredUsuarios = USUARIOS_INICIALES.filter(
    (u) =>
      u.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
      u.numeroCobro.includes(searchTerm)
  );

  const equiposDisponibles = equiposGlobales.filter((e) => e.estado === "Disponible");
  const equipoSeleccionado = equiposGlobales.find((e) => e.id === selectedEquipoId);
  
  // Separar equipos por tipo de asignación
  const equiposAsignados = formData.equiposAsignados.filter((e) => e.tipoAsignacion === "asignado");
  const equiposResponsables = formData.equiposAsignados.filter((e) => e.tipoAsignacion === "responsable");

  // Modal de Asignación/Edición de Equipo con Pestañas
  const EquipoModal = ({ 
    isOpen, 
    onClose, 
    onConfirm, 
    title, 
    isEditMode = false 
  }: { 
    isOpen: boolean; 
    onClose: () => void; 
    onConfirm: () => void;
    title: string;
    isEditMode?: boolean;
  }) => (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription>
            {isEditMode 
              ? "Modifique los detalles del equipo asignado" 
              : "Configure los detalles del equipo a asignar"}
          </DialogDescription>
        </DialogHeader>

        <Tabs defaultValue="caracteristicas" className="space-y-4">
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="caracteristicas">Características</TabsTrigger>
            <TabsTrigger value="network">Network</TabsTrigger>
            <TabsTrigger value="servicios">Servicios</TabsTrigger>
          </TabsList>

          <TabsContent value="caracteristicas" className="space-y-4">
            <div className="p-4 border rounded-lg bg-muted/30 space-y-4">
              <h3 className="font-semibold">Datos del Equipo</h3>
              
              {/* Tipo de Asignación - SIEMPRE PRIMERO */}
              <div className="space-y-2 p-3 border rounded-lg bg-background">
                <Label className="text-base">Tipo de Asignación *</Label>
                <RadioGroup 
                  value={tipoAsignacion} 
                  onValueChange={(value: "asignado" | "responsable") => {
                    setTipoAsignacion(value);
                    // Limpiar selección si cambia a responsable
                    if (value === "responsable" && !isEditMode) {
                      setSelectedEquipoId("");
                      setSelectedIp("");
                    }
                  }}
                  disabled={isEditMode}
                >
                  <div className="flex items-center space-x-2">
                    <RadioGroupItem value="asignado" id={isEditMode ? "asignado-edit" : "asignado"} />
                    <Label htmlFor={isEditMode ? "asignado-edit" : "asignado"} className="cursor-pointer">
                      Asignado - Equipo asignado para uso del personal
                    </Label>
                  </div>
                  <div className="flex items-center space-x-2">
                    <RadioGroupItem value="responsable" id={isEditMode ? "responsable-edit" : "responsable"} />
                    <Label htmlFor={isEditMode ? "responsable-edit" : "responsable"} className="cursor-pointer">
                      Responsable - Personal responsable del equipo
                    </Label>
                  </div>
                </RadioGroup>
              </div>

              {/* Mensaje cuando es responsable */}
              {tipoAsignacion === "responsable" && !isEditMode && (
                <div className="p-4 border-2 border-dashed rounded-lg bg-muted/50 text-center">
                  <Monitor className="w-12 h-12 mx-auto mb-3 opacity-30" />
                  <p className="text-muted-foreground font-medium">
                    No se pueden asignar equipos en modo "Responsable"
                  </p>
                  <p className="text-sm text-muted-foreground mt-1">
                    El modo responsable no permite seleccionar equipos del inventario
                  </p>
                </div>
              )}

              {/* Modo de ingreso - Solo para añadir y modo asignado */}
              {!isEditMode && tipoAsignacion === "asignado" && (
                <div className="space-y-2 p-3 border rounded-lg bg-background">
                  <Label className="text-base">Modo de Ingreso</Label>
                  <RadioGroup 
                    value={modoIngreso} 
                    onValueChange={(value: "seleccionar" | "manual") => {
                      setModoIngreso(value);
                      if (value === "manual") {
                        setSelectedEquipoId("");
                        setSelectedIp("");
                      }
                    }}
                  >
                    <div className="flex items-center space-x-2">
                      <RadioGroupItem value="seleccionar" id="modo-seleccionar" />
                      <Label htmlFor="modo-seleccionar" className="cursor-pointer">
                        Seleccionar de lista de equipos disponibles
                      </Label>
                    </div>
                    <div className="flex items-center space-x-2">
                      <RadioGroupItem value="manual" id="modo-manual" />
                      <Label htmlFor="modo-manual" className="cursor-pointer">
                        Ingresar datos manualmente
                      </Label>
                    </div>
                  </RadioGroup>
                </div>
              )}

              {/* Seleccionar equipo existente */}
              {!isEditMode && tipoAsignacion === "asignado" && modoIngreso === "seleccionar" && (
                <>
                  <div className="space-y-2">
                    <Label>Seleccionar Equipo *</Label>
                    <Select value={selectedEquipoId} onValueChange={setSelectedEquipoId}>
                      <SelectTrigger>
                        <SelectValue placeholder="Seleccione un equipo disponible" />
                      </SelectTrigger>
                      <SelectContent>
                        {equiposDisponibles.length > 0 ? (
                          equiposDisponibles.map((equipo) => (
                            <SelectItem key={equipo.id} value={equipo.id}>
                              <div className="flex items-center gap-2">
                                <Monitor className="w-4 h-4" />
                                {equipo.tipo} - {equipo.marca} {equipo.modelo} (S/N: {equipo.serie})
                              </div>
                            </SelectItem>
                          ))
                        ) : (
                          <SelectItem value="no-disponibles" disabled>
                            No hay equipos disponibles
                          </SelectItem>
                        )}
                      </SelectContent>
                    </Select>
                  </div>

                  {equipoSeleccionado && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <Label>Tipo</Label>
                        <Input value={equipoSeleccionado.tipo} disabled />
                      </div>
                      <div className="space-y-2">
                        <Label>Marca</Label>
                        <Input value={equipoSeleccionado.marca} disabled />
                      </div>
                      <div className="space-y-2">
                        <Label>Modelo</Label>
                        <Input value={equipoSeleccionado.modelo} disabled />
                      </div>
                      <div className="space-y-2">
                        <Label>Número de Serie</Label>
                        <Input value={equipoSeleccionado.serie} disabled />
                      </div>
                    </div>
                  )}
                </>
              )}

              {/* Ingresar datos manualmente */}
              {!isEditMode && tipoAsignacion === "asignado" && modoIngreso === "manual" && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Tipo *</Label>
                    <Select 
                      value={equipoManual.tipo} 
                      onValueChange={(value: "Desktop" | "Laptop" | "Impresora" | "Otro") => 
                        setEquipoManual({...equipoManual, tipo: value})
                      }
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Seleccione tipo" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="Desktop">Desktop</SelectItem>
                        <SelectItem value="Laptop">Laptop</SelectItem>
                        <SelectItem value="Impresora">Impresora</SelectItem>
                        <SelectItem value="Otro">Otro</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Marca *</Label>
                    <Input 
                      value={equipoManual.marca}
                      onChange={(e) => setEquipoManual({...equipoManual, marca: e.target.value})}
                      placeholder="Ej: Dell, HP, Lenovo"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Modelo *</Label>
                    <Input 
                      value={equipoManual.modelo}
                      onChange={(e) => setEquipoManual({...equipoManual, modelo: e.target.value})}
                      placeholder="Ej: Latitude 5420"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Número de Serie *</Label>
                    <Input 
                      value={equipoManual.serie}
                      onChange={(e) => setEquipoManual({...equipoManual, serie: e.target.value})}
                      placeholder="Ej: DL1234567"
                    />
                  </div>
                  <div className="space-y-2 md:col-span-2">
                    <Label>Dirección MAC</Label>
                    <Input 
                      value={equipoManual.mac}
                      onChange={(e) => setEquipoManual({...equipoManual, mac: e.target.value})}
                      placeholder="Ej: 00:1B:44:11:3A:B7"
                    />
                  </div>
                </div>
              )}

              {/* Datos del equipo en edición */}
              {isEditMode && equipoSeleccionado && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Tipo</Label>
                    <Input value={equipoSeleccionado.tipo} disabled />
                  </div>
                  <div className="space-y-2">
                    <Label>Marca</Label>
                    <Input value={equipoSeleccionado.marca} disabled />
                  </div>
                  <div className="space-y-2">
                    <Label>Modelo</Label>
                    <Input value={equipoSeleccionado.modelo} disabled />
                  </div>
                  <div className="space-y-2">
                    <Label>Número de Serie</Label>
                    <Input value={equipoSeleccionado.serie} disabled />
                  </div>
                </div>
              )}

              {/* Notas - siempre visible si hay equipo seleccionado o en modo manual */}
              {((equipoSeleccionado || (modoIngreso === "manual" && tipoAsignacion === "asignado")) && !isEditMode) || isEditMode ? (
                <div className="space-y-2">
                  <Label>Notas</Label>
                  <Textarea
                    value={notasEquipo}
                    onChange={(e) => setNotasEquipo(e.target.value)}
                    placeholder="Ej: Equipo principal de trabajo, requiere mantenimiento mensual..."
                    rows={3}
                  />
                </div>
              ) : null}
            </div>
          </TabsContent>

          <TabsContent value="network" className="space-y-4">
            <div className="p-4 border rounded-lg bg-muted/30 space-y-4">
              <h3 className="font-semibold">Configuración de Red</h3>
              
              {tipoAsignacion === "responsable" && !isEditMode && (
                <div className="p-4 border-2 border-dashed rounded-lg bg-muted/50 text-center">
                  <p className="text-muted-foreground">
                    No disponible en modo "Responsable"
                  </p>
                </div>
              )}

              {tipoAsignacion === "asignado" && modoIngreso === "manual" && !isEditMode && (
                <div className="space-y-4">
                  <div className="space-y-2">
                    <Label>Dirección MAC</Label>
                    <Input 
                      value={equipoManual.mac}
                      onChange={(e) => setEquipoManual({...equipoManual, mac: e.target.value})}
                      placeholder="Ej: 00:1B:44:11:3A:B7"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Dirección IP *</Label>
                    <Input 
                      value={equipoManual.ip}
                      onChange={(e) => setEquipoManual({...equipoManual, ip: e.target.value})}
                      placeholder="Ej: 192.168.1.100"
                    />
                  </div>
                </div>
              )}
              
              {equipoSeleccionado && tipoAsignacion === "asignado" && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Dirección MAC</Label>
                    <Input value={equipoSeleccionado.mac || "No disponible"} disabled />
                  </div>
                  <div className="space-y-2">
                    <Label>Dirección IP *</Label>
                    <Select value={selectedIp} onValueChange={setSelectedIp}>
                      <SelectTrigger>
                        <SelectValue placeholder="Seleccione una IP disponible" />
                      </SelectTrigger>
                      <SelectContent>
                        {equipoSeleccionado.ipsDisponibles.map((ip) => (
                          <SelectItem key={ip} value={ip}>
                            {ip}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              )}
            </div>
          </TabsContent>

          <TabsContent value="servicios" className="space-y-4">
            <div className="p-4 border rounded-lg bg-muted/30 space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="font-semibold">Servicios Instalados</h3>
                <Badge variant="secondary">{serviciosTemporales.length} seleccionados</Badge>
              </div>

              {tipoAsignacion === "responsable" && !isEditMode && (
                <div className="p-4 border-2 border-dashed rounded-lg bg-muted/50 text-center">
                  <p className="text-muted-foreground">
                    No disponible en modo "Responsable"
                  </p>
                </div>
              )}

              {tipoAsignacion === "asignado" && (
                <div className="space-y-2 max-h-96 overflow-y-auto">
                  {servicios.map((servicio) => {
                    const isSelected = serviciosTemporales.some(
                      (s) => s.servicioId === servicio.id
                    );
                    return (
                      <div
                        key={servicio.id}
                        className="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors"
                      >
                        <div className="flex items-center gap-3">
                          <Checkbox
                            id={`servicio-${servicio.id}`}
                            checked={isSelected}
                            onCheckedChange={() => handleToggleServicio(servicio.id)}
                          />
                          <div>
                            <Label htmlFor={`servicio-${servicio.id}`} className="cursor-pointer">
                              {servicio.nombre}
                            </Label>
                            <p className="text-sm text-muted-foreground">
                              {servicio.categoria} - Versión: {servicio.versionVigente}
                            </p>
                          </div>
                        </div>
                        {isSelected && (
                          <Badge variant="secondary" className="bg-accent text-accent-foreground">
                            Seleccionado
                          </Badge>
                        )}
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </TabsContent>
        </Tabs>

        <div className="flex justify-end gap-2 pt-4">
          <Button variant="outline" onClick={onClose}>
            Cancelar
          </Button>
          <Button 
            onClick={onConfirm}
            disabled={tipoAsignacion === "responsable" && !isEditMode}
          >
            {isEditMode ? "Actualizar" : "Confirmar"} Asignación
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );

  // Renderizar lista de equipos
  const renderEquiposList = (equipos: EquipoAsignado[], tipo: "asignado" | "responsable") => {
    if (equipos.length === 0) {
      return (
        <div className="text-center py-6 text-muted-foreground border rounded-lg bg-muted/20">
          <Monitor className="w-10 h-10 mx-auto mb-2 opacity-50" />
          <p className="text-sm">No hay equipos {tipo === "asignado" ? "asignados" : "responsables"}</p>
        </div>
      );
    }

    return (
      <div className="border rounded-lg overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Tipo</TableHead>
              <TableHead>Equipo</TableHead>
              <TableHead>Serie</TableHead>
              <TableHead>IP</TableHead>
              <TableHead>Servicios</TableHead>
              <TableHead>Notas</TableHead>
              <TableHead>Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {equipos.map((asignacion) => {
              const index = formData.equiposAsignados.findIndex((e) => e.id === asignacion.id);
              const equipoGlobal = equiposGlobales.find((e) => e.id === asignacion.equipoId);
              return (
                <TableRow key={asignacion.id}>
                  <TableCell>
                    <Badge>{equipoGlobal?.tipo || "N/A"}</Badge>
                  </TableCell>
                  <TableCell>
                    {equipoGlobal?.marca} {equipoGlobal?.modelo}
                  </TableCell>
                  <TableCell className="text-sm">{equipoGlobal?.serie}</TableCell>
                  <TableCell className="text-sm font-mono">{asignacion.ip}</TableCell>
                  <TableCell>
                    <Badge variant="outline">{asignacion.servicios.length}</Badge>
                  </TableCell>
                  <TableCell className="max-w-[200px] truncate text-sm">
                    {asignacion.notas || "-"}
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleOpenEditarDialog(index)}
                      >
                        <Pencil className="w-4 h-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleRemoveEquipo(index)}
                      >
                        <Trash2 className="w-4 h-4 text-destructive" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              );
            })}
          </TableBody>
        </Table>
      </div>
    );
  };

  // Vista de creación (modo original con pestañas)
  if (!isEditing) {
    return (
      <div className="space-y-6">
        <div className="flex items-center gap-4">
          <Button variant="ghost" size="icon" onClick={() => navigate("/personal-operativo")}>
            <ArrowLeft className="w-5 h-5" />
          </Button>
          <div>
            <h1 className="text-3xl font-bold">Nuevo Personal Operativo</h1>
            <p className="text-muted-foreground mt-1">
              Alta integral de personal con asignación de equipos
            </p>
          </div>
        </div>

        <Tabs defaultValue="caracteristicas" className="space-y-6">
          <TabsList className="grid w-full grid-cols-2">
            <TabsTrigger value="caracteristicas">Características</TabsTrigger>
            <TabsTrigger value="dispositivos">Asignar Dispositivo</TabsTrigger>
          </TabsList>

          <TabsContent value="caracteristicas" className="space-y-6">
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle>Datos Personales</CardTitle>
                    <CardDescription>Información del personal operativo</CardDescription>
                  </div>
                  <Dialog open={showSearchDialog} onOpenChange={setShowSearchDialog}>
                    <DialogTrigger asChild>
                      <Button variant="outline" className="flex items-center gap-2">
                        <Search className="w-4 h-4" />
                        Buscar Usuario
                      </Button>
                    </DialogTrigger>
                    <DialogContent className="max-w-2xl">
                      <DialogHeader>
                        <DialogTitle>Buscar en Base de Usuarios</DialogTitle>
                        <DialogDescription>
                          Seleccione un usuario para precargar sus datos
                        </DialogDescription>
                      </DialogHeader>
                      <div className="space-y-4">
                        <Input
                          placeholder="Buscar por nombre o número de cobro..."
                          value={searchTerm}
                          onChange={(e) => setSearchTerm(e.target.value)}
                        />
                        <div className="border rounded-lg">
                          <Table>
                            <TableHeader>
                              <TableRow>
                                <TableHead>No. Cobro</TableHead>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Departamento</TableHead>
                                <TableHead>Acción</TableHead>
                              </TableRow>
                            </TableHeader>
                            <TableBody>
                              {filteredUsuarios.map((usuario) => (
                                <TableRow key={usuario.numeroCobro}>
                                  <TableCell>{usuario.numeroCobro}</TableCell>
                                  <TableCell>{usuario.nombre}</TableCell>
                                  <TableCell>{usuario.departamento}</TableCell>
                                  <TableCell>
                                    <Button
                                      size="sm"
                                      onClick={() => handleSearchUser(usuario)}
                                    >
                                      Seleccionar
                                    </Button>
                                  </TableCell>
                                </TableRow>
                              ))}
                            </TableBody>
                          </Table>
                        </div>
                      </div>
                    </DialogContent>
                  </Dialog>
                </div>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="numeroCobro">Número de Cobro *</Label>
                    <Input
                      id="numeroCobro"
                      value={formData.numeroCobro}
                      onChange={(e) => handleInputChange("numeroCobro", e.target.value)}
                      placeholder="Ej: 001234"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="nombre">Nombre Completo *</Label>
                    <Input
                      id="nombre"
                      value={formData.nombre}
                      onChange={(e) => handleInputChange("nombre", e.target.value)}
                      placeholder="Ej: Juan Pérez García"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="correo">Correo Electrónico *</Label>
                    <Input
                      id="correo"
                      type="email"
                      value={formData.correo}
                      onChange={(e) => handleInputChange("correo", e.target.value)}
                      placeholder="nombre@gobierno.gob.mx"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="cuentaDominio">Cuenta de Dominio</Label>
                    <Input
                      id="cuentaDominio"
                      value={formData.cuentaDominio}
                      onChange={(e) => handleInputChange("cuentaDominio", e.target.value)}
                      placeholder="Ej: jperez"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="departamento">Departamento</Label>
                    <Input
                      id="departamento"
                      value={formData.departamento}
                      onChange={(e) => handleInputChange("departamento", e.target.value)}
                      placeholder="Ej: Tecnologías de la Información"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="puesto">Puesto</Label>
                    <Input
                      id="puesto"
                      value={formData.puesto}
                      onChange={(e) => handleInputChange("puesto", e.target.value)}
                      placeholder="Ej: Analista de Sistemas"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="extension">Extensión</Label>
                    <Input
                      id="extension"
                      value={formData.extension}
                      onChange={(e) => handleInputChange("extension", e.target.value)}
                      placeholder="Ej: 1234"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="segmento">Segmento de Red</Label>
                    <Select
                      value={formData.segmento}
                      onValueChange={(value) => handleInputChange("segmento", value)}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Seleccione un segmento" />
                      </SelectTrigger>
                      <SelectContent>
                        {segmentosRed.map((segmento) => (
                          <SelectItem key={segmento.id} value={segmento.nombre}>
                            {segmento.nombre} - {segmento.piso}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2 md:col-span-2">
                    <Label htmlFor="direccion">Dirección</Label>
                    <Input
                      id="direccion"
                      value={formData.direccion}
                      onChange={(e) => handleInputChange("direccion", e.target.value)}
                      placeholder="Ej: Av. Insurgentes Sur 1234"
                    />
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="dispositivos" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Asignación de Dispositivos</CardTitle>
                <CardDescription>
                  Asigne equipos disponibles del inventario a este personal
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <Button 
                  className="w-full flex items-center gap-2"
                  onClick={handleOpenAsignarDialog}
                >
                  <Plus className="w-4 h-4" />
                  Asignar Dispositivo
                </Button>

                {formData.equiposAsignados.length > 0 && (
                  <div className="space-y-2">
                    <h3 className="font-semibold">Equipos Asignados</h3>
                    <div className="border rounded-lg">
                      <Table>
                        <TableHeader>
                          <TableRow>
                            <TableHead>Tipo</TableHead>
                            <TableHead>Equipo</TableHead>
                            <TableHead>Serie</TableHead>
                            <TableHead>IP</TableHead>
                            <TableHead>Servicios</TableHead>
                            <TableHead>Notas</TableHead>
                            <TableHead>Acciones</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {formData.equiposAsignados.map((asignacion, index) => {
                            const equipoGlobal = equiposGlobales.find(
                              (e) => e.id === asignacion.equipoId
                            );
                            return (
                              <TableRow key={asignacion.id}>
                                <TableCell>
                                  <Badge>{equipoGlobal?.tipo || "N/A"}</Badge>
                                </TableCell>
                                <TableCell>
                                  {equipoGlobal?.marca} {equipoGlobal?.modelo}
                                </TableCell>
                                <TableCell className="text-sm">{equipoGlobal?.serie}</TableCell>
                                <TableCell className="text-sm font-mono">{asignacion.ip}</TableCell>
                                <TableCell>
                                  <Badge variant="outline">{asignacion.servicios.length}</Badge>
                                </TableCell>
                                <TableCell className="max-w-[200px] truncate text-sm">
                                  {asignacion.notas || "-"}
                                </TableCell>
                                <TableCell>
                                  <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => handleRemoveEquipo(index)}
                                  >
                                    <Trash2 className="w-4 h-4 text-destructive" />
                                  </Button>
                                </TableCell>
                              </TableRow>
                            );
                          })}
                        </TableBody>
                      </Table>
                    </div>
                  </div>
                )}

                {formData.equiposAsignados.length === 0 && (
                  <div className="text-center py-8 text-muted-foreground">
                    <Monitor className="w-12 h-12 mx-auto mb-3 opacity-50" />
                    <p>No hay dispositivos asignados</p>
                    <p className="text-sm">Haga clic en "Asignar Dispositivo" para comenzar</p>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>

        <EquipoModal
          isOpen={showAsignarDialog}
          onClose={() => setShowAsignarDialog(false)}
          onConfirm={handleAsignarEquipo}
          title="Asignar Dispositivo"
          isEditMode={false}
        />

        <div className="flex justify-end gap-4">
          <Button variant="outline" onClick={() => navigate("/personal-operativo")}>
            Cancelar
          </Button>
          <Button onClick={handleSubmit} className="flex items-center gap-2">
            <Save className="w-4 h-4" />
            Guardar Personal
          </Button>
        </div>
      </div>
    );
  }

  // Vista de edición (nueva estructura)
  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => navigate("/personal-operativo")}>
          <ArrowLeft className="w-5 h-5" />
        </Button>
        <div>
          <h1 className="text-3xl font-bold">Editar Personal Operativo</h1>
          <p className="text-muted-foreground mt-1">
            Actualice la información del personal y sus equipos asignados
          </p>
        </div>
      </div>

      {/* Sección: Datos Personales */}
      <Card>
        <CardHeader>
          <CardTitle>Datos Personales</CardTitle>
          <CardDescription>Información del personal operativo</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="numeroCobro">Número de Cobro *</Label>
              <Input
                id="numeroCobro"
                value={formData.numeroCobro}
                onChange={(e) => handleInputChange("numeroCobro", e.target.value)}
                placeholder="Ej: 001234"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="nombre">Nombre Completo *</Label>
              <Input
                id="nombre"
                value={formData.nombre}
                onChange={(e) => handleInputChange("nombre", e.target.value)}
                placeholder="Ej: Juan Pérez García"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="correo">Correo Electrónico *</Label>
              <Input
                id="correo"
                type="email"
                value={formData.correo}
                onChange={(e) => handleInputChange("correo", e.target.value)}
                placeholder="nombre@gobierno.gob.mx"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="cuentaDominio">Cuenta de Dominio</Label>
              <Input
                id="cuentaDominio"
                value={formData.cuentaDominio}
                onChange={(e) => handleInputChange("cuentaDominio", e.target.value)}
                placeholder="Ej: jperez"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="departamento">Departamento</Label>
              <Input
                id="departamento"
                value={formData.departamento}
                onChange={(e) => handleInputChange("departamento", e.target.value)}
                placeholder="Ej: Tecnologías de la Información"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="puesto">Puesto</Label>
              <Input
                id="puesto"
                value={formData.puesto}
                onChange={(e) => handleInputChange("puesto", e.target.value)}
                placeholder="Ej: Analista de Sistemas"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="extension">Extensión</Label>
              <Input
                id="extension"
                value={formData.extension}
                onChange={(e) => handleInputChange("extension", e.target.value)}
                placeholder="Ej: 1234"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="segmento">Segmento de Red</Label>
              <Select
                value={formData.segmento}
                onValueChange={(value) => handleInputChange("segmento", value)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Seleccione un segmento" />
                </SelectTrigger>
                <SelectContent>
                  {segmentosRed.map((segmento) => (
                    <SelectItem key={segmento.id} value={segmento.nombre}>
                      {segmento.nombre} - {segmento.piso}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="direccion">Dirección</Label>
              <Input
                id="direccion"
                value={formData.direccion}
                onChange={(e) => handleInputChange("direccion", e.target.value)}
                placeholder="Ej: Av. Insurgentes Sur 1234"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Sección: Equipos Asignados */}
      {equiposAsignados.length > 0 && (
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Equipos Asignados</CardTitle>
                <CardDescription>
                  Equipos asignados para uso del personal
                </CardDescription>
              </div>
              <Button 
                onClick={handleOpenAsignarDialog}
                className="flex items-center gap-2"
              >
                <Plus className="w-4 h-4" />
                Añadir Equipo
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {renderEquiposList(equiposAsignados, "asignado")}
          </CardContent>
        </Card>
      )}

      {/* Sección: Equipos Responsables */}
      {equiposResponsables.length > 0 && (
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Equipos Responsables</CardTitle>
                <CardDescription>
                  Equipos bajo la responsabilidad del personal
                </CardDescription>
              </div>
              <Button 
                onClick={handleOpenAsignarDialog}
                className="flex items-center gap-2"
              >
                <Plus className="w-4 h-4" />
                Añadir Equipo
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {renderEquiposList(equiposResponsables, "responsable")}
          </CardContent>
        </Card>
      )}

      {/* Si no hay equipos */}
      {equiposAsignados.length === 0 && equiposResponsables.length === 0 && (
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Equipos</CardTitle>
                <CardDescription>
                  No hay equipos asignados a este personal
                </CardDescription>
              </div>
              <Button 
                onClick={handleOpenAsignarDialog}
                className="flex items-center gap-2"
              >
                <Plus className="w-4 h-4" />
                Añadir Equipo
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div className="text-center py-8 text-muted-foreground">
              <Monitor className="w-12 h-12 mx-auto mb-3 opacity-50" />
              <p>No hay dispositivos asignados</p>
              <p className="text-sm">Haga clic en "Añadir Equipo" para comenzar</p>
            </div>
          </CardContent>
        </Card>
      )}

      <EquipoModal
        isOpen={showAsignarDialog}
        onClose={() => setShowAsignarDialog(false)}
        onConfirm={handleAsignarEquipo}
        title="Asignar Dispositivo"
        isEditMode={false}
      />

      <EquipoModal
        isOpen={showEditarDialog}
        onClose={() => {
          setShowEditarDialog(false);
          setEditingEquipoIndex(null);
        }}
        onConfirm={handleActualizarEquipo}
        title="Editar Dispositivo"
        isEditMode={true}
      />

      <div className="flex justify-end gap-4">
        <Button variant="outline" onClick={() => navigate("/personal-operativo")}>
          Cancelar
        </Button>
        <Button onClick={handleSubmit} className="flex items-center gap-2">
          <Save className="w-4 h-4" />
          Actualizar Personal
        </Button>
      </div>
    </div>
  );
}
