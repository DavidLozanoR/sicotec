<style>
    .columna-red-dinamica span.fw-bold {
        background-color: #cff4fc !important; /* Fondo azul info */
        color: #055160 !important; /* Texto azul oscuro */
        border: 1px solid #b6effb;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        font-family: monospace;
        font-size: 0.85rem;
        display: inline-block;
        margin-bottom: 0.2rem;
    }
    .columna-red-dinamica small.text-muted {
        color: #9D2449 !important; /* Color Guinda para la VLAN */
        font-weight: 700;
        font-size: 0.75rem !important;
    }
</style>
<div class="container-fluid px-4 py-3" id="main-dispositivos-container" data-id-sub="<?php echo $id_sub_actual ?? ''; ?>">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0" style="color: #333;">Catálogo de Dispositivos</h3>
            <p class="text-secondary mb-0">Consulta y gestión de equipos autorizados</p>
        </div>
        <a href="dispositivos/formulario/<?php echo $id_sub_actual; ?>/" class="btn btn-guinda px-4 py-2 shadow-sm">
            <i class="fas fa-plus me-2"></i> <span class="d-none d-sm-inline">Nuevo Dispositivo</span>
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

            <div class="row g-2 mb-3 align-items-center">
                <div class="col-12 col-lg-4">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-secondary-subtle"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="buscarDispositivo" class="form-control border-secondary-subtle focus-guinda" placeholder="Buscar equipo, serie o software...">
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-lg-3">
                    <select class="form-select shadow-sm focus-guinda border-secondary-subtle" id="filtroAsignacion">
                        <option value="todos">Asignación: Todas</option>
                        <option value="asignado">Equipos Asignados</option>
                        <option value="sin_asignar">Sin Asignar (Almacén)</option>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <select class="form-select shadow-sm focus-guinda border-secondary-subtle" id="filtroDepartamento">
                        <option value="todos">Red: Todas</option>
                        <option value="sin_red">Sin red / VLAN</option>
                        <?php foreach($departamentos as $dep): ?>
                            <option value="<?php echo $dep['id_vlan']; ?>"><?php echo htmlspecialchars($dep['nom_vlan']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-lg-2 d-flex justify-content-lg-end">
                    <select class="form-select shadow-sm focus-guinda border-secondary-subtle w-100" id="selectorRegistros">
                        <option value="10">10 filas</option>
                        <option value="25">25 filas</option>
                        <option value="50">50 filas</option>
                        <option value="all" selected>Todo</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <nav aria-label="Navegación de registros" class="overflow-auto">
                    <ul class="pagination pagination-sm mb-0 shadow-sm" id="paginacionTabla"></ul>
                </nav>
            </div>

            <div class="table-responsive border rounded-3" style="max-height: 500px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top" style="z-index: 2;">                        
                        <tr>
                            <th>Hostname</th>
                            <th class="d-none d-sm-table-cell">Marca / Modelo</th>
                            <th class="d-none d-lg-table-cell">No. Serie</th>
                            <th class="d-none d-md-table-cell">Dirección MAC</th>
                            <th>Conexión / VLAN</th>
                            <th class="d-none d-sm-table-cell">Responsable</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($dispositivos && count($dispositivos) > 0): ?>
                            <?php foreach ($dispositivos as $disp): ?>
                                <tr data-software="<?php echo htmlspecialchars($disp['str_software'] ?? ''); ?>" 
                                    data-estado="<?php echo $disp['estado_asig'] ?? 'sin_asignar'; ?>" 
                                    data-dep="<?php echo $disp['id_dep'] ?? 'sin_red'; ?>">
                                    
                                    <td class="fw-bold text-dark">
                                        <?php echo htmlspecialchars($disp['nom_host'] ?? ''); ?>
                                        <?php if (!empty($disp['tiene_obsoleto'])): ?>
                                            <br>
                                            <span class="badge bg-danger mt-1 shadow-sm">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Software Obsoleto
                                            </span>
                                        <?php endif; ?>
                                        <div class="d-block d-sm-none text-muted fw-normal mt-1" style="font-size: 0.75rem;">
                                            <?php echo htmlspecialchars(($disp['marca'] ?? '') . ' / ' . ($disp['modelo'] ?? '')); ?>
                                        </div>
                                    </td>
                                    
                                    <td class="d-none d-sm-table-cell">
                                        <?php echo htmlspecialchars(($disp['marca'] ?? '') . ' / ' . ($disp['modelo'] ?? '')); ?><br>
                                        <small class="text-muted"><strong class="text-dark">Inv:</strong> <?php echo htmlspecialchars($disp['num_inv'] ?? ''); ?></small>
                                    </td>
                                    
                                    <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($disp['num_ser'] ?? ''); ?></td>
                                    
                                    <td class="d-none d-md-table-cell" style="white-space: normal; min-width: 170px;">
                                        <?php if (!empty($disp['mac_ethernet'])): ?>
                                            <div title="MAC Ethernet" style="font-size: 0.8rem; line-height: 1.2;">
                                                <i class="fas fa-ethernet text-secondary" style="width: 16px;"></i> 
                                                <span class="font-monospace fw-bold text-dark"><?php echo htmlspecialchars($disp['mac_ethernet']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($disp['mac_wifi'])): ?>
                                            <div title="MAC WiFi" class="<?php echo !empty($disp['mac_ethernet']) ? 'mt-1' : ''; ?>" style="font-size: 0.8rem; line-height: 1.2;">
                                                <i class="fas fa-wifi text-secondary" style="width: 16px;"></i> 
                                                <span class="font-monospace fw-bold text-dark"><?php echo htmlspecialchars($disp['mac_wifi']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (empty($disp['mac_ethernet']) && empty($disp['mac_wifi'])): ?>
                                            <span class="text-muted small fst-italic">Sin registro físico</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if(!empty($disp['info_red_html'])): ?>
                                            <?php echo $disp['info_red_html']; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border border-secondary-subtle px-2 py-1">
                                                <i class="fas fa-unlink me-1"></i> Sin Red
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="d-none d-sm-table-cell">
                                        <?php if(!empty($disp['responsable'])): ?>
                                            <span class="text-dark">
                                                <i class="fas fa-user-tie me-2 text-secondary"></i><?php echo htmlspecialchars($disp['responsable']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-25 text-dark border shadow-sm">
                                                <i class="fas fa-box me-1"></i> En Almacén
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center">
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