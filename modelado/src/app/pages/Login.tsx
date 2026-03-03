import { useState } from "react";
import { Navigate, useNavigate } from "react-router";
import { useAuth } from "../context/AuthContext";
import { IneaLogo } from "../components/IneaLogo";
import { Footer } from "../components/Footer";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "../components/ui/card";
import { Alert, AlertDescription } from "../components/ui/alert";
import { AlertCircle } from "lucide-react";

export function Login() {
  const { login, isAuthenticated } = useAuth();
  const navigate = useNavigate();
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");

  if (isAuthenticated) {
    return <Navigate to="/" replace />;
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError("");

    if (!username || !password) {
      setError("Por favor ingrese usuario y contraseña");
      return;
    }

    const success = login(username, password);
    if (success) {
      navigate("/");
    } else {
      setError("Usuario o contraseña incorrectos");
    }
  };

  return (
    <div className="min-h-screen flex flex-col bg-gradient-to-br from-[#9D2449] via-[#691B32] to-[#4a1222]">
      <header className="bg-white/95 backdrop-blur-sm border-b border-border shadow-sm">
        <div className="container mx-auto px-4 py-4">
          <IneaLogo />
        </div>
      </header>

      <main className="flex-1 flex items-center justify-center p-4">
        <Card className="w-full max-w-md">
          <CardHeader className="space-y-1">
            <CardTitle className="text-2xl text-center">Iniciar Sesión</CardTitle>
            <CardDescription className="text-center">
              Sistema de Gestión de Personal Operativo y Activos Tecnológicos - INEA
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              {error && (
                <Alert variant="destructive">
                  <AlertCircle className="h-4 w-4" />
                  <AlertDescription>{error}</AlertDescription>
                </Alert>
              )}

              <div className="space-y-2">
                <Label htmlFor="username">Usuario</Label>
                <Input
                  id="username"
                  type="text"
                  placeholder="Ingrese su usuario"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  className="bg-input-background"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">Contraseña</Label>
                <Input
                  id="password"
                  type="password"
                  placeholder="Ingrese su contraseña"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="bg-input-background"
                />
              </div>

              <Button type="submit" className="w-full bg-primary hover:bg-primary/90">
                Iniciar Sesión
              </Button>

              <div className="mt-6 p-4 bg-muted rounded-md">
                <p className="text-sm font-medium mb-2">Usuarios de prueba:</p>
                <div className="text-xs space-y-1 text-muted-foreground">
                  <p><strong>Administrador:</strong> admin / admin123</p>
                  <p><strong>Técnico:</strong> tecnico / tecnico123</p>
                </div>
              </div>
            </form>
          </CardContent>
        </Card>
      </main>

      <Footer />
    </div>
  );
}