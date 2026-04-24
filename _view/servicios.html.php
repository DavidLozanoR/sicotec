<?php
$servicios = $servicios ?? [];
$id_sub_actual = $_SESSION['submodulo_actual'] ?? 2; 
?>

<div class="container-fluid px-4 py-3">
    <input type="hidden" id="id_sub_serv" value="<?php echo $id_sub_actual; ?>">

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h3 class="fw-bold text-guinda mb-1"><i class="fas fa-layer-group me-2"></i>Catálogo de Servicios</h3>
            <p class="text-secondary mb-0 small">Gestión de software, licencias y control de versiones.</p>
        </div>
        <button class="btn btn-guinda shadow-sm fw-bold" id="btnNuevoServicio">
            <i class="fas fa-plus-circle me-1"></i> Agregar Servicio
        </button>
    </div>

    <div class="row g-4">
        <?php if(empty($servicios)): ?>
            <div class="col-12 text-center py-5 text-muted border border-dashed rounded-3 bg-light">
                <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i>
                <p class="mb-0">El catálogo de servicios está vacío.</p>
            </div>
        <?php else: ?>
            <?php foreach($servicios as $serv): 
                $icono = !empty($serv['icono']) ? $serv['icono'] : 'fas fa-cube text-dark';
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 shadow-sm border-0 ring-1 ring-secondary-subtle">
                    <div class="card-body p-4 position-relative">
                        
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded p-2 me-3 fs-4">
                                    <i class="<?php echo htmlspecialchars($icono); ?>"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($serv['nom']); ?></h5>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle mt-1">
                                        <?php echo htmlspecialchars($serv['tipo_serv']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border-0 text-muted" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><a class="dropdown-item py-2 btn-versiones" href="#" data-id="<?php echo $serv['id_serv']; ?>" data-nom="<?php echo htmlspecialchars($serv['nom']); ?>"><i class="fas fa-code-branch me-2 text-primary"></i> Control de Versiones</a></li>
                                    <li><a class="dropdown-item py-2 btn-editar-serv" href="#" data-id="<?php echo $serv['id_serv']; ?>" data-tipo="<?php echo htmlspecialchars($serv['tipo_serv']); ?>" data-nom="<?php echo htmlspecialchars($serv['nom']); ?>" data-especs="<?php echo htmlspecialchars($serv['especs']); ?>" data-icono="<?php echo htmlspecialchars($icono); ?>"><i class="fas fa-edit me-2 text-secondary"></i> Editar Datos</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2 text-danger btn-eliminar-serv" href="#" data-id="<?php echo $serv['id_serv']; ?>"><i class="fas fa-trash-alt me-2"></i> Eliminar Catálogo</a></li>
                                </ul>
                            </div>
                        </div>

                        <div class="bg-light rounded-3 p-3 mb-3 border">
                            <div class="d-flex flex-column mb-2 small">
                                <span class="text-muted fw-bold mb-1">Versiones Aceptadas:</span>
                                <span class="text-success"><i class="fas fa-check-circle me-1"></i><?php echo htmlspecialchars($serv['versiones_aceptadas']); ?></span>
                            </div>
                            <div class="small text-muted mt-2 border-top pt-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($serv['especs']); ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between text-muted small fw-bold px-1">
                            <span><i class="fas fa-desktop me-1 text-guinda"></i> <?php echo $serv['total_instalaciones']; ?> Equipos</span>
                            <span><i class="fas fa-code-branch me-1"></i> <?php echo $serv['total_versiones']; ?> Versiones</span>
                        </div>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalFormServicio" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="tituloModalServ"><i class="fas fa-cube me-2 text-guinda"></i>Catálogo de Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formServicio" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_serv" id="serv_id">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Tipo de Servicio *</label>
                            <input type="text" class="form-control focus-guinda" name="tipo_serv" id="serv_tipo" placeholder="Ej. Sistema Operativo" required maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nombre del Software *</label>
                            <input type="text" class="form-control focus-guinda" name="nom" id="serv_nom" placeholder="Ej. Windows 11" required maxlength="30">
                        </div>
                    </div>

                    <div class="mb-3" id="bloque_version_inicial">
                        <label class="form-label fw-bold small text-primary">Versión Inicial *</label>
                        <input type="text" class="form-control focus-guinda border-primary-subtle" name="version_inicial" id="serv_ver_ini" placeholder="Ej. 22H2 Pro">
                        <small class="text-muted">Se registrará como Aceptada (Vigente) por defecto.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Icono Representativo *</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-light text-center justify-content-center" style="width: 50px;" id="preview-icono">
                                <i class="fas fa-cube fs-5"></i>
                            </span>
                            <select class="form-select focus-guinda" name="icono" id="serv_icono" required></select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Especificaciones / Licencia</label>
                        <textarea class="form-control focus-guinda" name="especs" id="serv_especs" rows="3" placeholder="Detalles de instalación o licencias..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-guinda fw-bold"><i class="fas fa-save me-1"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVersiones" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white border-bottom-0">
                <h5 class="modal-title fw-bold" id="tituloModalVer"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-light">
                <div class="p-3 bg-white border-bottom">
                    <form id="formNuevaVersion" class="row g-2 align-items-center">
                        <input type="hidden" id="ver_id_serv" name="id_serv">
                        <div class="col-md-6">
                            <input type="text" class="form-control focus-guinda" name="nueva_version" id="nueva_version" placeholder="Registrar nueva versión (Ej. 23H2)" required>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input focus-guinda" type="checkbox" name="marcar_aceptada" value="1" id="marcarAceptada" checked>
                                <label class="form-check-label small fw-bold text-success" for="marcarAceptada">Aceptada (Vigente)</label>
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="submit" class="btn btn-sm btn-success w-100 fw-bold"><i class="fas fa-plus"></i> Añadir</button>
                        </div>
                    </form>
                </div>
                <div class="table-responsive" style="max-height: 40vh;">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-4">Versión</th>
                                <th>Estatus</th>
                                <th class="text-end pe-4">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyVersiones"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .focus-guinda:focus { border-color: #9D2449; box-shadow: 0 0 0 0.25rem rgba(157, 36, 73, 0.25); }
    .text-guinda { color: #9D2449 !important; }
    .btn-guinda { background-color: #9D2449; color: white; border: none; }
</style>