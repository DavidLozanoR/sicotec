export function IneaLogo({ className = "" }: { className?: string }) {
  return (
    <div className={`flex items-center gap-3 ${className}`}>
      {/* Logo INEA - Texto provisional hasta tener imagen oficial */}
      <div className="flex items-center gap-2">
        <div className="w-12 h-12 bg-[#9D2449] flex items-center justify-center rounded">
          <span className="text-white font-bold text-sm">INEA</span>
        </div>
        <div className="flex flex-col leading-tight">
          <span className="text-[10px] font-semibold text-foreground uppercase">Instituto Nacional para la</span>
          <span className="text-sm font-bold text-foreground -mt-0.5 uppercase">Educación de los Adultos</span>
        </div>
      </div>
    </div>
  );
}

// Para usar imagen oficial cuando la tengas disponible:
// export function IneaLogo({ className = "" }: { className?: string }) {
//   return (
//     <div className={`flex items-center gap-3 ${className}`}>
//       <img 
//         src="/ruta/a/logo-inea.png" 
//         alt="Instituto Nacional para la Educación de los Adultos" 
//         className="h-12"
//       />
//     </div>
//   );
// }
