import { createBrowserRouter } from "react-router";
import LoginPage from "./pages/LoginPage";
import DashboardPage from "./pages/DashboardPage";
import UsersPage from "./pages/UsersPage";
import DevicesPage from "./pages/DevicesPage";
import SoftwarePage from "./pages/SoftwarePage";
import ReportsPage from "./pages/ReportsPage";

export const router = createBrowserRouter([
  {
    path: "/",
    Component: LoginPage,
  },
  {
    path: "/dashboard",
    Component: DashboardPage,
  },
  {
    path: "/usuarios",
    Component: UsersPage,
  },
  {
    path: "/dispositivos",
    Component: DevicesPage,
  },
  {
    path: "/software",
    Component: SoftwarePage,
  },
  {
    path: "/reportes",
    Component: ReportsPage,
  },
]);
