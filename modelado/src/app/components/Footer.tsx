import { Link } from "react-router";

export function Footer() {
  return (
    <footer className="bg-[#691B32] text-white mt-auto">
      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div>
            <h3 className="font-semibold mb-3 text-white">INEA</h3>
            <ul className="space-y-2 text-sm">
              <li>
                <a href="https://www.gob.mx/inea" target="_blank" rel="noopener noreferrer" className="hover:underline">
                  Portal INEA
                </a>
              </li>
              <li>
                <a href="https://www.gob.mx/inea/acciones-y-programas" target="_blank" rel="noopener noreferrer" className="hover:underline">
                  Programas
                </a>
              </li>
              <li>
                <a href="https://www.gob.mx/inea/documentos" target="_blank" rel="noopener noreferrer" className="hover:underline">
                  Documentos
                </a>
              </li>
            </ul>
          </div>
          
          <div>
            <h3 className="font-semibold mb-3 text-white">Enlaces</h3>
            <ul className="space-y-2 text-sm">
              <li>
                <a href="https://www.gob.mx" target="_blank" rel="noopener noreferrer" className="hover:underline">
                  gob.mx
                </a>
              </li>
              <li>
                <a href="https://datos.gob.mx" target="_blank" rel="noopener noreferrer" className="hover:underline">
                  Datos Abiertos
                </a>
              </li>
              <li>
                <a href="https://www.gob.mx/tramites" target="_blank" rel="noopener noreferrer" className="hover:underline">
                  Trámites
                </a>
              </li>
            </ul>
          </div>
          
          <div>
            <h3 className="font-semibold mb-3 text-white">Información</h3>
            <ul className="space-y-2 text-sm">
              <li>
                <a href="#" className="hover:underline">
                  Aviso de Privacidad
                </a>
              </li>
              <li>
                <a href="#" className="hover:underline">
                  Términos y Condiciones
                </a>
              </li>
              <li>
                <a href="#" className="hover:underline">
                  Política de Seguridad
                </a>
              </li>
            </ul>
          </div>
          
          <div>
            <h3 className="font-semibold mb-3 text-white">Contacto</h3>
            <ul className="space-y-2 text-sm">
              <li>Tel: 01 800 00 32 466</li>
              <li>contacto.inea@inea.gob.mx</li>
              <li>Atención de Lunes a Viernes<br/>9:00 - 18:00 hrs</li>
            </ul>
          </div>
        </div>
        
        <div className="border-t border-white/20 mt-8 pt-6 text-center text-sm">
          <p>&copy; 2026 Instituto Nacional para la Educación de los Adultos (INEA). Sistema de Gestión de Personal Operativo y Activos Tecnológicos.</p>
        </div>
      </div>
    </footer>
  );
}