<?php
// Extraemos variables enviadas desde el controlador
$departamentos = $departamentos ?? [];
$segmentos = $segmentos ?? [];
// Asumimos que el ID del submódulo actual (Red) está en la sesión o se pasa por defecto (ej. 3)
$id_sub_actual = $_SESSION['submodulo_actual'] ?? 3; 
?>

<div class="container-fluid px-4 py-3">
    
    <input type="hidden" id="id_sub_red" value="<?php echo $id_sub_actual; ?>">

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h3 class="fw-bold text-guinda mb-1"><i class="fas fa-network-wired me-2"></i>Infraestructura de Red</h3>
            <p class="text-secondary mb-0 small">Gestión de segmentos, IPs reservadas y asignaciones por departamento.</p>
        </div>
        <button class="btn btn-guinda shadow-sm fw-bold" id="btnNuevoSegmento">
            <i class="fas fa-plus-circle me-1"></i> Nuevo Segmento
        </button>
    </div>

    <div class="row g-4">
        <?php if(empty($segmentos)): ?>
            <div class="col-12 text-center py-5 text-muted border border-dashed rounded-3 bg-light">
                <i class="fas fa-network-wired fa-3x mb-3 opacity-25"></i>
                <p class="mb-0">No hay segmentos de red activos configurados en el sistema.</p>
            </div>
        <?php else: ?>
            <?php foreach($segmentos as $seg): 
                $stats = $seg['estadisticas'];
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 shadow-sm border-0 ring-1 ring-secondary-subtle">
                    <div class="card-body p-4">
                        
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold text-dark mb-1">
                                    <i class="fas fa-server text-guinda me-2"></i>Segmento <?php echo htmlspecialchars($seg['id_seg']); ?>
                                </h5>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle mt-1">
                                    <i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($seg['nom_dep'] ?? 'Sin asignar'); ?>
                                </span>
                            </div>
                            
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border-0 text-muted" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 ring-1 ring-secondary-subtle">
                                    <li><a class="dropdown-item py-2 btn-ver-ips" href="#" data-id="<?php echo $seg['id_seg']; ?>"><i class="fas fa-list-ol me-2 text-primary"></i> Explorar IPs</a></li>
                                    <li><a class="dropdown-item py-2 btn-editar-seg" href="#" data-id="<?php echo $seg['id_seg']; ?>" data-dep="<?php echo $seg['id_dep']; ?>" data-ini="<?php echo htmlspecialchars($seg['ip_ini']); ?>" data-lim="<?php echo htmlspecialchars($seg['ip_lim']); ?>"><i class="fas fa-edit me-2 text-secondary"></i> Editar Rango</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2 text-danger btn-eliminar-seg" href="#" data-id="<?php echo $seg['id_seg']; ?>"><i class="fas fa-trash-alt me-2"></i> Desactivar Segmento</a></li>
                                </ul>
                            </div>
                        </div>

                        <div class="bg-light rounded-3 p-3 mb-4 border">
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">Rango IP:</span>
                                <span class="fw-bold text-dark font-monospace"><?php echo htmlspecialchars($seg['ip_ini']); ?> - <?php echo htmlspecialchars($seg['ip_lim']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Capacidad Total:</span>
                                <span class="fw-bold text-dark"><?php echo number_format($stats['total']); ?> IPs</span>
                            </div>
                        </div>

                        <div class="mb-2 d-flex justify-content-between small fw-bold">
                            <span class="text-success"><?php echo number_format($stats['libres']); ?> Libres</span>
                            <span class="text-primary"><?php echo number_format($stats['ocupadas']); ?> Ocupadas</span>
                        </div>
                        <div class="progress mb-3 shadow-sm" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $stats['pct_ocupadas']; ?>%" title="Ocupadas"></div>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $stats['pct_reservadas']; ?>%" title="Reservadas"></div>
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $stats['pct_libres']; ?>%" title="Libres"></div>
                        </div>
                        
                        <?php if($stats['reservadas'] > 0): ?>
                            <div class="text-center small text-warning fw-bold">
                                <i class="fas fa-lock me-1"></i> <?php echo number_format($stats['reservadas']); ?> IPs Reservadas
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalFormSegmento" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold text-dark" id="tituloModalSegmento">Nuevo Segmento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formSegmento" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_seg" id="seg_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark mb-1">Departamento Asignado *</label>
                        <select class="form-select focus-guinda border-secondary-subtle" name="id_dep" id="seg_dep" required>
                            <option value="" selected disabled>Seleccione un departamento...</option>
                            <?php foreach($departamentos as $dep): ?>
                                <option value="<?php echo $dep['id_dep']; ?>"><?php echo htmlspecialchars($dep['nom_dep']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar un departamento.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">IP Inicial *</label>
                            <input type="text" class="form-control focus-guinda font-monospace" name="ip_ini" id="seg_ini" placeholder="Ej. 192.168.1.1" required>
                            <div class="invalid-feedback">Ingrese una IP válida.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">IP Límite *</label>
                            <input type="text" class="form-control focus-guinda font-monospace" name="ip_lim" id="seg_lim" placeholder="Ej. 192.168.1.254" required>
                            <div class="invalid-feedback">Ingrese una IP válida.</div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info small mt-4 mb-0 py-2 d-flex align-items-start">
                        <i class="fas fa-info-circle me-2 mt-1"></i>
                        <span>El sistema gestionará las direcciones IP automáticamente. Las IPs inactivas recuperadas conservarán su estado anterior si es posible.</span>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-guinda fw-bold" id="btnGuardarSeg"><i class="fas fa-save me-1"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalIps" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white border-bottom-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-list-ol me-2"></i> Explorador de Direcciones IP</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="buscadorIps" placeholder="Buscar IP, Estatus o Dispositivo (Host)...">
                    </div>
                </div>
                
                <div class="table-responsive" style="max-height: 50vh;">
                    <table class="table table-hover align-middle mb-0" id="tablaIps">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-4">Dirección IP</th>
                                <th>Estatus</th>
                                <th>Host / Dispositivo</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyIps">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .border-dashed { border-style: dashed !important; border-width: 2px !important; }
    .focus-guinda:focus { border-color: #9D2449; box-shadow: 0 0 0 0.25rem rgba(157, 36, 73, 0.25); }
    .text-guinda { color: #9D2449 !important; }
    .btn-guinda { background-color: #9D2449; color: white; border: none; }
    .btn-guinda:hover { background-color: #7a1c39; color: white; }
    .ring-1 { box-shadow: 0 0 0 1px rgba(0,0,0,.05); }
</style>