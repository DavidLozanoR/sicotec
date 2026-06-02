<style>
    .bg-guinda { background-color: #9D2449 !important; }
    .text-guinda { color: #9D2449 !important; }
    .border-guinda { border-color: #9D2449 !important; }
    
    .upload-zone {
        border: 2px dashed #ccc;
        border-radius: 15px;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
        cursor: pointer;
    }
    .upload-zone:hover, .upload-zone.dragover {
        border-color: #9D2449;
        background-color: rgba(157, 36, 73, 0.05);
    }
    .nav-pills .nav-link.active { background-color: #9D2449 !important; }
    .nav-link { color: #6c757d; font-weight: bold; }
    .cursor-pointer { cursor: pointer !important; }
    
    /* Corrección visual para los íconos de ayuda dentro del botón activo */
    .nav-link.active .fa-question-circle { color: rgba(255, 255, 255, 0.7) !important; }
    .nav-link:not(.active) .fa-question-circle { color: #adb5bd !important; }
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <div>
            <h3 class="fw-bold text-guinda mb-0"><i class="fas fa-clipboard-check me-2"></i>Auditoría y Conciliación DHCP</h3>
            <p class="text-muted small mb-0">Cruce de información entre el Servidor DHCP y el Inventario SICOSOTE</p>
        </div>
        <input type="hidden" id="id_sub_actual" value="<?php echo htmlspecialchars($id_sub ?? 11); ?>">
    </div>

    <div id="seccion-carga" class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-5 text-center">
                    <div class="upload-zone p-5 mb-4" id="dropzone">
                        <i class="fas fa-file-excel fa-4x text-success mb-3"></i>
                        <h5>Arrastra el archivo del DHCP (<strong>Excel, CSV o XML</strong>)</h5>
                        <p class="text-muted small">O haz clic para seleccionar el archivo desde tu equipo</p>
                        <input type="file" id="input-archivo-dhcp" class="d-none" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel, .xml">
                    </div>
                    
                    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center text-start">
                        <i class="fas fa-info-circle fa-lg me-3"></i>
                        <small>El algoritmo detectará automáticamente las columnas dinámicas. Asegúrese de que el documento original contenga las columnas que mencionan: <strong>HOSTNAME, IP y MAC</strong>.</small>
                    </div>

                    <button id="btn-iniciar-conciliacion" class="btn bg-guinda text-white fw-bold px-5 py-2 mt-3 shadow-sm" disabled>
                        <i class="fas fa-sync-alt me-2"></i> Iniciar Conciliación
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="seccion-resultados" class="d-none">
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm border-start border-5 border-primary">
                    <div class="card-body p-3">
                        <h6 class="text-muted small fw-bold text-uppercase">Analizados en DHCP</h6>
                        <h3 id="stat-total-dhcp" class="fw-bold mb-0">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm border-start border-5 border-guinda">
                    <div class="card-body p-3">
                        <h6 class="text-muted small fw-bold text-uppercase">Conflictos de MAC</h6>
                        <h3 id="stat-conflictos" class="fw-bold mb-0 text-danger">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm border-start border-5 border-dark">
                    <div class="card-body p-3">
                        <h6 class="text-muted small fw-bold text-uppercase">Equipos Fantasmas</h6>
                        <h3 id="stat-fantasmas" class="fw-bold mb-0">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm border-start border-5 border-warning">
                    <div class="card-body p-3">
                        <h6 class="text-muted small fw-bold text-uppercase">IPs Huérfanas</h6>
                        <h3 id="stat-huerfanas" class="fw-bold mb-0 text-warning">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row align-items-center mb-3">
            <div class="col-md-6">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-guinda"><i class="fas fa-search"></i></span>
                    <input type="text" id="inputBuscar" class="form-control border-start-0 focus-guinda" placeholder="Buscar IP, MAC, Hostname, Ubicación o Usuario..." autocomplete="off">
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <label class="small text-muted fw-bold me-2">Mostrar:</label>
                <select id="selectorRegistros" class="form-select form-select-sm d-inline-block w-auto border-secondary-subtle focus-guinda shadow-sm">
                    <option value="10">10 registros</option>
                    <option value="25">25 registros</option>
                    <option value="50">50 registros</option>
                    <option value="100">100 registros</option>
                    <option value="todos">Todos</option>
                </select>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3">
                <ul class="nav nav-pills card-header-pills justify-content-center" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold d-flex align-items-center" id="tab-fantasmas-tab" data-bs-toggle="pill" data-bs-target="#tab-fantasmas" type="button" role="tab">
                            <i class="fas fa-ghost me-2"></i>Fantasmas / Desact.
                            <i class="fas fa-question-circle ms-2" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-container="body" title="Equipos Fantasmas" data-bs-content="Equipos que están navegando activamente en la red, pero que NO existen en el inventario de SICOSOTE o tienen registrada otra IP. Suelen ser dispositivos personales no autorizados (celulares, laptops externas) o equipos nuevos que olvidaron registrar."></i>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold d-flex align-items-center" id="tab-conflictos-tab" data-bs-toggle="pill" data-bs-target="#tab-conflictos" type="button" role="tab">
                            <i class="fas fa-exclamation-triangle me-2"></i>Conflictos de MAC
                            <i class="fas fa-question-circle ms-2" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-container="body" title="Conflictos de Red (MAC)" data-bs-content="La tarjeta de red (MAC) conectada físicamente no coincide con la registrada en SICOSOTE para esa IP. Ocurre si a un equipo se le cambió la tarjeta interna, usa un adaptador WiFi externo por USB, fue reemplazado físicamente, o en casos de suplantación de identidad."></i>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold d-flex align-items-center" id="tab-huerfanas-tab" data-bs-toggle="pill" data-bs-target="#tab-huerfanas" type="button" role="tab">
                            <i class="fas fa-plug me-2"></i>IPs Huérfanas
                            <i class="fas fa-question-circle ms-2" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-container="body" title="IPs Huérfanas" data-bs-content="Equipos que SICOSOTE marca como activos y con una IP asignada, pero que el servidor DHCP ya NO detecta en la red. Suelen ser dispositivos que llevan meses apagados, guardados en almacén o desechados sin haber sido dados de baja, desperdiciando direcciones IP."></i>
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="pills-tabContent">
                    
                    <div class="tab-pane fade show active" id="tab-fantasmas" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th class="ps-4">Tipo</th><th>IP (DHCP)</th><th>Dirección MAC</th><th>Hostname DHCP</th><th>Usuario Detectado</th><th class="pe-4">Observación</th></tr>
                                </thead>
                                <tbody id="tbody-fantasmas"></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="tab-conflictos" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-danger">
                                    <tr>
                                        <th class="ps-4">IP en Conflicto</th>
                                        <th>Hostname (DHCP vs SIC)</th>
                                        <th>MAC (DHCP vs SIC)</th>
                                        <th>Ubicación</th>
                                        <th>Responsable</th>
                                        <th class="pe-4">Acción Recomendada</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-conflictos"></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="tab-huerfanas" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">IP SICOSOTE</th>
                                        <th>Hostname Inventario</th>
                                        <th>MAC Registrada</th>
                                        <th># Inventario</th>
                                        <th>Ubicación</th>
                                        <th>Responsable</th>
                                        <th class="pe-4">Detalles</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-huerfanas"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-top d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
                <span id="infoPaginacion" class="text-muted small fw-bold mb-2 mb-md-0"></span>
                <ul id="ulPaginacion" class="pagination pagination-sm mb-0"></ul>
            </div>
            <div class="card-footer bg-light text-end py-3">
                <button class="btn btn-sm btn-secondary me-2" onclick="location.reload()"><i class="fas fa-undo me-1"></i> Nueva Auditoría</button>
                
            </div>
        </div>
    </div>
</div>