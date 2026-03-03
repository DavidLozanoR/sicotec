import React, { createContext, useContext, useState, useEffect } from "react";

interface User {
  id: string;
  username: string;
  rol: string;
  permisos: {
    gestionPersonal: boolean;
    configuracionGlobal: boolean;
    consultas: boolean;
    usuarios: boolean;
  };
}

interface AuthContextType {
  user: User | null;
  login: (username: string, password: string) => boolean;
  logout: () => void;
  isAuthenticated: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

// Usuarios predefinidos
const USERS: { [key: string]: { password: string; data: User } } = {
  admin: {
    password: "admin123",
    data: {
      id: "1",
      username: "admin",
      rol: "Administrador",
      permisos: {
        gestionPersonal: true,
        configuracionGlobal: true,
        consultas: true,
        usuarios: true,
      },
    },
  },
  tecnico: {
    password: "tecnico123",
    data: {
      id: "2",
      username: "tecnico",
      rol: "Usuario",
      permisos: {
        gestionPersonal: true,
        configuracionGlobal: true,
        consultas: true,
        usuarios: false,
      },
    },
  },
};

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);

  useEffect(() => {
    // Verificar si hay sesión guardada
    const savedUser = localStorage.getItem("user");
    if (savedUser) {
      setUser(JSON.parse(savedUser));
    }
  }, []);

  const login = (username: string, password: string): boolean => {
    const userAccount = USERS[username.toLowerCase()];
    if (userAccount && userAccount.password === password) {
      setUser(userAccount.data);
      localStorage.setItem("user", JSON.stringify(userAccount.data));
      return true;
    }
    return false;
  };

  const logout = () => {
    setUser(null);
    localStorage.removeItem("user");
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        login,
        logout,
        isAuthenticated: !!user,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
}