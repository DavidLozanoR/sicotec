import { RouterProvider } from "react-router";
import { router } from "./routes";
import { useEffect } from "react";

export default function App() {
  // Load gob.mx framework
  useEffect(() => {
    const link = document.createElement("link");
    link.href = "https://framework-gb.cdn.gob.mx/assets/styles/main.css";
    link.rel = "stylesheet";
    document.head.appendChild(link);

    const script = document.createElement("script");
    script.src = "https://framework-gb.cdn.gob.mx/gobmx.js";
    script.async = true;
    document.body.appendChild(script);

    return () => {
      document.head.removeChild(link);
      document.body.removeChild(script);
    };
  }, []);

  return <RouterProvider router={router} />;
}
