import { useState } from "react";
import { useData, Usuario as UsuarioType } from "../context/DataContext";
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
import { Checkbox } from "../components/ui/checkbox";
import { Plus, Edit, Trash2, Shield, ShieldCheck } from "lucide-react";
import { toast } from "sonner";

export function Usuarios() {
  const { usuarios, addUsuario, updateUsuario, deleteUsuario } = useData();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingUsuario, setEditingUsuario] = useState<UsuarioType | null>(null);
  const [formData, setFormData] = useState({
    username: "",
    password: "",
    rol: "",
    permisos: {
      gestionPersonal: true,
      configuracionGlobal: true,
      consultas: true,
      usuarios: false,
    },
  });

  // Roles predefinidos para el combobox
  const rolesPredef = ["Administrador", "Usuario", "Técnico", "Supervisor", "Operador"];

  const handleOpenDialog = (usuario?: UsuarioType) => {
    if (usuario) {
      setEditingUsuario(usuario);
      setFormData({
        username: usuario.username,
        password: usuario.password, // Mantener contraseña actual
        rol: usuario.rol,
        permisos: { ...usuario.permisos },
      });
    } else {
      setEditingUsuario(null);
      setFormData({
        username: "",
        password: "",
        rol: "Usuario",
        permisos: {
          gestionPersonal: true,
          configuracionGlobal: true,
          consultas: false,
          usuarios: false,
        },
      });
    }
    setIsDialogOpen(true);
  };

  const handleSubmit = () => {
    if (!formData.username || !formData.rol) {
      toast.error("Complete los campos obligatorios");
      return;
    }

    if (!editingUsuario && !formData.password) {
      toast.error("La contraseña es obligatoria para nuevos usuarios");
      return;
    }

    if (editingUsuario) {
      updateUsuario(editingUsuario.id, formData);
      toast.success("Usuario actualizado correctamente");
    } else {
      addUsuario(formData);
      toast.success("Usuario creado correctamente");
    }

    setIsDialogOpen(false);
  };

  const handleDelete = (id: string) => {
    deleteUsuario(id);
    toast.success("Usuario eliminado correctamente");
  };

  const handleRolChange = (rol: string) => {
    if (rol === "Administrador") {
      setFormData({
        ...formData,
        rol,
        permisos: {
          gestionPersonal: true,
          configuracionGlobal: true,
          consultas: true,
          usuarios: true,
        },
      });
    } else {
      setFormData({
        ...formData,
        rol,
        permisos: {
          ...formData.permisos,
          usuarios: false,
        },
      });
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Administración de Usuarios</h1>
          <p className="text-muted-foreground mt-1">
            Gestión de cuentas y permisos del sistema (RBAC)
          </p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild>
            <Button onClick={() => handleOpenDialog()} className="flex items-center gap-2">
              <Plus className="w-4 h-4" />
              Nuevo Usuario
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-lg">
            <DialogHeader>
              <DialogTitle>
                {editingUsuario ? "Editar Usuario" : "Nuevo Usuario"}
              </DialogTitle>
              <DialogDescription>
                {editingUsuario
                  ? "Actualice la información y permisos del usuario"
                  : "Cree una nueva cuenta de usuario del sistema"}
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <Label htmlFor="username">Nombre de Usuario *</Label>
                <Input
                  id="username"
                  value={formData.username}
                  onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                  placeholder="Ej: jperez"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="password">Contraseña *</Label>
                <Input
                  id="password"
                  type="password"
                  value={formData.password}
                  onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                  placeholder={editingUsuario ? "Ingrese nueva contraseña para cambiarla" : "Ej: 123456"}
                />
                {editingUsuario && (
                  <p className="text-xs text-muted-foreground">
                    Modifique este campo solo si desea actualizar la contraseña del usuario
                  </p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="rol">Rol *</Label>
                <div className="relative">
                  <Input
                    id="rol"
                    value={formData.rol}
                    onChange={(e) => handleRolChange(e.target.value)}
                    placeholder="Seleccione o escriba un rol"
                    list="roles-list"
                  />
                  <datalist id="roles-list">
                    {rolesPredef.map((rol) => (
                      <option key={rol} value={rol} />
                    ))}
                  </datalist>
                </div>
                <p className="text-xs text-muted-foreground">
                  Seleccione de la lista o escriba un rol personalizado
                </p>
              </div>

              <div className="space-y-3">
                <Label>Permisos del Sistema</Label>
                <div className="space-y-2 pl-2">
                  <div className="flex items-center space-x-2">
                    <Checkbox
                      id="gestionPersonal"
                      checked={formData.permisos.gestionPersonal}
                      onCheckedChange={(checked) =>
                        setFormData({
                          ...formData,
                          permisos: {
                            ...formData.permisos,
                            gestionPersonal: checked as boolean,
                          },
                        })
                      }
                      disabled={formData.rol === "Administrador"}
                    />
                    <Label htmlFor="gestionPersonal" className="cursor-pointer text-sm">
                      Gestión de Personal Operativo
                    </Label>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Checkbox
                      id="configuracionGlobal"
                      checked={formData.permisos.configuracionGlobal}
                      onCheckedChange={(checked) =>
                        setFormData({
                          ...formData,
                          permisos: {
                            ...formData.permisos,
                            configuracionGlobal: checked as boolean,
                          },
                        })
                      }
                      disabled={formData.rol === "Administrador"}
                    />
                    <Label htmlFor="configuracionGlobal" className="cursor-pointer text-sm">
                      Configuración Global
                    </Label>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Checkbox
                      id="consultas"
                      checked={formData.permisos.consultas}
                      onCheckedChange={(checked) =>
                        setFormData({
                          ...formData,
                          permisos: { ...formData.permisos, consultas: checked as boolean },
                        })
                      }
                      disabled={formData.rol === "Administrador"}
                    />
                    <Label htmlFor="consultas" className="cursor-pointer text-sm">
                      Consultas y Auditoría
                    </Label>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Checkbox
                      id="usuarios"
                      checked={formData.permisos.usuarios}
                      onCheckedChange={(checked) =>
                        setFormData({
                          ...formData,
                          permisos: { ...formData.permisos, usuarios: checked as boolean },
                        })
                      }
                      disabled={formData.rol === "Administrador"}
                    />
                    <Label htmlFor="usuarios" className="cursor-pointer text-sm">
                      Administración de Usuarios
                    </Label>
                  </div>
                </div>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setIsDialogOpen(false)}>
                Cancelar
              </Button>
              <Button onClick={handleSubmit}>
                {editingUsuario ? "Actualizar" : "Crear"} Usuario
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Usuarios del Sistema</CardTitle>
          <CardDescription>
            {usuarios.length} usuarios registrados (más 2 usuarios predefinidos)
          </CardDescription>
        </CardHeader>
        <CardContent>
          {usuarios.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <Shield className="w-12 h-12 mx-auto mb-3 opacity-50" />
              <p>No hay usuarios adicionales registrados</p>
              <p className="text-sm mt-1">Los usuarios admin y tecnico son predefinidos</p>
            </div>
          ) : (
            <div className="border rounded-lg">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Usuario</TableHead>
                    <TableHead>Rol</TableHead>
                    <TableHead>Permisos</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {usuarios.map((usuario) => (
                    <TableRow key={usuario.id}>
                      <TableCell className="font-medium">{usuario.username}</TableCell>
                      <TableCell>
                        <Badge
                          variant={
                            usuario.rol === "Administrador" ? "default" : "secondary"
                          }
                          className={
                            usuario.rol === "Administrador"
                              ? "bg-primary text-primary-foreground"
                              : ""
                          }
                        >
                          {usuario.rol === "Administrador" ? (
                            <ShieldCheck className="w-3 h-3 mr-1" />
                          ) : (
                            <Shield className="w-3 h-3 mr-1" />
                          )}
                          {usuario.rol}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <div className="flex flex-wrap gap-1">
                          {usuario.permisos.gestionPersonal && (
                            <Badge variant="outline" className="text-xs">
                              Personal
                            </Badge>
                          )}
                          {usuario.permisos.configuracionGlobal && (
                            <Badge variant="outline" className="text-xs">
                              Config
                            </Badge>
                          )}
                          {usuario.permisos.consultas && (
                            <Badge variant="outline" className="text-xs">
                              Consultas
                            </Badge>
                          )}
                          {usuario.permisos.usuarios && (
                            <Badge variant="outline" className="text-xs">
                              Usuarios
                            </Badge>
                          )}
                        </div>
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-2">
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleOpenDialog(usuario)}
                          >
                            <Edit className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDelete(usuario.id)}
                          >
                            <Trash2 className="w-4 h-4 text-destructive" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}

          <div className="mt-6 p-4 bg-muted rounded-lg">
            <h3 className="font-semibold mb-2 flex items-center gap-2">
              <ShieldCheck className="w-4 h-4" />
              Usuarios Predefinidos del Sistema
            </h3>
            <div className="space-y-2 text-sm">
              <div className="flex items-center justify-between py-2 border-b">
                <div>
                  <p className="font-medium">admin</p>
                  <p className="text-xs text-muted-foreground">Administrador del Sistema</p>
                </div>
                <Badge className="bg-primary">Administrador</Badge>
              </div>
              <div className="flex items-center justify-between py-2">
                <div>
                  <p className="font-medium">tecnico</p>
                  <p className="text-xs text-muted-foreground">Técnico de Soporte</p>
                </div>
                <Badge variant="secondary">Usuario</Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}