interface TerminologyModalProps {
  open: boolean;
  onClose: () => void;
  department: string;
}

interface Terminology {
  term: string;
  definition: string;
}

const GENERAL_TERMINOLOGIES: Terminology[] = [
  {
    term: "INEA",
    definition: "Instituto Nacional para la Educación de los Adultos. Organismo descentralizado de la Administración Pública Federal que tiene como objetivo promover, organizar e impartir educación básica para adultos.",
  },
  {
    term: "Usuario",
    definition: "Persona registrada en el sistema con acceso a las funcionalidades según su departamento y permisos asignados.",
  },
  {
    term: "Departamento",
    definition: "Unidad organizacional dentro del INEA que agrupa personal con funciones y responsabilidades específicas.",
  },
  {
    term: "MEVyT",
    definition: "Modelo de Educación para la Vida y el Trabajo. Sistema modular que permite a las personas adultas iniciar o continuar su educación básica.",
  },
  {
    term: "Alfabetización",
    definition: "Proceso de aprendizaje de lectura, escritura y cálculo básico para personas adultas.",
  },
];

const DEPARTMENT_TERMINOLOGIES: Record<string, Terminology[]> = {
  Administración: [
    {
      term: "Gestión Administrativa",
      definition: "Conjunto de procesos y actividades relacionados con la planificación, organización, dirección y control de recursos institucionales.",
    },
    {
      term: "Recursos Materiales",
      definition: "Bienes muebles e inmuebles, así como insumos necesarios para el funcionamiento de la institución.",
    },
    {
      term: "Patrimonio",
      definition: "Conjunto de bienes y derechos que pertenecen al INEA y que se utilizan para cumplir con sus objetivos institucionales.",
    },
    {
      term: "Inventario",
      definition: "Registro detallado de los bienes muebles e inmuebles propiedad del instituto.",
    },
  ],
  "Recursos Humanos": [
    {
      term: "Nómina",
      definition: "Documento que contiene el registro de los sueldos y salarios que se pagan al personal del INEA.",
    },
    {
      term: "Prestaciones",
      definition: "Beneficios económicos y en especie otorgados al personal además del salario base.",
    },
    {
      term: "Capacitación",
      definition: "Proceso de formación continua del personal para mejorar sus competencias y habilidades laborales.",
    },
    {
      term: "Evaluación del Desempeño",
      definition: "Proceso sistemático para valorar el rendimiento y productividad del personal en sus funciones.",
    },
  ],
  Tecnología: [
    {
      term: "SASA",
      definition: "Sistema Automatizado de Seguimiento y Acreditación. Plataforma tecnológica para el registro y seguimiento de educandos.",
    },
    {
      term: "Base de Datos",
      definition: "Sistema organizado de almacenamiento de información que permite consultar, actualizar y administrar datos institucionales.",
    },
    {
      term: "Seguridad Informática",
      definition: "Conjunto de medidas y protocolos para proteger los sistemas de información contra accesos no autorizados.",
    },
    {
      term: "Respaldo",
      definition: "Copia de seguridad de la información crítica del sistema para prevenir pérdida de datos.",
    },
  ],
  Operaciones: [
    {
      term: "Plaza Comunitaria",
      definition: "Espacio educativo donde se ofrecen servicios de alfabetización, educación primaria y secundaria para adultos.",
    },
    {
      term: "Asesor Educativo",
      definition: "Persona que guía y apoya el proceso de aprendizaje de los educandos en el MEVyT.",
    },
    {
      term: "Módulo",
      definition: "Unidad de estudio del MEVyT que aborda temas específicos y tiene una duración determinada.",
    },
    {
      term: "Acreditación",
      definition: "Proceso mediante el cual se certifican los conocimientos adquiridos por los educandos.",
    },
  ],
  Finanzas: [
    {
      term: "Presupuesto",
      definition: "Plan financiero que establece los recursos económicos asignados al INEA para un periodo determinado.",
    },
    {
      term: "Partida Presupuestal",
      definition: "Clasificación de los gastos según su naturaleza y destino dentro del presupuesto institucional.",
    },
    {
      term: "Comprobación de Gastos",
      definition: "Proceso de validación de que los recursos fueron ejercidos conforme a la normatividad aplicable.",
    },
    {
      term: "Auditoría",
      definition: "Revisión sistemática de las operaciones financieras y administrativas para verificar su correcta aplicación.",
    },
  ],
  Marketing: [
    {
      term: "Campaña de Difusión",
      definition: "Estrategia de comunicación para dar a conocer los servicios educativos del INEA a la población objetivo.",
    },
    {
      term: "Público Objetivo",
      definition: "Personas de 15 años o más sin educación básica terminada a quienes se dirigen los servicios del INEA.",
    },
    {
      term: "Material Promocional",
      definition: "Recursos gráficos y audiovisuales utilizados para promover los servicios educativos del instituto.",
    },
    {
      term: "Imagen Institucional",
      definition: "Conjunto de elementos visuales y conceptuales que representan e identifican al INEA ante la sociedad.",
    },
  ],
};

export function TerminologyModal({ open, onClose, department }: TerminologyModalProps) {
  if (!open) return null;

  const isGeneral = department === "Todos";
  const terminologies = isGeneral
    ? GENERAL_TERMINOLOGIES
    : DEPARTMENT_TERMINOLOGIES[department] || [];

  return (
    <>
      <div className="modal fade in" style={{ display: "block" }} role="dialog">
        <div className="modal-dialog modal-lg">
          <div className="modal-content">
            <div className="modal-header">
              <button type="button" className="close" onClick={onClose}>
                <span>&times;</span>
              </button>
              <h4 className="modal-title">
                <span className="glyphicon glyphicon-book"></span>{" "}
                {isGeneral
                  ? "Terminologías Generales"
                  : `Terminologías - ${department}`}
              </h4>
            </div>
            <div className="modal-body" style={{ maxHeight: "500px", overflowY: "auto" }}>
              {terminologies.length === 0 ? (
                <div className="alert alert-info">
                  <p>No hay terminologías disponibles para este departamento.</p>
                </div>
              ) : (
                <div className="panel-group" id="terminology-accordion">
                  {terminologies.map((item, index) => (
                    <div key={index} className="panel panel-default">
                      <div className="panel-heading">
                        <h4 className="panel-title">
                          <a
                            data-toggle="collapse"
                            data-parent="#terminology-accordion"
                            href={`#term-${index}`}
                            style={{ display: "block", textDecoration: "none" }}
                          >
                            <span className="glyphicon glyphicon-menu-right" style={{ marginRight: "10px", fontSize: "12px" }}></span>
                            <strong>{item.term}</strong>
                          </a>
                        </h4>
                      </div>
                      <div id={`term-${index}`} className="panel-collapse collapse">
                        <div className="panel-body">
                          <p style={{ margin: 0, lineHeight: "1.6" }}>{item.definition}</p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}

              {!isGeneral && (
                <div style={{ marginTop: "20px", padding: "15px", backgroundColor: "#f6f6f6", borderRadius: "4px" }}>
                  <p style={{ margin: 0, fontSize: "14px", color: "#666" }}>
                    <span className="glyphicon glyphicon-info-sign" style={{ marginRight: "8px" }}></span>
                    <strong>Nota:</strong> Estas terminologías son específicas del departamento de {department}.
                    Para ver terminologías generales del INEA, seleccione "Todos" en el filtro de departamentos.
                  </p>
                </div>
              )}
            </div>
            <div className="modal-footer">
              <button type="button" className="btn btn-default" onClick={onClose}>
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
      <div className="modal-backdrop fade in" onClick={onClose}></div>
    </>
  );
}
