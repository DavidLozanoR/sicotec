import { useState } from "react";

interface Device {
  id: string;
  name: string;
  macEthernet?: string;
  macWifi?: string;
  ipAddress: string;
  connectionType: "Ethernet" | "WiFi";
  domain: string;
  operativePersonnelId: string;
  operativePersonnelName: string;
  os: string;
  location: string;
  registeredAt: string;
}

interface Service {
  id: string;
  name: string;
  version: string;
  lastUpdated: string;
}

const MOCK_SERVICES: Record<string, Service[]> = {
  DEV001: [
    { id: "s1", name: "Microsoft Office", version: "2021", lastUpdated: "2025-01-15" },
    { id: "s2", name: "Adobe Acrobat", version: "DC 2024", lastUpdated: "2024-12-10" },
    { id: "s3", name: "Antivirus Kaspersky", version: "21.3.10.391", lastUpdated: "2025-02-01" },
  ],
  DEV002: [
    { id: "s4", name: "Microsoft Office", version: "2019", lastUpdated: "2024-11-20" },
    { id: "s5", name: "AutoCAD", version: "2024", lastUpdated: "2025-01-05" },
  ],
};

interface User {
  id: string;
  role: "Jefe" | "Usuario";
}

interface DevicesModuleProps {
  currentUser: User;
}

export function DevicesModule({ currentUser }: DevicesModuleProps) {
  const [devices, setDevices] = useState<Device[]>([
    {
      id: "DEV001",
      name: "Laptop-Tecnología-01",
      macEthernet: "00:1B:44:11:3A:B7",
      macWifi: "A4:C3:F0:12:34:56",
      ipAddress: "192.168.1.105",
      connectionType: "WiFi",
      domain: "ineadom.gob.mx",
      operativePersonnelId: "OP001",
      operativePersonnelName: "Pedro Martínez Ramírez",
      os: "Windows 11 Pro",
      location: "Piso 2 - Área de Tecnología",
      registeredAt: "2024-10-15",
    },
    {
      id: "DEV002",
      name: "Desktop-Operaciones-01",
      macEthernet: "00:1A:2B:3C:4D:5E",
      ipAddress: "192.168.1.210",
      connectionType: "Ethernet",
      domain: "ineadom.gob.mx",
      operativePersonnelId: "OP003",
      operativePersonnelName: "Miguel Ángel Torres",
      os: "Windows 10 Pro",
      location: "Piso 1 - Área de Operaciones",
      registeredAt: "2024-09-20",
    },
  ]);

  const [selectedDevice, setSelectedDevice] = useState<Device | null>(null);
  const [activeTab, setActiveTab] = useState<"services" | "characteristics" | "networking">(
    "services"
  );
  const [searchQuery, setSearchQuery] = useState("");
  const [editingService, setEditingService] = useState<Service | null>(null);
  const [newVersion, setNewVersion] = useState("");

  const filteredDevices = devices.filter((device) => {
    const query = searchQuery.toLowerCase();
    return (
      device.name.toLowerCase().includes(query) ||
      device.operativePersonnelName.toLowerCase().includes(query) ||
      device.ipAddress.includes(query)
    );
  });

  const handleUpdateService = () => {
    if (editingService && newVersion.trim()) {
      // Aquí se actualizaría el servicio en la base de datos
      setEditingService(null);
      setNewVersion("");
      alert(`Servicio "${editingService.name}" actualizado a versión ${newVersion}`);
    }
  };

  return (
    <>
      <ol className="breadcrumb">
        <li>
          <a href="#">
            <i className="icon icon-home"></i>
          </a>
        </li>
        <li>
          <a href="#">INEA</a>
        </li>
        <li className="active">Dispositivos</li>
      </ol>

      <div className="row">
        <div className="col-md-12">
          <h1>Gestión de Dispositivos</h1>
        </div>
      </div>
      <hr className="red" />

      <div className="row">
        <div className="col-md-12">
          <p>
            Administre los dispositivos registrados en el dominio ineadom.gob.mx y su configuración
            de red.
          </p>
        </div>
      </div>

      <div className="row" style={{ marginTop: "30px", marginBottom: "20px" }}>
        <div className="col-md-8">
          <div className="form-group">
            <label htmlFor="searchDevice" className="control-label">
              Buscar Dispositivo
            </label>
            <div className="input-group">
              <span className="input-group-addon">
                <span className="glyphicon glyphicon-search"></span>
              </span>
              <input
                id="searchDevice"
                type="text"
                className="form-control"
                placeholder="Buscar por nombre, responsable o IP..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
          </div>
        </div>
      </div>

      {filteredDevices.length === 0 ? (
        <div className="row">
          <div className="col-md-12">
            <div
              className="alert alert-info"
              style={{ textAlign: "center", padding: "40px" }}
            >
              <span
                className="glyphicon glyphicon-info-sign"
                style={{ fontSize: "48px", display: "block", marginBottom: "20px" }}
              ></span>
              <h3 style={{ marginTop: 0 }}>No se encontraron dispositivos</h3>
              <p>No hay dispositivos que coincidan con la búsqueda.</p>
            </div>
          </div>
        </div>
      ) : (
        <div className="row">
          <div className="col-md-12">
            <div className="table-responsive">
              <table className="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th>ID Dispositivo</th>
                    <th>Nombre</th>
                    <th>Responsable (OP)</th>
                    <th>IP</th>
                    <th>Conexión</th>
                    <th style={{ width: "120px", textAlign: "center" }}>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredDevices.map((device) => (
                    <tr key={device.id}>
                      <td>
                        <strong>{device.id}</strong>
                      </td>
                      <td>
                        <span
                          className="glyphicon glyphicon-hdd"
                          style={{ marginRight: "8px", color: "#888" }}
                        ></span>
                        {device.name}
                      </td>
                      <td>{device.operativePersonnelName}</td>
                      <td>
                        <code>{device.ipAddress}</code>
                      </td>
                      <td>
                        <span
                          className={`label ${
                            device.connectionType === "Ethernet"
                              ? "label-success"
                              : "label-info"
                          }`}
                        >
                          {device.connectionType}
                        </span>
                      </td>
                      <td style={{ textAlign: "center" }}>
                        <button
                          type="button"
                          className="btn btn-primary btn-sm"
                          onClick={() => {
                            setSelectedDevice(device);
                            setActiveTab("services");
                          }}
                        >
                          <span className="glyphicon glyphicon-cog"></span> Gestionar
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* Modal de gestión de dispositivo */}
      {selectedDevice && (
        <>
          <div className="modal fade in" style={{ display: "block" }} role="dialog">
            <div className="modal-dialog modal-lg">
              <div className="modal-content">
                <div className="modal-header">
                  <button
                    type="button"
                    className="close"
                    onClick={() => setSelectedDevice(null)}
                  >
                    <span>&times;</span>
                  </button>
                  <h4 className="modal-title">
                    <span className="glyphicon glyphicon-hdd"></span> Gestión de Dispositivo:{" "}
                    {selectedDevice.name}
                  </h4>
                </div>
                <div className="modal-body">
                  {/* Tabs */}
                  <ul className="nav nav-tabs" style={{ marginBottom: "20px" }}>
                    <li className={activeTab === "services" ? "active" : ""}>
                      <a href="#" onClick={() => setActiveTab("services")}>
                        Servicios
                      </a>
                    </li>
                    <li className={activeTab === "characteristics" ? "active" : ""}>
                      <a href="#" onClick={() => setActiveTab("characteristics")}>
                        Características
                      </a>
                    </li>
                    <li className={activeTab === "networking" ? "active" : ""}>
                      <a href="#" onClick={() => setActiveTab("networking")}>
                        Networking
                      </a>
                    </li>
                  </ul>

                  {/* Tab Content */}
                  {activeTab === "services" && (
                    <div>
                      <h4>Software Instalado</h4>
                      <div className="table-responsive">
                        <table className="table table-bordered">
                          <thead>
                            <tr>
                              <th>Software</th>
                              <th>Versión</th>
                              <th>Última Actualización</th>
                              <th style={{ width: "120px" }}>Acciones</th>
                            </tr>
                          </thead>
                          <tbody>
                            {(MOCK_SERVICES[selectedDevice.id] || []).map((service) => (
                              <tr key={service.id}>
                                <td>{service.name}</td>
                                <td>
                                  <code>{service.version}</code>
                                </td>
                                <td>{service.lastUpdated}</td>
                                <td>
                                  <button
                                    type="button"
                                    className="btn btn-primary btn-xs"
                                    onClick={() => setEditingService(service)}
                                  >
                                    Actualizar
                                  </button>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>

                      {editingService && (
                        <div className="panel panel-default" style={{ marginTop: "20px" }}>
                          <div className="panel-body">
                            <h5>
                              Actualizar: <strong>{editingService.name}</strong>
                            </h5>
                            <div className="form-group">
                              <label>Nueva Versión:</label>
                              <input
                                type="text"
                                className="form-control"
                                value={newVersion}
                                onChange={(e) => setNewVersion(e.target.value)}
                                placeholder="Ej: 2024.1.5"
                              />
                            </div>
                            <button
                              type="button"
                              className="btn btn-success btn-sm"
                              onClick={handleUpdateService}
                            >
                              Confirmar Actualización
                            </button>
                            <button
                              type="button"
                              className="btn btn-default btn-sm"
                              style={{ marginLeft: "10px" }}
                              onClick={() => {
                                setEditingService(null);
                                setNewVersion("");
                              }}
                            >
                              Cancelar
                            </button>
                          </div>
                        </div>
                      )}
                    </div>
                  )}

                  {activeTab === "characteristics" && (
                    <div>
                      <h4>Características del Dispositivo</h4>
                      <div className="row">
                        <div className="col-md-6">
                          <dl>
                            <dt>ID Dispositivo:</dt>
                            <dd>{selectedDevice.id}</dd>
                            <dt>Nombre:</dt>
                            <dd>{selectedDevice.name}</dd>
                            <dt>Sistema Operativo:</dt>
                            <dd>{selectedDevice.os}</dd>
                            <dt>Dominio:</dt>
                            <dd>{selectedDevice.domain}</dd>
                          </dl>
                        </div>
                        <div className="col-md-6">
                          <dl>
                            <dt>Responsable:</dt>
                            <dd>{selectedDevice.operativePersonnelName}</dd>
                            <dt>Ubicación:</dt>
                            <dd>{selectedDevice.location}</dd>
                            <dt>Fecha de Registro:</dt>
                            <dd>{selectedDevice.registeredAt}</dd>
                          </dl>
                        </div>
                      </div>
                    </div>
                  )}

                  {activeTab === "networking" && (
                    <div>
                      <h4>Configuración de Red</h4>
                      <div className="panel panel-default">
                        <div className="panel-body">
                          <div className="row">
                            <div className="col-md-6">
                              <dl>
                                <dt>Dirección IP:</dt>
                                <dd>
                                  <code>{selectedDevice.ipAddress}</code>
                                </dd>
                                <dt>Tipo de Conexión:</dt>
                                <dd>
                                  <span
                                    className={`label ${
                                      selectedDevice.connectionType === "Ethernet"
                                        ? "label-success"
                                        : "label-info"
                                    }`}
                                  >
                                    {selectedDevice.connectionType}
                                  </span>
                                </dd>
                                {selectedDevice.macEthernet && (
                                  <>
                                    <dt>MAC Ethernet:</dt>
                                    <dd>
                                      <code>{selectedDevice.macEthernet}</code>
                                    </dd>
                                  </>
                                )}
                              </dl>
                            </div>
                            <div className="col-md-6">
                              {selectedDevice.macWifi && (
                                <dl>
                                  <dt>MAC WiFi:</dt>
                                  <dd>
                                    <code>{selectedDevice.macWifi}</code>
                                  </dd>
                                </dl>
                              )}
                              <dl>
                                <dt>Dominio:</dt>
                                <dd>
                                  <code>{selectedDevice.domain}</code>
                                </dd>
                              </dl>
                            </div>
                          </div>

                          <hr />

                          <div className="alert alert-warning" style={{ marginBottom: 0 }}>
                            <p style={{ margin: 0 }}>
                              <span
                                className="glyphicon glyphicon-warning-sign"
                                style={{ marginRight: "8px" }}
                              ></span>
                              <strong>Cambio de Infraestructura:</strong> Para cambiar la
                              configuración de red (Ethernet a WiFi o cambio de ubicación),
                              contacte al administrador del sistema.
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
                <div className="modal-footer">
                  <button
                    type="button"
                    className="btn btn-default"
                    onClick={() => setSelectedDevice(null)}
                  >
                    Cerrar
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div
            className="modal-backdrop fade in"
            onClick={() => setSelectedDevice(null)}
          ></div>
        </>
      )}
    </>
  );
}
