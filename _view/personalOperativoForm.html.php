<?php
// Variables recibidas del controlador PersonalOPFormController
$id_po = $id_po ?? null;
$personal = $personal ?? null;
$departamentos = $departamentos ?? [];
$equipos_asignados = $equipos_asignados ?? [];
$equipos_responsables = $equipos_responsables ?? [];
$id_sub_actual = $id_sub_actual ?? 1;

$modo = $personal ? 'Editar Empleado' : 'Añadir Empleado';

// =====================================================================
// LÓGICA DE VISTA: Unificamos los equipos para mostrarlos como Tarjetas
// =====================================================================
$todos_los_equipos = [];
if ($personal) {
    // 1. Procesamos los asignados (Uso Diario)
    foreach($equipos_asignados as $eq) {
        $eq['etiqueta'] = 'Uso Diario';
        $eq['color_badge'] = 'text-primary bg-primary bg-opacity-10 border-primary-subtle';
        $todos_los_equipos[$eq['id_disp']] = $eq;
    }
    // 2. Procesamos los responsables (Resguardo) y combinamos si se repiten
    foreach($equipos_responsables as $eq) {
        if(isset($todos_los_equipos[$eq['id_disp']])) {
            $todos_los_equipos[$eq['id_disp']]['etiqueta'] = 'Uso y Resguardo';
            $todos_los_equipos[$eq['id_disp']]['color_badge'] = 'text-success bg-success bg-opacity-10 border-success-subtle';
        } else {
            $eq['etiqueta'] = 'En Resguardo';
            $eq['color_badge'] = 'text-warning bg-warning bg-opacity-10 border-warning-subtle';
            $todos_los_equipos[$eq['id_disp']] = $eq;
        }
    }
}
?>

<div class="container-fluid px-4 py-3">
    
    <div class="d-flex align-items-center mb-4 gap-3">
        <a href="personal/" class="btn btn-light border shadow-sm rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
            <i class="fas fa-arrow-left text-muted"></i>
        </a>
        <div>
            <h3 class="fw-bold mb-0 text-dark"><?php echo $modo; ?></h3>
            <p class="text-secondary mb-0 small">Navegue por el menú lateral para completar la información</p>
        </div>
    </div>

    <form id="formPersonalOperativo" novalidate>
        
        <input type="hidden" name="id_po" id="id_po" value="<?php echo $id_po; ?>">
        <input type="hidden" name="id_sub" id="id_sub" value="<?php echo $id_sub_actual; ?>">
        
        <div class="row">
            
            <div class="col-md-3 mb-4">
                <div class="card shadow-sm border-0 rounded-3 sticky-top" style="top: 80px;">
                    <div class="card-body p-2">
                        <div class="nav flex-column nav-pills custom-pills-inea" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            
                            <button class="nav-link active text-start px-3 py-3 fw-bold mb-1 rounded-3 d-flex align-items-center" id="v-pills-datos-tab" data-bs-toggle="pill" data-bs-target="#v-pills-datos" type="button" role="tab" aria-selected="true">
                                <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-3 icon-box" style="width: 35px; height: 35px;">
                                    <i class="fas fa-id-card"></i>
                                </div>
                                Datos Generales
                            </button>
                            
                            <button class="nav-link text-start px-3 py-3 fw-bold mb-1 rounded-3 d-flex align-items-center" id="v-pills-area-tab" data-bs-toggle="pill" data-bs-target="#v-pills-area" type="button" role="tab" aria-selected="false">
                                <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-3 icon-box" style="width: 35px; height: 35px;">
                                    <i class="fas fa-building"></i>
                                </div>
                                Área y Puesto
                            </button>
                            
                            <button class="nav-link text-start px-3 py-3 fw-bold rounded-3 d-flex align-items-center" id="v-pills-equipos-tab" data-bs-toggle="pill" data-bs-target="#v-pills-equipos" type="button" role="tab" aria-selected="false">
                                <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-3 icon-box" style="width: 35px; height: 35px;">
                                    <i class="fas fa-laptop"></i>
                                </div>
                                Equipos Asignados
                            </button>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card shadow-sm border-0 rounded-3 mb-4" style="min-height: 400px;">
                    <div class="card-body p-4 p-md-5">
                        <div class="tab-content" id="v-pills-tabContent">
                            
                            <div class="tab-pane fade show active" id="v-pills-datos" role="tabpanel" aria-labelledby="v-pills-datos-tab">
                                <h5 class="fw-bold text-guinda mb-4 border-bottom border-secondary-subtle pb-3">
                                    Información Personal y de Contacto
                                </h5>
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-dark mb-1">Nombres *</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle" name="nombres" value="<?php echo $personal['nombres'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-dark mb-1">Apellido Paterno</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle" name="ape_pa" value="<?php echo $personal['ape_pa'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-dark mb-1">Apellido Materno</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle" name="ape_ma" value="<?php echo $personal['ape_ma'] ?? ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-dark mb-1">No. de Cobro *</label>
                                        <input type="number" class="form-control focus-guinda border-secondary-subtle" name="num_cobro" value="<?php echo $personal['num_cobro'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-dark mb-1">Extensión</label>
                                        <input type="number" class="form-control focus-guinda border-secondary-subtle" name="ext" value="<?php echo $personal['ext'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-dark mb-1">No. Cuenta Dom.</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle" name="cta_dom" value="<?php echo $personal['cta_dom'] ?? ''; ?>">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-dark mb-1">Dirección Completa</label>
                                        <textarea class="form-control focus-guinda border-secondary-subtle" name="dir" rows="3"><?php echo $personal['dir'] ?? ''; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="v-pills-area" role="tabpanel" aria-labelledby="v-pills-area-tab">
                                <h5 class="fw-bold text-guinda mb-4 border-bottom border-secondary-subtle pb-3">
                                    Asignación de Área y Accesos
                                </h5>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Área (Departamento) *</label>
                                        <select class="form-select focus-guinda border-secondary-subtle" name="id_dep" required>
                                            <option value="" selected disabled>Seleccione el departamento...</option>
                                            <?php foreach($departamentos as $dep): ?>
                                                <option value="<?php echo $dep['id_dep']; ?>" <?php echo (isset($personal) && $personal['id_dep'] == $dep['id_dep']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dep['nom_dep']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Puesto Operativo</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle" name="puesto" value="<?php echo $personal['puesto'] ?? ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Correo Institucional</label>
                                        <div class="input-group">
                                            <?php 
                                                $correo_parts = isset($personal['correo']) && !empty($personal['correo']) ? explode('@', $personal['correo']) : [''];
                                                $prefijo = $correo_parts[0];
                                            ?>
                                            <input type="text" class="form-control focus-guinda border-end-0 border-secondary-subtle" name="correo_prefijo" value="<?php echo htmlspecialchars($prefijo); ?>" placeholder="usuario">
                                            <span class="input-group-text bg-light text-muted border-start-0 border-secondary-subtle">@inea.gob.mx</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Estatus del Empleado</label>
                                        <select class="form-select focus-guinda border-secondary-subtle" name="estatus">
                                            <option value="1" <?php echo (isset($personal) && $personal['estatus'] == 1) ? 'selected' : ''; ?>>Activo (Laborando)</option>
                                            <option value="0" <?php echo (isset($personal) && $personal['estatus'] == 0) ? 'selected' : ''; ?>>Inactivo / Baja</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="v-pills-equipos" role="tabpanel" aria-labelledby="v-pills-equipos-tab">
                                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary-subtle pb-3">
                                    <div>
                                        <h5 class="fw-bold text-guinda mb-0">Dispositivos Vinculados</h5>
                                        <small class="text-muted">Equipos asignados para uso diario o bajo resguardo legal.</small>
                                    </div>
                                    <?php if($personal): ?>
                                        <button type="button" class="btn btn-sm btn-guinda shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDispositivo">
                                            <i class="fas fa-plus me-1"></i> Añadir Equipo
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <?php if(!$personal): ?>
                                    <div class="text-center py-5 text-muted border border-dashed rounded-3 bg-light">
                                        <i class="fas fa-save fa-3x mb-3 opacity-25"></i>
                                        <p class="mb-0">Debe guardar la información del empleado primero<br> para habilitar la asignación de equipos.</p>
                                    </div>
                                <?php elseif(empty($todos_los_equipos)): ?>
                                    <div class="text-center py-5 text-muted border border-dashed rounded-3 bg-light">
                                        <i class="fas fa-laptop fa-3x mb-3 opacity-25"></i>
                                        <p class="mb-0">Este empleado no tiene dispositivos vinculados en el sistema.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="row g-3">
                                        <?php foreach($todos_los_equipos as $disp): ?>
                                            <div class="col-md-12 col-xl-6">
                                                <div class="card h-100 border-secondary-subtle shadow-sm rounded-3">
                                                    <div class="card-body p-3">
                                                        
                                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="bg-light rounded p-2 text-secondary d-flex justify-content-center align-items-center border" style="width: 45px; height: 45px;">
                                                                    <i class="fas fa-desktop fa-lg"></i>
                                                                </div>
                                                                <div>
                                                                    <h6 class="fw-bold text-dark mb-1 text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($disp['nom_host'] ?? 'Equipo'); ?>">
                                                                        <?php echo htmlspecialchars($disp['nom_host'] ?? 'Equipo General'); ?>
                                                                    </h6>
                                                                    <span class="badge border <?php echo $disp['color_badge']; ?>" style="font-size: 0.70rem;">
                                                                        <?php echo $disp['etiqueta']; ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-light border-0 text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                    <i class="fas fa-ellipsis-v"></i>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 ring-1 ring-secondary-subtle">
                                                                    <li>
                                                                        <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#modalDispositivo" data-id="<?php echo $disp['id_disp']; ?>">
                                                                            <i class="fas fa-edit me-2 text-primary"></i> Editar Dispositivo
                                                                        </a>
                                                                    </li>
                                                                    <li><hr class="dropdown-divider"></li>
                                                                    <?php if($disp['etiqueta'] == 'Uso Diario' || $disp['etiqueta'] == 'Uso y Resguardo'): ?>
                                                                        <li>
                                                                            <a class="dropdown-item py-2 text-danger" href="#" data-action="quitar_uso" data-id="<?php echo $disp['id_disp']; ?>">
                                                                                <i class="fas fa-unlink me-2"></i> Quitar de Uso Diario
                                                                            </a>
                                                                        </li>
                                                                    <?php endif; ?>
                                                                    <?php if($disp['etiqueta'] == 'En Resguardo' || $disp['etiqueta'] == 'Uso y Resguardo'): ?>
                                                                        <li>
                                                                            <a class="dropdown-item py-2 text-danger" href="#" data-action="quitar_resguardo" data-id="<?php echo $disp['id_disp']; ?>">
                                                                                <i class="fas fa-file-signature me-2"></i> Liberar Resguardo
                                                                            </a>
                                                                        </li>
                                                                    <?php endif; ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="row g-2 small bg-light rounded-3 p-2 border">
                                                            <div class="col-6">
                                                                <div class="text-muted" style="font-size: 0.70rem;">Marca</div>
                                                                <div class="fw-bold text-dark text-truncate"><?php echo htmlspecialchars($disp['marca'] ?: 'N/A'); ?></div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted" style="font-size: 0.70rem;">Inventario</div>
                                                                <div class="fw-bold text-dark text-truncate"><?php echo htmlspecialchars($disp['num_inv'] ?: 'N/A'); ?></div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted" style="font-size: 0.70rem;">Modelo</div>
                                                                <div class="fw-bold text-dark text-truncate"><?php echo htmlspecialchars($disp['modelo'] ?: 'N/A'); ?></div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted" style="font-size: 0.70rem;">Dir. IP</div>
                                                                <div class="fw-bold text-dark text-truncate font-monospace">
                                                                    <?php echo htmlspecialchars($disp['dir_ip'] ?? '192.168.x.x'); ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted" style="font-size: 0.70rem;">No. Serie</div>
                                                                <div class="fw-bold text-dark text-truncate font-monospace"><?php echo htmlspecialchars($disp['num_ser'] ?: 'N/A'); ?></div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted" style="font-size: 0.70rem;">Dir. MAC</div>
                                                                <div class="fw-bold text-dark text-truncate font-monospace"><?php echo htmlspecialchars($disp['dir_mac'] ?: 'N/A'); ?></div>
                                                            </div>
                                                        </div>
                                                        
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-2 mb-5">
                    <a href="personal/" class="btn btn-outline-secondary px-4 fw-bold shadow-sm">Cancelar</a>
                    <button type="submit" class="btn btn-guinda px-4 fw-bold shadow-sm" id="btnGuardarPersonal">
                        <i class="fas fa-save me-2"></i> Guardar Empleado
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="modalDispositivo" tabindex="-1" aria-labelledby="modalDispositivoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            
            <div class="modal-header text-white" style="background-color: #9D2449;">
                <h5 class="modal-title fw-bold" id="modalDispositivoLabel">
                    <i class="fas fa-laptop me-2"></i> Formulario de Dispositivo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-0 bg-light">
                <form id="formDispositivo" novalidate>
                    <input type="hidden" name="peticion_disp" id="peticion_disp" value="INSERT">
                    <input type="hidden" name="id_disp" id="id_disp" value="">

                    <div class="bg-white border-bottom px-4 pt-3">
                        <ul class="nav nav-tabs border-0" id="dispTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold text-secondary custom-tab-inea" id="caract-tab" data-bs-toggle="tab" data-bs-target="#caract" type="button" role="tab" aria-selected="true">
                                    <i class="fas fa-list me-1"></i> Características
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold text-secondary custom-tab-inea" id="net-tab" data-bs-toggle="tab" data-bs-target="#net" type="button" role="tab" aria-selected="false">
                                    <i class="fas fa-network-wired me-1"></i> Network
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold text-secondary custom-tab-inea" id="serv-tab" data-bs-toggle="tab" data-bs-target="#serv" type="button" role="tab" aria-selected="false">
                                    <i class="fas fa-cogs me-1"></i> Servicios
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content p-4" id="dispTabsContent">
                        
                        <div class="tab-pane fade show active" id="caract" role="tabpanel" aria-labelledby="caract-tab">
                            
                            <div class="mb-4 bg-white p-3 rounded-3 border border-secondary-subtle shadow-sm">
                                <label class="form-label fw-bold small text-dark mb-3"><i class="fas fa-link me-1 text-guinda"></i> Relación de Asignación al Empleado *</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="checkbox" class="btn-check" id="check_uso" name="es_uso" value="1" autocomplete="off" checked>
                                        <label class="btn btn-outline-primary w-100 fw-bold d-flex flex-column align-items-center py-3 shadow-sm" for="check_uso">
                                            <i class="fas fa-desktop fa-2x mb-2"></i>
                                            Uso Diario (Asignado)
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="checkbox" class="btn-check" id="check_resguardo" name="es_resguardo" value="1" autocomplete="off">
                                        <label class="btn btn-outline-success w-100 fw-bold d-flex flex-column align-items-center py-3 shadow-sm" for="check_resguardo">
                                            <i class="fas fa-file-signature fa-2x mb-2"></i>
                                            Resguardo Legal (Responsable)
                                        </label>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-3 text-center"><i class="fas fa-info-circle me-1"></i> Puede seleccionar ambos roles simultáneamente.</small>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Tipo de Equipo *</label>
                                    <select class="form-select focus-guinda" name="id_tipo_d" required>
                                        <option value="" selected disabled>Seleccione...</option>
                                        <option value="1">Computadora de Escritorio</option>
                                        <option value="2">Laptop</option>
                                        <option value="3">Impresora</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Marca *</label>
                                    <select class="form-select focus-guinda" name="id_mar" required>
                                        <option value="" selected disabled>Seleccione...</option>
                                        <option value="1">Dell</option>
                                        <option value="2">HP</option>
                                        <option value="3">Lenovo</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Modelo</label>
                                    <input type="text" class="form-control focus-guinda" name="modelo" placeholder="Ej. Optiplex 3080">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Procesador</label>
                                    <input type="text" class="form-control focus-guinda" name="procesador" placeholder="Ej. Intel Core i5">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Memoria RAM</label>
                                    <input type="text" class="form-control focus-guinda" name="ram" placeholder="Ej. 16 GB">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Almacenamiento</label>
                                    <input type="text" class="form-control focus-guinda" name="almacenamiento" placeholder="Ej. 512 GB SSD">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-dark">No. de Serie *</label>
                                    <input type="text" class="form-control focus-guinda border-secondary-subtle" name="num_ser" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-dark">No. de Inventario</label>
                                    <input type="text" class="form-control focus-guinda border-secondary-subtle" name="num_inv">
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="net" role="tabpanel" aria-labelledby="net-tab">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Nombre de Host (Hostname)</label>
                                    <input type="text" class="form-control focus-guinda font-monospace" name="nom_host" placeholder="Ej. PC-INEA-01">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Dirección MAC</label>
                                    <input type="text" class="form-control focus-guinda font-monospace" name="dir_mac" placeholder="00:1A:2B:3C:4D:5E">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Dirección IP</label>
                                    <input type="text" class="form-control focus-guinda font-monospace" name="dir_ip" placeholder="192.168.x.x">
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="serv" role="tabpanel" aria-labelledby="serv-tab">
                            
                            <div class="alert alert-light border border-info border-start border-5 shadow-sm d-flex align-items-center py-2 mb-4">
                                <i class="fas fa-info-circle text-info fa-lg me-3"></i>
                                <small class="text-muted">Marque los servicios instalados en el equipo. Si un servicio aparece resaltado en <strong class="text-danger">rojo</strong>, indica que la versión instalada está obsoleta respecto al catálogo oficial.</small>
                            </div>

                            <div class="list-group shadow-sm border-secondary-subtle mb-3">
                                
                                <label class="list-group-item d-flex justify-content-between align-items-center py-3 list-group-item-action cursor-pointer">
                                    <div class="d-flex align-items-start gap-3">
                                        <input class="form-check-input mt-1 focus-guinda border-secondary-subtle" type="checkbox" name="servicios[]" value="3" checked>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark">Antivirus <span class="text-muted fw-normal small">(Kaspersky Endpoint)</span></h6>
                                            <small class="text-muted d-block mt-1">Versión en equipo: <strong>Windows 10 Pro 22H2</strong></small>
                                        </div>
                                    </div>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-2 shadow-sm">
                                        <i class="fas fa-check-circle me-1"></i> Al día
                                    </span>
                                </label>

                                <label class="list-group-item d-flex justify-content-between align-items-center py-3 list-group-item-action cursor-pointer bg-danger bg-opacity-10 border-danger border-opacity-50">
                                    <div class="d-flex align-items-start gap-3">
                                        <input class="form-check-input mt-1 focus-guinda border-danger" type="checkbox" name="servicios[]" value="1" checked>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i> Sistema Operativo <span class="fw-normal small">(Windows Principal)</span>
                                            </h6>
                                            <small class="text-danger d-block mt-1">
                                                Instalado: <strong>Windows 10 Pro 22H2</strong><br>
                                                <span class="opacity-75">Catálogo oficial: Windows 11 Enterprise</span>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-danger text-white rounded-pill px-3 py-1 mb-2 shadow-sm">
                                            Obsoleto
                                        </span><br>
                                        <button type="button" class="btn btn-sm btn-outline-danger" style="font-size: 0.70rem; padding: 0.2rem 0.5rem;" title="Registrar actualización en sistema">
                                            <i class="fas fa-sync-alt me-1"></i> Actualizar a Oficial
                                        </button>
                                    </div>
                                </label>

                                <label class="list-group-item d-flex justify-content-between align-items-center py-3 list-group-item-action cursor-pointer bg-light">
                                    <div class="d-flex align-items-start gap-3 opacity-50">
                                        <input class="form-check-input mt-1 focus-guinda border-secondary-subtle" type="checkbox" name="servicios[]" value="2">
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark">Suite Ofimática <span class="text-muted fw-normal small">(Microsoft Office)</span></h6>
                                            <small class="text-muted d-block mt-1">Sin instalar</small>
                                        </div>
                                    </div>
                                </label>

                            </div>
                            
                        </div>

                    </div>
                </form>
            </div>
            
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-outline-secondary px-4 fw-bold shadow-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formDispositivo" class="btn btn-guinda px-4 fw-bold shadow-sm">
                    <i class="fas fa-save me-2"></i> Guardar Dispositivo
                </button>
            </div>
            
        </div>
    </div>
</div>

<style>
    /* Estilos del Menú Lateral Vertical */
    .custom-pills-inea .nav-link {
        color: #495057;
        background-color: transparent;
        border: 1px solid transparent;
        transition: all 0.2s ease-in-out;
    }
    .custom-pills-inea .nav-link:hover {
        background-color: #f8f9fa;
        color: #9D2449;
    }
    .custom-pills-inea .nav-link.active {
        color: #fff !important;
        background-color: #9D2449 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .custom-pills-inea .nav-link.active .icon-box {
        background-color: rgba(255,255,255,0.2) !important;
        color: #fff !important;
    }
    .border-dashed {
        border-style: dashed !important;
        border-width: 2px !important;
    }
    
    /* Estilos de las Pestañas (Tabs) del Modal de Dispositivos */
    .custom-tab-inea { 
        border: none !important; 
        border-bottom: 3px solid transparent !important; 
        background: transparent !important; 
    }
    .custom-tab-inea.active { 
        border-bottom-color: #9D2449 !important; 
        color: #9D2449 !important; 
    }

    /* Estilo para las filas seleccionables de los servicios */
    .cursor-pointer {
        cursor: pointer;
    }
</style>