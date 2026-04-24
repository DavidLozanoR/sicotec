<?php
// Variables recibidas del controlador PersonalOPFormController
$id_po = $id_po ?? null;
$personal = $personal ?? null;
$departamentos = $departamentos ?? [];
$equipos_asignados = $equipos_asignados ?? [];
$equipos_responsables = $equipos_responsables ?? [];
$id_sub_actual = $id_sub_actual ?? 1;
$id_sub_dispositivos = $id_sub_dispositivos ?? 8;
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
                <div class="card shadow-sm border-0 rounded-3 sticky-top" style="top: 80px; z-index:1;">
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
                                Dispositivos Asignados
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
                                        <div class="invalid-feedback">Por favor, ingrese el nombre(s) minimo 3 carácteres.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-dark mb-1">Apellido Paterno *</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle" name="ape_pa" value="<?php echo $personal['ape_pa'] ?? ''; ?>" required>
                                        <div class="invalid-feedback">El apellido paterno es obligatorio, minimo 3 carácteres.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-dark mb-1">Apellido Materno</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle" name="ape_ma" value="<?php echo $personal['ape_ma'] ?? ''; ?>" required>
                                        <div class="invalid-feedback">El apellido materno es obligatorio, minimo 3 carácteres.</div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-dark mb-1">No. de Cobro *</label>
                                        <input type="number" class="form-control focus-guinda border-secondary-subtle" name="num_cobro" value="<?php echo $personal['num_cobro'] ?? ''; ?>" required>
                                        <div class="invalid-feedback">El número de cobro es obligatorio.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-dark mb-1">Extensión</label>
                                        <input type="number" class="form-control focus-guinda border-secondary-subtle" name="ext" value="<?php echo $personal['ext'] ?? ''; ?>">
                                        <div class="invalid-feedback">La extensión es obligatoria y debe ser numérica.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-dark mb-1">No. Cuenta Dom.</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle" name="cta_dom" value="<?php echo $personal['cta_dom'] ?? ''; ?>">
                                        <div class="invalid-feedback">El No. de cuenta de dominio es obligatorio.</div>
                                    </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold small text-dark mb-1">Dirección Completa *</label>
                                            <textarea class="form-control focus-guinda border-secondary-subtle" name="dir" rows="3" ><?php echo $personal['dir'] ?? ''; ?></textarea>
                                            <div class="invalid-feedback">Por favor, proporcione la dirección completa.</div>
                                        </div>
                                        
                                        <div class="col-12 mt-4 text-end border-top border-secondary-subtle pt-4">
                                            <button type="button" class="btn btn-outline-secondary fw-bold px-4 btn-siguiente" data-next="v-pills-area-tab">
                                                Siguiente: Área y Puesto <i class="fas fa-arrow-right ms-2"></i>
                                            </button>
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
                                        <div class="dropdown w-100">
                                            <?php
                                                // Lógica para determinar el texto y valor inicial (Modo Nuevo vs Modo Edición)
                                                $texto_inicial = "Seleccione el departamento...";
                                                $valor_inicial = "";
                                                if (isset($personal) && !empty($personal['id_dep'])) {
                                                    foreach($departamentos as $dep) {
                                                        if ($personal['id_dep'] == $dep['id_dep']) {
                                                            $texto_inicial = htmlspecialchars($dep['nom_dep']);
                                                            $valor_inicial = $dep['id_dep'];
                                                            break;
                                                        }
                                                    }
                                                }
                                            ?>
                                            
                                            <button class="btn btn-white form-select d-flex justify-content-between align-items-center shadow-sm focus-guinda border-secondary-subtle bg-white" type="button" id="btnSelectDepForm" data-bs-toggle="dropdown" aria-expanded="false" data-bs-display="static">
                                                <span id="textoSelectDepForm" class="text-truncate text-start"><?php echo $texto_inicial; ?></span>
                                            </button>
                                            
                                            <ul class="dropdown-menu w-100 " aria-labelledby="btnSelectDepForm" style="max-height: 30vh; overflow-y: auto;">
                                                <li>
                                                    <a class="dropdown-item item-dep-form text-muted border-bottom" href="#" data-value="" data-text="Seleccione el departamento..." style="white-space: normal; word-wrap: break-word; font-size: clamp(0.8rem, 3vw, 0.9rem); padding: 10px 12px;">
                                                        <em>Seleccione el departamento...</em>
                                                    </a>
                                                </li>
                                                <?php foreach($departamentos as $dep): ?>
                                                    <li>
                                                        <a class="dropdown-item item-dep-form text-wrap <?php echo ($valor_inicial == $dep['id_dep']) ? 'active bg-light text-dark fw-bold' : ''; ?>" href="#" data-value="<?php echo $dep['id_dep']; ?>" data-text="<?php echo htmlspecialchars($dep['nom_dep']); ?>" style="white-space: normal; word-wrap: break-word; font-size: clamp(0.8rem, 3vw, 0.9rem); padding: 10px 12px; border-bottom: 1px solid #f1f1f1; line-height: 1.3;">
                                                            <?php echo htmlspecialchars($dep['nom_dep']); ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <input type="hidden" name="id_dep" id="inputHiddenDepForm" value="<?php echo $valor_inicial; ?>" required>
                                        </div>
                                        <div class="invalid-feedback">Debe asignar un área o departamento.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Puesto Operativo *</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle" name="puesto" value="<?php echo $personal['puesto'] ?? ''; ?>" required>
                                        <div class="invalid-feedback">El puesto operativo es obligatorio.</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Correo Institucional *</label>
                                        <div class="input-group has-validation">
                                            <?php 
                                                $correo_parts = isset($personal['correo']) && !empty($personal['correo']) ? explode('@', $personal['correo']) : [''];
                                                $prefijo = $correo_parts[0];
                                            ?>
                                            <input type="text" class="form-control focus-guinda border-end-0 border-secondary-subtle" name="correo_prefijo" value="<?php echo htmlspecialchars($prefijo); ?>" placeholder="usuario" required>
                                            <span class="input-group-text bg-light text-muted border-start-0 border-secondary-subtle">@inea.gob.mx</span>
                                            <div class="invalid-feedback">Ingrese el prefijo del correo institucional.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                            <label class="form-label fw-bold small text-dark mb-1">Estatus del Empleado</label>
                                        <select class="form-select focus-guinda border-secondary-subtle" name="estatus">
                                            <option value="1" <?php echo (isset($personal) && $personal['estatus'] == 1) ? 'selected' : ''; ?>>Activo (Laborando)</option>
                                            
                                            <?php if ($id_po): ?>
                                                <option value="0" <?php echo (isset($personal) && $personal['estatus'] == 0) ? 'selected' : ''; ?>>Inactivo / Baja</option>
                                            <?php endif; ?>
                                        </select>
                                        </div>
                                        
                                        <div class="col-12 mt-4 text-end border-top border-secondary-subtle pt-4">
                                            <button type="button" class="btn btn-outline-secondary fw-bold px-4 btn-siguiente" data-next="v-pills-equipos-tab">
                                                Siguiente: Dispositivos <i class="fas fa-arrow-right ms-2"></i>
                                            </button>
                                        </div>
                                        </div>
                                </div>

                                <div class="tab-pane fade" id="v-pills-equipos" role="tabpanel" aria-labelledby="v-pills-equipos-tab" data-sub-disp="<?php echo $id_sub_dispositivos; ?>">                               
                                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary-subtle pb-3">
                                    <div>
                                        <h5 class="fw-bold text-guinda mb-0">Dispositivos Vinculados</h5>
                                        <small class="text-muted">Dispositivos asignados para uso diario o bajo resguardo legal.</small>
                                    </div>
                                    <?php if($personal): ?>
                                        <a href="dispositivos/" class="btn btn-sm btn-guinda shadow-sm" id="btnVincularDispositivo">
                                            <i class="fas fa-plus me-1"></i> Añadir Dispositivo
                                        </a>
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
                                                                    <h6 class="fw-bold text-dark mb-1 text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($disp['nom_host'] ?? 'Dispositivo'); ?>">
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
                                                                        <a class="dropdown-item py-2" href="dispositivos/formulario/<?php echo $id_sub_dispositivos; ?>/<?php echo $disp['id_disp']; ?>">
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
                                                                    <?php echo htmlspecialchars($disp['dir_ip'] ?? 'N/A'); ?>
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
</style>