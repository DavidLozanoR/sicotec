<?php
$id_disp = $id_disp ?? null;
$dispositivo = $dispositivo ?? null;
$redes_disponibles = $redes_disponibles ?? [];
$servicios = $servicios ?? [];
$id_sub_actual = $id_sub_actual ?? 1;

$modo = $dispositivo ? 'Editar Dispositivo' : 'Añadir Dispositivo';
?>

<div class="container-fluid px-4 py-3">
    
    <div class="d-flex align-items-center mb-4 gap-3">
        <a href="configuracion/" class="btn btn-light border shadow-sm rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
            <i class="fas fa-arrow-left text-muted"></i>
        </a>
        <div>
            <h3 class="fw-bold mb-0 text-dark"><?php echo $modo; ?></h3>
            <p class="text-secondary mb-0 small">Navegue por el menú lateral para completar la información técnica</p>
        </div>
    </div>

    <form id="formDispositivo" novalidate>
        
        <input type="hidden" name="id_disp" id="id_disp" value="<?php echo $id_disp; ?>">
        <input type="hidden" name="id_sub" id="id_sub" value="<?php echo $id_sub_actual; ?>">
        <input type="hidden" name="peticion" value="<?php echo $dispositivo ? 'UPDATE' : 'INSERT'; ?>">
        
        <div class="row">
            
            <div class="col-md-3 mb-4">
                <div class="card shadow-sm border-0 rounded-3 sticky-top" style="top: 80px;">
                    <div class="card-body p-2">
                        <div class="nav flex-column nav-pills custom-pills-inea" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            
                            <button class="nav-link active text-start px-3 py-3 fw-bold mb-1 rounded-3 d-flex align-items-center" id="v-pills-hw-tab" data-bs-toggle="pill" data-bs-target="#v-pills-hw" type="button" role="tab" aria-selected="true">
                                <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-3 icon-box" style="width: 35px; height: 35px;">
                                    <i class="fas fa-microchip"></i>
                                </div>
                                Hardware
                            </button>
                            
                            <button class="nav-link text-start px-3 py-3 fw-bold mb-1 rounded-3 d-flex align-items-center" id="v-pills-net-tab" data-bs-toggle="pill" data-bs-target="#v-pills-net" type="button" role="tab" aria-selected="false">
                                <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-3 icon-box" style="width: 35px; height: 35px;">
                                    <i class="fas fa-network-wired"></i>
                                </div>
                                Red y Conexión
                            </button>
                            
                            <button class="nav-link text-start px-3 py-3 fw-bold rounded-3 d-flex align-items-center" id="v-pills-asig-tab" data-bs-toggle="pill" data-bs-target="#v-pills-asig" type="button" role="tab" aria-selected="false">
                                <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-3 icon-box" style="width: 35px; height: 35px;">
                                    <i class="fas fa-user-tag"></i>
                                </div>
                                Asignación y Servicios
                            </button>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card shadow-sm border-0 rounded-3 mb-4" style="min-height: 400px;">
                    <div class="card-body p-4 p-md-5">
                        <div class="tab-content" id="v-pills-tabContent">
                            
                            <div class="tab-pane fade show active" id="v-pills-hw" role="tabpanel" aria-labelledby="v-pills-hw-tab">
                                <h5 class="fw-bold text-guinda mb-4 border-bottom border-secondary-subtle pb-3">
                                    Características Físicas del Equipo
                                </h5>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Marca *</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle" name="marca" value="<?php echo htmlspecialchars($dispositivo['marca'] ?? ''); ?>" maxlength="50" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Modelo *</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle" name="modelo" value="<?php echo htmlspecialchars($dispositivo['modelo'] ?? ''); ?>" maxlength="50" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Número de Serie</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle font-monospace" name="num_ser" value="<?php echo htmlspecialchars($dispositivo['num_ser'] ?? ''); ?>" maxlength="100">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Número de Inventario</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle font-monospace" name="num_inv" value="<?php echo htmlspecialchars($dispositivo['num_inv'] ?? ''); ?>" maxlength="100">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-dark mb-1">Especificaciones de Hardware</label>
                                        <textarea class="form-control focus-guinda border-secondary-subtle" name="especs_hw" rows="4"><?php echo htmlspecialchars($dispositivo['especs_hw'] ?? ''); ?></textarea>
                                        <div class="form-text small text-muted">Ej: Procesador, RAM, Almacenamiento.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="v-pills-net" role="tabpanel" aria-labelledby="v-pills-net-tab">
                                <h5 class="fw-bold text-guinda mb-4 border-bottom border-secondary-subtle pb-3">
                                    Configuración Lógica y de Red
                                </h5>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Hostname *</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle font-monospace" name="nom_host" value="<?php echo htmlspecialchars($dispositivo['nom_host'] ?? ''); ?>" maxlength="100" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Dominio</label>
                                        <input type="text" class="form-control focus-guinda border-secondary-subtle" name="dom" value="<?php echo htmlspecialchars($dispositivo['dom'] ?? ''); ?>" maxlength="100">
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold small text-dark mb-1">Asignación de IP y Red</label>
                                        <select class="form-select focus-guinda border-secondary-subtle font-monospace" name="id_red">
                                            <option value="" selected>Sin red asignada (Equipo en almacén / Offline)</option>
                                            <?php foreach($redes_disponibles as $red): ?>
                                                <option value="<?php echo $red['id_red']; ?>" <?php echo (isset($dispositivo) && $dispositivo['id_red'] == $red['id_red']) ? 'selected' : ''; ?>>
                                                    IP: <?php echo htmlspecialchars($red['ip']); ?> - MAC: <?php echo htmlspecialchars($red['mac']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="v-pills-asig" role="tabpanel" aria-labelledby="v-pills-asig-tab">
                                <h5 class="fw-bold text-guinda mb-4 border-bottom border-secondary-subtle pb-3">
                                    Asignación de Personal y Software
                                </h5>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">ID PO Asignado (Uso Diario)</label>
                                        <input type="number" class="form-control focus-guinda border-secondary-subtle" name="asig_po_id" value="<?php echo htmlspecialchars($dispositivo['asig_po_id'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">ID PO Responsable (Resguardo Legal)</label>
                                        <input type="number" class="form-control focus-guinda border-secondary-subtle" name="resp_po_id" value="<?php echo htmlspecialchars($dispositivo['resp_po_id'] ?? ''); ?>">
                                    </div>

                                    <div class="col-md-12 mt-5">
                                        <label class="form-label fw-bold small text-dark mb-1"><i class="fas fa-cogs text-guinda me-1"></i> Servicio Asociado</label>
                                        <select class="form-select focus-guinda border-secondary-subtle" name="id_serv">
                                            <option value="" selected>Ningún servicio asignado...</option>
                                            <?php foreach($servicios as $serv): ?>
                                                <option value="<?php echo $serv['id_serv']; ?>" <?php echo (isset($dispositivo) && $dispositivo['id_serv'] == $serv['id_serv']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($serv['nom']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text small text-muted">Seleccione el servicio o catálogo de software vinculado a este equipo.</div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-2 mb-5">
                    <a href="dispositivos/" class="btn btn-light border shadow-sm rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-arrow-left text-muted"></i>
                    </a>
                    
                    <a href="dispositivos/" class="btn btn-outline-secondary px-4 fw-bold shadow-sm">Cancelar</a>
                    <button type="submit" class="btn btn-guinda px-4 fw-bold shadow-sm" id="btnGuardarDispositivo">
                        <i class="fas fa-save me-2"></i> Guardar Dispositivo
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>

<style>
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
</style>