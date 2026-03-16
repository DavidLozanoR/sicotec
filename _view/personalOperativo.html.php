<?php
// Variables esperadas desde el PersonalOPController.php
$lista_personal = $lista_personal ?? [];
$departamentos = $departamentos ?? [];
$id_sub_actual = $id_sub_actual ?? 1; // ID del submódulo (por defecto 1 si no llega)
?>

<div class="space-y-6">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h3 font-weight-bold mb-1 text-dark">Gestión de Personal Operativo</h2>
            <p class="text-muted mb-0">Administración integral del directorio de trabajadores y sus equipos</p>
        </div>
        <a href="personal/formulario/<?php echo $id_sub_actual; ?>/" class="btn btn-inea d-flex align-items-center gap-2 px-4 shadow-sm">
            <i class="fas fa-plus"></i> Nuevo Personal
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom pt-4 pb-3 px-4">
            <h5 class="card-title fw-bold mb-1">Directorio de Personal Operativo</h5>
            <small class="text-muted" id="contador-registros">Mostrando <?php echo count($lista_personal); ?> registros</small>
        </div>
        
        <div class="card-body px-4 py-4">
            
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0 focus-guinda" id="buscarCobroNombre" placeholder="Buscar No. Cobro o Nombre...">
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <label class="small fw-bold text-nowrap mb-0 text-muted">Depto:</label>
                        <select class="form-select focus-guinda shadow-sm" id="filtroDepartamento">
                            <option value="all">Todos los departamentos</option>
                            <?php foreach($departamentos as $dep): ?>
                                <option value="<?php echo htmlspecialchars(strtolower($dep['nom_dep'])); ?>">
                                    <?php echo htmlspecialchars($dep['nom_dep']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-briefcase"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0 focus-guinda" id="buscarPuesto" placeholder="Filtrar por puesto...">
                    </div>
                </div>

                <div class="col-12 col-md-2 text-md-end">
                    <button class="btn btn-outline-secondary w-100 shadow-sm" id="btnLimpiarFiltros">
                        <i class="fas fa-eraser me-1"></i> Limpiar
                    </button>
                </div>
            </div>

            <div class="table-responsive border rounded shadow-sm">
                <table class="table table-hover align-middle mb-0" id="tablaPersonal">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th class="d-none d-sm-table-cell text-center" style="width: 60px;">ID</th>
                            <th>No. Cobro</th>
                            <th>Nombre Completo</th>
                            <th class="d-none d-md-table-cell">Departamento</th>
                            <th class="d-none d-lg-table-cell">Puesto</th>
                            <th class="text-center">Equipos</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyPersonal">
                        <?php if(!empty($lista_personal)): ?>
                            <?php foreach($lista_personal as $po): ?>
                                <tr class="fila-personal" 
                                    data-nombre="<?php echo strtolower($po['nombres'].' '.$po['ape_pa'].' '.$po['ape_ma']); ?>"
                                    data-cobro="<?php echo strtolower($po['num_cobro']); ?>"
                                    data-depto="<?php echo strtolower($po['nom_dep']); ?>"
                                    data-puesto="<?php echo strtolower($po['puesto']); ?>">
                                    
                                    <td class="d-none d-sm-table-cell text-center text-muted font-monospace small">
                                        <?php echo $po['id_po']; ?>
                                    </td>
                                    
                                    <td class="fw-medium text-guinda">
                                        <?php echo htmlspecialchars($po['num_cobro']); ?>
                                    </td>
                                    
                                    <td style="min-width: 200px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center text-muted" style="width: 32px; height: 32px;">
                                                <i class="fas fa-user small"></i>
                                            </div>
                                            <span class="fw-bold text-dark">
                                                <?php echo htmlspecialchars($po['nombres'].' '.$po['ape_pa'].' '.$po['ape_ma']); ?>
                                            </span>
                                        </div>
                                        <div class="d-block d-md-none text-muted extra-small mt-1 ms-4 ps-1">
                                            <i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($po['nom_dep']); ?>
                                        </div>
                                        <div class="d-block d-lg-none text-muted extra-small ms-4 ps-1">
                                            <i class="fas fa-briefcase me-1"></i> <?php echo htmlspecialchars($po['puesto']); ?>
                                        </div>
                                    </td>
                                    
                                    <td class="d-none d-md-table-cell text-muted small">
                                        <?php echo htmlspecialchars($po['nom_dep']); ?>
                                    </td>
                                    
                                    <td class="d-none d-lg-table-cell text-muted small">
                                        <?php echo htmlspecialchars($po['puesto']); ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <span class="badge <?php echo ($po['total_equipos'] > 0) ? 'bg-info text-dark' : 'bg-light text-muted'; ?> border d-inline-flex align-items-center gap-1 px-2 py-1">
                                            <i class="fas fa-laptop"></i> 
                                            <?php echo $po['total_equipos'] ?? 0; ?>
                                        </span>
                                    </td>
                                    
                                    <td class="text-end pe-3">
                                        <a href="personal/formulario/<?php echo $id_sub_actual; ?>/<?php echo $po['id_po']; ?>" 
                                           class="btn btn-sm btn-light text-primary shadow-sm me-1" 
                                           title="Editar Personal">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-baja-po" 
                                                data-id="<?php echo $po['id_po']; ?>" 
                                                data-sub="<?php echo $id_sub_actual; ?>" 
                                                data-nombre="<?php echo htmlspecialchars($po['nombres'] . ' ' . $po['ape_pa']); ?>" 
                                                title="Dar de Baja">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="fila-vacia">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-users-slash fa-3x mb-3 opacity-25"></i>
                                    <p class="mb-0">No se encontraron registros de personal operativo.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>