import { createBrowserRouter } from "react-router";
import { MainLayout } from "./components/layouts/MainLayout";
import { Login } from "./pages/Login";
import { Dashboard } from "./pages/Dashboard";
import { PersonalOperativo } from "./pages/PersonalOperativo";
import { PersonalOperativoForm } from "./pages/PersonalOperativoForm";
import { ConfiguracionGlobal } from "./pages/ConfiguracionGlobal";
import { Servicios } from "./pages/Servicios";
import { InfraestructuraRed } from "./pages/InfraestructuraRed";
import { Responsiva } from "./pages/Responsiva";
import { Consultas } from "./pages/Consultas";
import { Usuarios } from "./pages/Usuarios";
import { NotFound } from "./pages/NotFound";
import { AuthProvider } from "./context/AuthContext";

function RootLayout({ children }: { children: React.ReactNode }) {
  return <AuthProvider>{children}</AuthProvider>;
}

export const router = createBrowserRouter([
  {
    path: "/login",
    element: (
      <RootLayout>
        <Login />
      </RootLayout>
    ),
  },
  {
    path: "/",
    element: (
      <RootLayout>
        <MainLayout />
      </RootLayout>
    ),
    children: [
      { index: true, element: <Dashboard /> },
      { path: "personal-operativo", element: <PersonalOperativo /> },
      { path: "personal-operativo/nuevo", element: <PersonalOperativoForm /> },
      { path: "personal-operativo/editar/:id", element: <PersonalOperativoForm /> },
      { path: "configuracion-global", element: <ConfiguracionGlobal /> },
      { path: "configuracion-global/servicios", element: <Servicios /> },
      { path: "configuracion-global/red", element: <InfraestructuraRed /> },
      { path: "configuracion-global/responsiva", element: <Responsiva /> },
      { path: "consultas", element: <Consultas /> },
      { path: "usuarios", element: <Usuarios /> },
      { path: "*", element: <NotFound /> },
    ],
  },
]);