import React, { createContext, useContext, useState } from "react";

// Tipos de datos
export interface PersonalOperativo {
  id: string;
  numeroCobro: string;
  nombre: string;
  direccion: string;
  correo: string;
  puesto: string;
  departamento: string;
  extension: string;
  cuentaDominio: string;
  segmento: string;
  equiposAsignados: EquipoAsignado[];
}

export interface EquipoAsignado {
  id: string;
  equipoId: string; // Referencia al equipo global
  ip: string;
  notas: string;
  fechaAsignacion: string;
  servicios: ServicioAsignado[];
  tipoAsignacion: "asignado" | "responsable"; // Nuevo campo
}

export interface EquipoGlobal {
  id: string;
  tipo: "Desktop" | "Laptop" | "Impresora" | "Otro";
  marca: string;
  modelo: string;
  serie: string;
  mac?: string;
  ipsDisponibles: string[];
  asignadoA: string | null; // ID del PersonalOperativo asignado
  responsableId: string | null; // ID del PersonalOperativo responsable (puede ser diferente)
  estado: "Disponible" | "Asignado" | "En Mantenimiento" | "Dado de Baja";
}

export interface Equipo {
  id: string;
  tipo: "Desktop" | "Laptop" | "Impresora" | "Otro";
  marca: string;
  modelo: string;
  serie: string;
  mac?: string;
  ip?: string;
  servicios: ServicioAsignado[];
}

export interface ServicioAsignado {
  id: string;
  servicioId: string;
  version: string;
  fechaInstalacion: string;
}

export interface Servicio {
  id: string;
  nombre: string;
  categoria: "Sistema Operativo" | "Programa" | "Driver" | "Otro";
  versiones: string[];
  versionVigente: string;
}

export interface SegmentoRed {
  id: string;
  nombre: string;
  piso: string;
  rangoInicio: string;
  rangoFin: string;
  ipsAsignadas: IPAsignada[];
}

export interface IPAsignada {
  ip: string;
  mac: string;
  responsableId: string;
  responsableNombre: string;
  estado: "Operando" | "Disponible";
}

export interface Usuario {
  id: string;
  username: string;
  password: string;
  rol: string;
  permisos: {
    gestionPersonal: boolean;
    configuracionGlobal: boolean;
    consultas: boolean;
    usuarios: boolean;
  };
}

interface DataContextType {
  personalOperativo: PersonalOperativo[];
  servicios: Servicio[];
  segmentosRed: SegmentoRed[];
  usuarios: Usuario[];
  equiposGlobales: EquipoGlobal[];
  
  // Personal Operativo
  addPersonalOperativo: (personal: Omit<PersonalOperativo, "id">) => void;
  updatePersonalOperativo: (id: string, personal: Partial<PersonalOperativo>) => void;
  deletePersonalOperativo: (id: string) => void;
  getPersonalOperativoById: (id: string) => PersonalOperativo | undefined;
  
  // Equipos Globales
  addEquipoGlobal: (equipo: Omit<EquipoGlobal, "id">) => void;
  updateEquipoGlobal: (id: string, equipo: Partial<EquipoGlobal>) => void;
  asignarEquipoAPersonal: (equipoId: string, personalId: string, ip: string, notas: string) => void;
  
  // Equipos (legacy - para compatibilidad)
  addEquipoToPersonal: (personalId: string, equipo: Omit<Equipo, "id">) => void;
  updateEquipo: (personalId: string, equipoId: string, equipo: Partial<Equipo>) => void;
  cambiarResponsable: (equipoId: string, oldPersonalId: string, newPersonalId: string) => void;
  
  // Servicios
  addServicio: (servicio: Omit<Servicio, "id">) => void;
  updateServicio: (id: string, servicio: Partial<Servicio>) => void;
  deleteServicio: (id: string) => void;
  
  // Segmentos de Red
  addSegmentoRed: (segmento: Omit<SegmentoRed, "id">) => void;
  updateSegmentoRed: (id: string, segmento: Partial<SegmentoRed>) => void;
  
  // Usuarios
  addUsuario: (usuario: Omit<Usuario, "id">) => void;
  updateUsuario: (id: string, usuario: Partial<Usuario>) => void;
  deleteUsuario: (id: string) => void;
}

const DataContext = createContext<DataContextType | undefined>(undefined);

// Datos iniciales
const INITIAL_SERVICIOS: Servicio[] = [
  {
    id: "1",
    nombre: "Windows",
    categoria: "Sistema Operativo",
    versiones: ["10", "11"],
    versionVigente: "11",
  },
  {
    id: "2",
    nombre: "Microsoft Office",
    categoria: "Programa",
    versiones: ["2019", "2021", "365"],
    versionVigente: "365",
  },
  {
    id: "3",
    nombre: "WinRAR",
    categoria: "Programa",
    versiones: ["6.0", "6.11", "6.24"],
    versionVigente: "6.24",
  },
  {
    id: "4",
    nombre: "Adobe Reader",
    categoria: "Programa",
    versiones: ["DC 2020", "DC 2023"],
    versionVigente: "DC 2023",
  },
];

const INITIAL_SEGMENTOS: SegmentoRed[] = [
  {
    id: "1",
    nombre: "Segmento A - Administración",
    piso: "Piso 1",
    rangoInicio: "192.168.1.1",
    rangoFin: "192.168.1.50",
    ipsAsignadas: [],
  },
  {
    id: "2",
    nombre: "Segmento B - Finanzas",
    piso: "Piso 2",
    rangoInicio: "192.168.2.1",
    rangoFin: "192.168.2.50",
    ipsAsignadas: [],
  },
  {
    id: "3",
    nombre: "Segmento C - Recursos Humanos",
    piso: "Piso 3",
    rangoInicio: "192.168.3.1",
    rangoFin: "192.168.3.50",
    ipsAsignadas: [],
  },
];

const INITIAL_EQUIPOS_GLOBALES: EquipoGlobal[] = [
  {
    id: "eg1",
    tipo: "Laptop",
    marca: "Dell",
    modelo: "Latitude 5420",
    serie: "DL1234567",
    mac: "00:1B:44:11:3A:B7",
    ipsDisponibles: ["192.168.1.10", "192.168.1.11", "192.168.1.12"],
    asignadoA: "1",
    responsableId: "1",
    estado: "Asignado",
  },
  {
    id: "eg2",
    tipo: "Desktop",
    marca: "HP",
    modelo: "ProDesk 600",
    serie: "HP9876543",
    mac: "00:1B:44:22:3A:C8",
    ipsDisponibles: ["192.168.2.15", "192.168.2.16"],
    asignadoA: "2",
    responsableId: "2",
    estado: "Asignado",
  },
  {
    id: "eg3",
    tipo: "Laptop",
    marca: "Lenovo",
    modelo: "ThinkPad X1",
    serie: "LN5567890",
    mac: "00:1B:44:33:3A:D9",
    ipsDisponibles: ["192.168.1.20", "192.168.1.21", "192.168.1.22"],
    asignadoA: null,
    responsableId: null,
    estado: "Disponible",
  },
  {
    id: "eg4",
    tipo: "Desktop",
    marca: "Dell",
    modelo: "OptiPlex 7090",
    serie: "DL9988776",
    mac: "00:1B:44:44:3A:E0",
    ipsDisponibles: ["192.168.3.10", "192.168.3.11"],
    asignadoA: null,
    responsableId: null,
    estado: "Disponible",
  },
  {
    id: "eg5",
    tipo: "Impresora",
    marca: "HP",
    modelo: "LaserJet Pro M404",
    serie: "HP1122334",
    mac: "00:1B:44:55:3A:F1",
    ipsDisponibles: ["192.168.1.100", "192.168.2.100"],
    asignadoA: null,
    responsableId: "1",
    estado: "Asignado",
  },
];

const INITIAL_PERSONAL: PersonalOperativo[] = [
  {
    id: "1",
    numeroCobro: "001234",
    nombre: "Juan Pérez García",
    direccion: "Av. Insurgentes Sur 1234",
    correo: "juan.perez@gobierno.gob.mx",
    puesto: "Analista de Sistemas",
    departamento: "Tecnologías de la Información",
    extension: "1234",
    cuentaDominio: "jperez",
    segmento: "Segmento A - Administración",
    equiposAsignados: [
      {
        id: "ea1",
        equipoId: "eg1",
        ip: "192.168.1.10",
        notas: "Equipo principal de trabajo",
        fechaAsignacion: "2024-01-15",
        servicios: [
          {
            id: "sa1",
            servicioId: "1",
            version: "11",
            fechaInstalacion: "2024-01-15",
          },
          {
            id: "sa2",
            servicioId: "2",
            version: "365",
            fechaInstalacion: "2024-01-15",
          },
        ],
        tipoAsignacion: "asignado",
      },
      {
        id: "ea2",
        equipoId: "eg5",
        ip: "192.168.1.100",
        notas: "Impresora compartida del área",
        fechaAsignacion: "2024-02-10",
        servicios: [],
        tipoAsignacion: "responsable",
      },
    ],
  },
  {
    id: "2",
    numeroCobro: "002345",
    nombre: "María López Hernández",
    direccion: "Calle Reforma 456",
    correo: "maria.lopez@gobierno.gob.mx",
    puesto: "Directora de Finanzas",
    departamento: "Finanzas",
    extension: "2345",
    cuentaDominio: "mlopez",
    segmento: "Segmento B - Finanzas",
    equiposAsignados: [
      {
        id: "ea2",
        equipoId: "eg2",
        ip: "192.168.2.15",
        notas: "Equipo de escritorio principal",
        fechaAsignacion: "2024-02-01",
        servicios: [
          {
            id: "sa3",
            servicioId: "1",
            version: "11",
            fechaInstalacion: "2024-02-01",
          },
        ],
        tipoAsignacion: "asignado", // Nuevo campo
      },
    ],
  },
];

export function DataProvider({ children }: { children: React.ReactNode }) {
  const [personalOperativo, setPersonalOperativo] = useState<PersonalOperativo[]>(INITIAL_PERSONAL);
  const [servicios, setServicios] = useState<Servicio[]>(INITIAL_SERVICIOS);
  const [segmentosRed, setSegmentosRed] = useState<SegmentoRed[]>(INITIAL_SEGMENTOS);
  const [usuarios, setUsuarios] = useState<Usuario[]>([]);
  const [equiposGlobales, setEquiposGlobales] = useState<EquipoGlobal[]>(INITIAL_EQUIPOS_GLOBALES);

  // Personal Operativo
  const addPersonalOperativo = (personal: Omit<PersonalOperativo, "id">) => {
    const newPersonal: PersonalOperativo = {
      ...personal,
      id: Date.now().toString(),
    };
    setPersonalOperativo([...personalOperativo, newPersonal]);
  };

  const updatePersonalOperativo = (id: string, personal: Partial<PersonalOperativo>) => {
    setPersonalOperativo(
      personalOperativo.map((p) => (p.id === id ? { ...p, ...personal } : p))
    );
  };

  const deletePersonalOperativo = (id: string) => {
    const personal = personalOperativo.find((p) => p.id === id);
    if (!personal) return;

    // Verificar que no tenga equipos asignados (RN-01)
    if (personal.equiposAsignados && personal.equiposAsignados.length > 0) {
      throw new Error("No se puede eliminar personal con equipos asignados (RN-01)");
    }

    setPersonalOperativo(personalOperativo.filter((p) => p.id !== id));
  };

  const getPersonalOperativoById = (id: string) => {
    return personalOperativo.find((p) => p.id === id);
  };

  // Equipos Globales
  const addEquipoGlobal = (equipo: Omit<EquipoGlobal, "id">) => {
    const newEquipo: EquipoGlobal = {
      ...equipo,
      id: Date.now().toString(),
    };
    setEquiposGlobales([...equiposGlobales, newEquipo]);
  };

  const updateEquipoGlobal = (id: string, equipo: Partial<EquipoGlobal>) => {
    setEquiposGlobales(equiposGlobales.map((e) => (e.id === id ? { ...e, ...equipo } : e)));
  };

  const asignarEquipoAPersonal = (equipoId: string, personalId: string, ip: string, notas: string) => {
    // Actualizar el equipo global
    setEquiposGlobales(
      equiposGlobales.map((e) =>
        e.id === equipoId ? { ...e, asignadoA: personalId, estado: "Asignado" as const } : e
      )
    );

    // Agregar la asignación al personal
    const newAsignacion: EquipoAsignado = {
      id: Date.now().toString(),
      equipoId,
      ip,
      notas,
      fechaAsignacion: new Date().toISOString().split("T")[0],
      servicios: [],
      tipoAsignacion: "asignado", // Nuevo campo
    };

    setPersonalOperativo(
      personalOperativo.map((p) =>
        p.id === personalId
          ? { ...p, equiposAsignados: [...p.equiposAsignados, newAsignacion] }
          : p
      )
    );
  };

  // Equipos (legacy - para compatibilidad)
  const addEquipoToPersonal = (personalId: string, equipo: Omit<Equipo, "id">) => {
    const newEquipo: Equipo = {
      ...equipo,
      id: Date.now().toString(),
    };
    setPersonalOperativo(
      personalOperativo.map((p) =>
        p.id === personalId ? { ...p, equiposAsignados: [...p.equiposAsignados, newEquipo] } : p
      )
    );
  };

  const updateEquipo = (personalId: string, equipoId: string, equipo: Partial<Equipo>) => {
    setPersonalOperativo(
      personalOperativo.map((p) =>
        p.id === personalId
          ? {
              ...p,
              equiposAsignados: p.equiposAsignados.map((e) => (e.id === equipoId ? { ...e, ...equipo } : e)),
            }
          : p
      )
    );
  };

  const cambiarResponsable = (equipoId: string, oldPersonalId: string, newPersonalId: string) => {
    const oldPersonal = personalOperativo.find((p) => p.id === oldPersonalId);
    if (!oldPersonal) return;

    // Buscar la asignación del equipo (equipoId es el ID de la asignación, no del equipo global)
    const asignacion = oldPersonal.equiposAsignados.find((e) => e.id === equipoId);
    if (!asignacion) return;

    // Actualizar el responsableId en el equipo global
    setEquiposGlobales(
      equiposGlobales.map((e) =>
        e.id === asignacion.equipoId ? { ...e, responsableId: newPersonalId, asignadoA: newPersonalId } : e
      )
    );

    // Remover la asignación del responsable anterior y agregarlo al nuevo
    setPersonalOperativo(
      personalOperativo.map((p) => {
        if (p.id === oldPersonalId) {
          return { ...p, equiposAsignados: p.equiposAsignados.filter((e) => e.id !== equipoId) };
        }
        if (p.id === newPersonalId) {
          return { ...p, equiposAsignados: [...p.equiposAsignados, asignacion] };
        }
        return p;
      })
    );
  };

  // Servicios
  const addServicio = (servicio: Omit<Servicio, "id">) => {
    const newServicio: Servicio = {
      ...servicio,
      id: Date.now().toString(),
    };
    setServicios([...servicios, newServicio]);
  };

  const updateServicio = (id: string, servicio: Partial<Servicio>) => {
    setServicios(servicios.map((s) => (s.id === id ? { ...s, ...servicio } : s)));
  };

  const deleteServicio = (id: string) => {
    setServicios(servicios.filter((s) => s.id !== id));
  };

  // Segmentos de Red
  const addSegmentoRed = (segmento: Omit<SegmentoRed, "id">) => {
    const newSegmento: SegmentoRed = {
      ...segmento,
      id: Date.now().toString(),
    };
    setSegmentosRed([...segmentosRed, newSegmento]);
  };

  const updateSegmentoRed = (id: string, segmento: Partial<SegmentoRed>) => {
    setSegmentosRed(segmentosRed.map((s) => (s.id === id ? { ...s, ...segmento } : s)));
  };

  // Usuarios
  const addUsuario = (usuario: Omit<Usuario, "id">) => {
    const newUsuario: Usuario = {
      ...usuario,
      id: Date.now().toString(),
    };
    setUsuarios([...usuarios, newUsuario]);
  };

  const updateUsuario = (id: string, usuario: Partial<Usuario>) => {
    setUsuarios(usuarios.map((u) => (u.id === id ? { ...u, ...usuario } : u)));
  };

  const deleteUsuario = (id: string) => {
    setUsuarios(usuarios.filter((u) => u.id !== id));
  };

  return (
    <DataContext.Provider
      value={{
        personalOperativo,
        servicios,
        segmentosRed,
        usuarios,
        equiposGlobales,
        addPersonalOperativo,
        updatePersonalOperativo,
        deletePersonalOperativo,
        getPersonalOperativoById,
        addEquipoGlobal,
        updateEquipoGlobal,
        asignarEquipoAPersonal,
        addEquipoToPersonal,
        updateEquipo,
        cambiarResponsable,
        addServicio,
        updateServicio,
        deleteServicio,
        addSegmentoRed,
        updateSegmentoRed,
        addUsuario,
        updateUsuario,
        deleteUsuario,
      }}
    >
      {children}
    </DataContext.Provider>
  );
}

export function useData() {
  const context = useContext(DataContext);
  if (context === undefined) {
    throw new Error("useData must be used within a DataProvider");
  }
  return context;
}