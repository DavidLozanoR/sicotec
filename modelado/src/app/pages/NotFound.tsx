import { Link } from "react-router";
import { Button } from "../components/ui/button";
import { Home } from "lucide-react";

export function NotFound() {
  return (
    <div className="flex flex-col items-center justify-center min-h-[60vh] text-center">
      <h1 className="text-6xl font-bold text-primary mb-4">404</h1>
      <h2 className="text-2xl font-semibold mb-2">Página No Encontrada</h2>
      <p className="text-muted-foreground mb-8 max-w-md">
        La página que busca no existe o ha sido movida a otra ubicación.
      </p>
      <Link to="/">
        <Button className="flex items-center gap-2">
          <Home className="w-4 h-4" />
          Volver al Inicio
        </Button>
      </Link>
    </div>
  );
}
