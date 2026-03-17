<?php
$departamentos = $departamentos ?? [];
$servicios = $servicios ?? [];
?>
<link rel="stylesheet" href="css/master.css">

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h3 class="fw-bold text-guinda mb-1"><i class="fas fa-search-plus me-2"></i>Consultas y Auditoría</h3>
            <p class="text-secondary mb-0 small">Cruce de información estratégica de hardware, red y software.</p>
        </div>
        <div>
            <button type="button" class="btn btn-success fw-bold shadow-sm" id="btn-exportar-excel">
                <i class="fas fa-file-excel me-2"></i>Exportar Reporte
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-guinda text-white pt-3 pb-2" style="background-color: #9D2449;">
                    <h6 class="fw-bold mb-0"><i class="fas fa-sliders-h me-2"></i>Criterios de Auditoría</h6>
                </div>
                <div class="card-body bg-light">
                    <form id="form-consultas">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-map-marker-alt text-guinda me-1"></i> Departamento (Red)</label>
                            <select class="form-select border-secondary-subtle focus-guinda shadow-sm" name="id_dep">
                                <option value="">-- Toda la Institución --</option>
                                <?php foreach($departamentos as $dep): ?>
                                    <option value="<?php echo $dep['id_dep']; ?>"><?php echo htmlspecialchars($dep['nom_dep']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-user-tag text-guinda me-1"></i> Estado de Asignación</label>
                            <select class="form-select border-secondary-subtle focus-guinda shadow-sm" name="estado_asig">
                                <option value="todos">Mostrar Todos</option>
                                <option value="asignados">Solo equipos asignados (En uso)</option>
                                <option value="sin_asignar">Equipos sin responsable (En Almacén)</option>
                            </select>
                        </div>

                        <hr class="my-4 text-secondary">
                        <h6 class="fw-bold text-dark small mb-3">Auditoría de Software</h6>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-cogs text-guinda me-1"></i> Servicio Instalado</label>
                            <select class="form-select border-secondary-subtle focus-guinda shadow-sm" name="id_serv" id="filtro-servicio">
                                <option value="">-- No auditar software --</option>
                                <?php foreach($servicios as $serv): ?>
                                    <option value="<?php echo $serv['id_serv']; ?>"><?php echo htmlspecialchars($serv['nom']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-shield-alt text-guinda me-1"></i> Estado de Versión</label>
                            <select class="form-select border-secondary-subtle focus-guinda shadow-sm" name="estado_soft" id="filtro-estado-soft" disabled>
                                <option value="todos">Cualquier versión</option>
                                <option value="actualizado">Solo Actualizados (Seguros)</option>
                                <option value="obsoleto">Solo Obsoletos (Vulnerables)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-guinda w-100 fw-bold shadow-sm" style="background-color: #9D2449; color: white;" id="btn-generar-consulta">
                            <i class="fas fa-search me-2"></i>Generar Consulta
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top shadow-sm">
                                <tr>
                                    <th class="ps-4">Equipo / Detalles</th>
                                    <th>Ubicación (Red)</th>
                                    <th>Responsable</th>
                                    <th class="d-none th-software">Software Auditado</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-resultados">
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-filter fa-3x mb-3 opacity-25"></i>
                                        <h5 class="fw-bold text-secondary">Panel de Auditoría</h5>
                                        <p class="mb-0 small">Configure los filtros a la izquierda y presione "Generar Consulta" para cruzar la información.</p>
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