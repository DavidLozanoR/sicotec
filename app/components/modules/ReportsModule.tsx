import { useState } from "react";

interface ReportData {
  softwareName: string;
  deviceCount: number;
  lastUpdate: string;
}

const MOCK_REPORT_DATA: ReportData[] = [
  { softwareName: "Microsoft Office", deviceCount: 45, lastUpdate: "2025-01-15" },
  { softwareName: "Adobe Acrobat", deviceCount: 32, lastUpdate: "2024-12-10" },
  { softwareName: "Antivirus Kaspersky", deviceCount: 50, lastUpdate: "2025-02-01" },
  { softwareName: "AutoCAD", deviceCount: 12, lastUpdate: "2025-01-05" },
  { softwareName: "Navegador Chrome", deviceCount: 50, lastUpdate: "2025-02-10" },
];

export function ReportsModule() {
  const [selectedSoftware, setSelectedSoftware] = useState("");
  const [reportResult, setReportResult] = useState<ReportData | null>(null);

  const handleGenerateReport = () => {
    if (selectedSoftware) {
      const result = MOCK_REPORT_DATA.find((item) => item.softwareName === selectedSoftware);
      setReportResult(result || null);
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
        <li className="active">Reportes</li>
      </ol>

      <div className="row">
        <div className="col-md-12">
          <h1>Reportería del Sistema</h1>
        </div>
      </div>
      <hr className="red" />

      <div className="row">
        <div className="col-md-12">
          <p>
            Genere reportes sobre la instalación de software en los dispositivos registrados en el
            sistema.
          </p>
        </div>
      </div>

      {/* Formulario de reporte */}
      <div className="row" style={{ marginTop: "30px" }}>
        <div className="col-md-8 col-md-offset-2">
          <div className="panel panel-default">
            <div className="panel-heading">
              <h3 className="panel-title">
                <span className="glyphicon glyphicon-stats"></span> Generar Reporte de Software
              </h3>
            </div>
            <div className="panel-body">
              <form
                onSubmit={(e) => {
                  e.preventDefault();
                  handleGenerateReport();
                }}
              >
                <div className="form-group">
                  <label htmlFor="softwareSelect" className="control-label">
                    Seleccione el Software <span style={{ color: "#D0021B" }}>*</span>
                  </label>
                  <select
                    id="softwareSelect"
                    className="form-control"
                    value={selectedSoftware}
                    onChange={(e) => setSelectedSoftware(e.target.value)}
                    required
                  >
                    <option value="">-- Seleccione un software --</option>
                    {MOCK_REPORT_DATA.map((item) => (
                      <option key={item.softwareName} value={item.softwareName}>
                        {item.softwareName}
                      </option>
                    ))}
                  </select>
                  <p className="help-block" style={{ fontSize: "12px", marginTop: "5px" }}>
                    Seleccione el software del cual desea obtener información de instalación.
                  </p>
                </div>

                <button type="submit" className="btn btn-primary">
                  <span className="glyphicon glyphicon-search"></span> Generar Reporte
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      {/* Resultado del reporte */}
      {reportResult && (
        <div className="row" style={{ marginTop: "30px" }}>
          <div className="col-md-8 col-md-offset-2">
            <div className="panel panel-success">
              <div className="panel-heading">
                <h3 className="panel-title">
                  <span className="glyphicon glyphicon-ok-circle"></span> Resultado del Reporte
                </h3>
              </div>
              <div className="panel-body">
                <div className="row">
                  <div className="col-md-12">
                    <h4 style={{ marginTop: 0 }}>
                      <strong>{reportResult.softwareName}</strong>
                    </h4>
                  </div>
                </div>

                <div className="row" style={{ marginTop: "20px" }}>
                  <div className="col-sm-6 col-md-4">
                    <div
                      style={{
                        textAlign: "center",
                        padding: "20px",
                        backgroundColor: "#f6f6f6",
                        borderRadius: "4px",
                      }}
                    >
                      <span
                        className="glyphicon glyphicon-hdd"
                        style={{ fontSize: "40px", color: "#4D92DF", display: "block" }}
                      ></span>
                      <h2 style={{ margin: "10px 0 5px 0", color: "#4D92DF" }}>
                        {reportResult.deviceCount}
                      </h2>
                      <p style={{ margin: 0, color: "#666" }}>Dispositivos</p>
                    </div>
                  </div>

                  <div className="col-sm-6 col-md-8">
                    <div style={{ paddingLeft: "20px" }}>
                      <dl>
                        <dt>Software:</dt>
                        <dd style={{ marginBottom: "15px" }}>{reportResult.softwareName}</dd>

                        <dt>Total de Instalaciones:</dt>
                        <dd style={{ marginBottom: "15px" }}>
                          <span
                            className="badge"
                            style={{ backgroundColor: "#4D92DF", fontSize: "16px" }}
                          >
                            {reportResult.deviceCount}
                          </span>{" "}
                          dispositivos con este software instalado
                        </dd>

                        <dt>Última Actualización Registrada:</dt>
                        <dd style={{ marginBottom: "15px" }}>
                          <span className="glyphicon glyphicon-calendar"></span>{" "}
                          {reportResult.lastUpdate}
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>

                <hr />

                <div className="row">
                  <div className="col-md-12">
                    <h5>
                      <strong>Información Adicional:</strong>
                    </h5>
                    <p style={{ marginBottom: "10px" }}>
                      Este reporte muestra el número total de dispositivos que tienen instalado{" "}
                      <strong>{reportResult.softwareName}</strong> en el sistema INEA.
                    </p>
                    <p style={{ marginBottom: 0, fontSize: "14px", color: "#666" }}>
                      <span
                        className="glyphicon glyphicon-info-sign"
                        style={{ marginRight: "8px" }}
                      ></span>
                      Para ver detalles específicos de cada dispositivo, consulte el módulo de{" "}
                      <strong>Dispositivos</strong>.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Tabla resumen general */}
      <div className="row" style={{ marginTop: "40px" }}>
        <div className="col-md-12">
          <h3>Resumen General de Software</h3>
          <hr className="red" />
          <div className="table-responsive">
            <table className="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>Software</th>
                  <th style={{ textAlign: "center" }}>Dispositivos con Instalación</th>
                  <th>Última Actualización</th>
                </tr>
              </thead>
              <tbody>
                {MOCK_REPORT_DATA.map((item) => (
                  <tr key={item.softwareName}>
                    <td>
                      <strong>{item.softwareName}</strong>
                    </td>
                    <td style={{ textAlign: "center" }}>
                      <span className="badge" style={{ backgroundColor: "#4D92DF" }}>
                        {item.deviceCount}
                      </span>
                    </td>
                    <td>
                      <span className="glyphicon glyphicon-calendar"></span> {item.lastUpdate}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </>
  );
}
