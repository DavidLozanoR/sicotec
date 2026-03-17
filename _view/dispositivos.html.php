<link rel="stylesheet" href="css/dispositivos.css">

<div class="container-fluid px-4 py-3" id="main-dispositivos-container" data-id-sub="<?php echo $id_sub_actual ?? ''; ?>">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0" style="color: #333;">Catálogo de Dispositivos</h3>
            <p class="text-secondary mb-0">Consulta y gestión de equipos autorizados</p>
        </div>
        <a href="dispositivos/formulario/<?php echo $id_sub_actual; ?>/" class="btn btn-guinda px-4 py-2 shadow-sm">
            <i class="fas fa-plus me-2"></i> Nuevo Dispositivo
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            
            <div class="d-flex justify-content-between align-items-end mb-4 border-bottom pb-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-laptop text-guinda me-2"></i> Lista de Dispositivos
                </h6>
                <small class="text-muted" id="contador-registros">Mostrando <?php echo count($dispositivos ?? []); ?> registros</small>
            </div>

            <div class="row g-3 mb-3 align-items-center">
                <div class="col-12 col-md-5 col-lg-4">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-secondary-subtle"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="buscarDispositivo" class="form-control border-secondary-subtle focus-guinda" placeholder="Buscar por hostname, serie, marca o IP...">
                    </div>
                </div>

                <div class="col-12 col-md-7 col-lg-8 d-flex justify-content-md-end align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-muted mb-0">Mostrar</label>
                        <select class="form-select form-select-sm shadow-sm focus-guinda" id="selectorRegistros" style="width: 80px;">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">Todos</option>
                        </select>
                        <label class="small text-muted mb-0 d-none d-sm-block">registros</label>
                    </div>

                    <nav aria-label="Navegación de registros">
                        <ul class="pagination pagination-sm mb-0 shadow-sm" id="paginacionTabla">
                            </ul>
                    </nav>
                </div>
            </div>

            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">                        <tr>
                            <th>Hostname</th>
                            <th>Marca / Modelo</th>
                            <th>No. Serie</th>
                            <th>No. Inventario</th>
                            <th>Red (IP / MAC)</th>
                            <th>Responsable</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($dispositivos && count($dispositivos) > 0): ?>
                            <?php foreach ($dispositivos as $disp): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($disp['nom_host']); ?></td>
                                    <td><?php echo htmlspecialchars($disp['marca'] . ' / ' . $disp['modelo']); ?></td>
                                    <td><?php echo htmlspecialchars($disp['num_ser']); ?></td>
                                    <td><?php echo htmlspecialchars($disp['num_inv']); ?></td>
                                    <td>
                                        <?php if($disp['ip']): ?>
                                            <span class="badge bg-info text-dark d-block mb-1">
                                                <i class="fas fa-network-wired me-1"></i><?php echo htmlspecialchars($disp['ip']); ?>
                                            </span>
                                            <?php if($disp['mac']): ?>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                    <i class="fas fa-microchip me-1"></i><?php echo htmlspecialchars($disp['mac']); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin red asignada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($disp['responsable'])): ?>
                                            <span class="text-dark">
                                                <i class="fas fa-user-tie me-1 text-secondary"></i><?php echo htmlspecialchars($disp['responsable']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <a href="dispositivos/formulario/<?php echo $id_sub_actual; ?>/<?php echo $disp['id_disp']; ?>" 
                                            class="btn btn-sm btn-light text-primary shadow-sm me-1"
                                            title="Editar Detalles">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-light text-muted shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Más opciones">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 ring-1 ring-secondary-subtle">
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger py-2 btn-eliminar-disp" data-id="<?php echo $disp['id_disp']; ?>">
                                                        <i class="fas fa-trash-alt me-2"></i> Dar de Baja
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-laptop fa-3x mb-3" style="color: #e0e0e0;"></i><br>
                                    No se encontraron dispositivos en el catálogo.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>