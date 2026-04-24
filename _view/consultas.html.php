<?php
$departamentos = $departamentos ?? [];
$servicios = $servicios ?? [];
$vlans = $vlans ?? [];
$usuarios = $usuarios ?? [];
?>
<link rel="stylesheet" href="css/master.css">

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <div>
            <h3 class="fw-bold text-guinda mb-1"><i class="fas fa-search-plus me-2"></i>Consultas y Auditoría</h3>
            <p class="text-secondary mb-0 small">Cruce de información de hardware, red, software y bitácora del sistema.</p>
        </div>
        <div>
            <button type="button" class="btn btn-success fw-bold shadow-sm" id="btn-exportar-excel">
                <i class="fas fa-file-excel me-2"></i>Exportar Reporte
            </button>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="consultasTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="auditoria-tab" data-bs-toggle="tab" data-bs-target="#auditoria-pane" type="button" role="tab" style="color: #9D2449;">
                <i class="fas fa-laptop me-1"></i> Auditoría de Equipos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="bitacora-tab" data-bs-toggle="tab" data-bs-target="#bitacora-pane" type="button" role="tab" style="color: #9D2449;">
                <i class="fas fa-history me-1"></i> Bitácora del Sistema
            </button>
        </li>
    </ul>

    <div class="tab-content" id="consultasTabsContent">
        
        <div class="tab-pane fade show active" id="auditoria-pane" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-3">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header text-white pt-3 pb-2" style="background-color: #9D2449;">
                            <h6 class="fw-bold mb-0"><i class="fas fa-sliders-h me-2"></i>Criterios de Búsqueda</h6>
                        </div>
                        <div class="card-body bg-light">
                            <form id="form-consultas">
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-primary"><i class="fas fa-box-open me-1"></i>Estado Físico del Equipo</label>
                                    <select class="form-select form-select-sm border-primary" id="filtro-estado-asig" name="estado_asig">
                                        <option value="todos">Todos (Asignados y Almacén)</option>
                                        <option value="asignados">Solo Equipos Asignados</option>
                                        <option value="almacen">Equipos en Almacén (Sin asignar)</option>
                                    </select>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Ubicación Organizacional</label>
                                    <select class="form-select form-select-sm" id="filtro-departamento" name="id_dep">
                                        <option value="">Cualquier Departamento</option>
                                        <?php foreach($departamentos as $d): ?>
                                            <option value="<?= $d['id_dep'] ?>"><?= htmlspecialchars($d['nom_dep']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted" style="font-size: 0.7rem;" id="help-departamento">Filtra por el área del responsable.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Ubicación Física / Red</label>
                                    <select id="vlan-select" class="form-select form-select-sm" name="id_vlan">
                                        <option value="">Cualquier VLAN / Segmento</option>
                                        <?php foreach($vlans as $v): ?>
                                            <option value="<?= $v['id_vlan'] ?>"><?= htmlspecialchars($v['nom_vlan']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Auditoría de Software</label>
                                    <select class="form-select form-select-sm" id="filtro-servicio" name="id_serv">
                                        <option value="">Sin filtro de software</option>
                                        <?php foreach($servicios as $s): ?>
                                            <option value="<?= $s['id_serv'] ?>"><?= htmlspecialchars($s['tipo_serv'] . ' - ' . $s['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">Estado de la Licencia/Versión</label>
                                    <select class="form-select form-select-sm" id="filtro-estado-soft" name="estado_soft" disabled>
                                        <option value="todos">Cualquier Estado</option>
                                        <option value="actualizado">Versiones Aceptadas / Actualizadas</option>
                                        <option value="obsoleto">Versiones Obsoletas (Riesgo)</option>
                                    </select>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="button" class="btn text-white fw-bold shadow-sm" id="btn-generar-consulta" style="background-color: #B38E5D;">
                                        <i class="fas fa-play me-2"></i>Generar Consulta
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light sticky-top shadow-sm">
                                        <tr>
                                            <th class="ps-4">Equipo e Inventario</th>
                                            <th>Conexión y Ubicación</th>
                                            <th>Responsable Asignado</th>
                                            <th class="d-none th-software">Software Auditado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-resultados">
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <i class="fas fa-filter fa-3x mb-3 opacity-25"></i>
                                                <h5 class="fw-bold text-secondary">Panel de Auditoría</h5>
                                                <p class="mb-0 small">Seleccione los criterios a la izquierda y presione "Generar Consulta".</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="bitacora-pane" role="tabpanel">
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-body bg-light rounded-3">
                    <form id="form-bitacora" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Fecha Inicio</label>
                            <input type="date" class="form-control form-control-sm border-secondary" name="fecha_ini">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Fecha Fin</label>
                            <input type="date" class="form-control form-control-sm border-secondary" name="fecha_fin">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Usuario que realizó el movimiento</label>
                            <select class="form-select form-select-sm border-secondary" name="id_usuar">
                                <option value="">Todos los Usuarios</option>
                                <?php foreach($usuarios as $u): ?>
                                    <option value="<?= $u['id_usuar'] ?>"><?= htmlspecialchars($u['nom_usuar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="button" class="btn text-white fw-bold btn-sm shadow-sm" id="btn-generar-bitacora" style="background-color: #B38E5D;">
                                <i class="fas fa-search me-1"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-dark sticky-top shadow-sm">
                                <tr>
                                    <th class="ps-4">Fecha y Hora</th>
                                    <th>Usuario (Actor)</th>
                                    <th>Módulo Afectado</th>
                                    <th>Tipo de Acción</th>
                                    <th>Detalle del Registro</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-bitacora">
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-history fa-3x mb-3 opacity-25"></i>
                                        <h5 class="fw-bold text-secondary">Listos para auditar</h5>
                                        <p class="mb-0 small">Haga clic en Buscar para cargar la bitácora.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>