<script> const ID_SUB_PERSONAL = '<?php echo $id_sub_personal; ?>'; </script>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fas fa-database me-2 text-primary"></i>Importación Masiva de Personal</h2>
            <p class="text-muted">Sincronice la base de datos con el archivo de nómina actual.</p>
        </div>
        <a href="personal" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Regresar a Personal
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            
            <div id="section_upload" class="py-5 text-center">
                <div class="mb-4">
                    <i class="fas fa-file-excel fa-5x text-success opacity-25"></i>
                </div>
                <h3>Paso 1: Cargar Archivo de Datos</h3>
                <p class="text-muted">Asegúrese de que el archivo contenga las columnas de <b>Nombre</b> y <b>Número de Cobro</b>.<br>
                <small>Formatos permitidos: Excel (.xlsx), CSV, XML.</small></p>
                
                <div class="col-md-5 mx-auto mt-4">
                    <div class="input-group">
                        <input type="file" class="form-control" id="inputArchivoMasivo" accept=".csv, .xlsx, .xml, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel, application/vnd.oasis.opendocument.spreadsheet, application/json, application/xml, text/xml">
                        <button class="btn btn-primary" id="btnIniciarAnalisis">
                            <i class="fas fa-search me-2"></i> Analizar
                        </button>
                    </div>
                    <div id="upload_progress" class="progress mt-3 d-none" style="height: 5px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                    </div>
                </div>
            </div>

            <div id="section_resolucion" class="d-none">
                <div class="p-4 border-bottom bg-light">
                    <h4 class="mb-0">Paso 2: Revisión y Resolución de Conflictos</h4>
                </div>
                
                <div class="p-4">
                    <ul class="nav nav-pills mb-4" id="importTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="dept-tab" data-bs-toggle="tab" data-bs-target="#tab-depts" type="button">
                                <i class="fas fa-building me-2"></i> Áreas / Departamentos 
                                <span class="badge bg-danger ms-2" id="badge-conflictos-dep">0</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="bajas-tab" data-bs-toggle="tab" data-bs-target="#tab-bajas" type="button">
                                <i class="fas fa-user-minus me-2"></i> Bajas Detectadas 
                                <span class="badge bg-warning text-dark ms-2" id="badge-bajas">0</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content border p-3 rounded bg-white" id="importTabContent">
                        
                        <div class="tab-pane fade show active" id="tab-depts" role="tabpanel">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Los siguientes departamentos en el archivo no coinciden exactamente con la base de datos. Elija una acción.
                            </div>
                        
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Clave Origen</th>
                                            <th>Nombre en Origen</th>
                                            <th>Sugerencia / Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyConflictosDep">
                                        </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-bajas" role="tabpanel">
                            <div class="alert alert-warning text-dark">
                                <i class="fas fa-exclamation-triangle me-2"></i> Estos usuarios activos en el sistema NO están en el archivo de hoy. Seleccione a quiénes dar de baja.
                            </div>
                            <div class="alert alert-warning text-dark">
                                <i class="fas fa-info-circle me-2"></i> Si un usuario tiene equipos asignados, se deben liberar sus equipos y dar de baja individualmente.
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Num. Cobro</th>
                                            <th>Nombre Completo</th>
                                            <th>Responsivas (Equipos)</th>
                                            <th class="text-center">¿Dar de Baja?</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyBajasDetectadas">
                                        </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-footer bg-white text-end p-4 border-top">
                    <button class="btn btn-lg btn-success" id="btnFinalizarImportacion">
                        <i class="fas fa-sync-alt me-2"></i> Ejecutar Sincronización Final
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>