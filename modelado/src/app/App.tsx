import { RouterProvider } from "react-router";
import { router } from "./routes";
import { DataProvider } from "./context/DataContext";
import { Toaster } from "./components/ui/sonner";

export default function App() {
  return (
    <DataProvider>
      <RouterProvider router={router} />
      <Toaster />
    </DataProvider>
  );
}