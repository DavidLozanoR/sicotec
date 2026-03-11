<?php
// Variables esperadas desde el PersonalOPController.php
$lista_personal = $lista_personal ?? [];
$departamentos = $departamentos ?? [];
?>

<div class="space-y-6">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h3 font-weight-bold mb-1 text-dark">Gestión de Personal Operativo</h2>
            <p class="text-muted mb-0">Administración integral del directorio de trabajadores y sus equipos</p>
        </div>
        <button type="button" class="btn btn-inea d-flex align-items-center gap-2 px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPersonal">
            <i class="fas fa-plus"></i> Nuevo Personal
        </button>
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
                                        <button class="btn btn-sm btn-light text-primary btn-editar shadow-sm me-1" 
                                            data-id="<?php echo $po['id_po']; ?>"
                                            title="Editar Personal">
                                            <i class="fas fa-edit" style="pointer-events: none;"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light text-danger btn-baja shadow-sm" 
                                            data-id="<?php echo $po['id_po']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($po['nombres'].' '.$po['ape_pa']); ?>"
                                            data-equipos="<?php echo $po['total_equipos'] ?? 0; ?>"
                                            title="Dar de Baja">
                                            <i class="fas fa-trash-alt" style="pointer-events: none;"></i>
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

<div class="modal fade" id="modalPersonal" tabindex="-1" aria-labelledby="modalPersonalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            
            <div class="modal-header text-white" style="background-color: #9D2449;">
                <h5 class="modal-title fw-bold" id="modalPersonalLabel">Nuevo Personal Operativo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body bg-light p-4">
                <form id="formPersonal">
                    <input type="hidden" id="peticion_input" name="peticion" value="INSERT">
                    <input type="hidden" id="id_po_input" name="id_po" value="">
                    
                    <ul class="nav nav-tabs mb-4" id="personalTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="caracteristicas-tab" data-bs-toggle="tab" data-bs-target="#caracteristicas" type="button" role="tab" aria-selected="true">
                                <i class="fas fa-id-card me-2"></i> Datos del Trabajador
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="dispositivos-tab" data-bs-toggle="tab" data-bs-target="#dispositivos" type="button" role="tab" aria-selected="false">
                                <i class="fas fa-laptop me-2"></i> Dispositivos Asignados
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="personalTabsContent">
                        
                        <div class="tab-pane fade show active" id="caracteristicas" role="tabpanel">
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-tools fa-3x mb-3 opacity-25"></i>
                                <h5>Formulario de registro en construcción</h5>
                                <p class="small">Aquí irán los inputs de nombre, apellidos, dirección, etc.</p>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="dispositivos" role="tabpanel">
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-network-wired fa-3x mb-3 opacity-25"></i>
                                <h5>Lógica de dispositivos en construcción</h5>
                                <p class="small">Aquí se mostrará la tabla de equipos asignados a este empleado.</p>
                            </div>
                        </div>
                        
                    </div>
                </form>
            </div>
            
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-outline-secondary px-4 shadow-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formPersonal" class="btn btn-inea px-4 d-flex align-items-center gap-2 shadow-sm">
                    <i class="fas fa-save"></i> Guardar Personal
                </button>
            </div>
            
        </div>
    </div>
</div>