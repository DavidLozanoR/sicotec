<link rel="stylesheet" href="css/dispositivosForm.css">
<?php
$id_disp = $id_disp ?? null;
$dispositivo = $dispositivo ?? null;
$redes_disponibles = $redes_disponibles ?? [];
$personal_operativo = $personal_operativo ?? [];
$servicios = $servicios ?? [];
$versiones = $versiones ?? [];
$servicios_asignados = $servicios_asignados ?? [];
$id_sub_actual = $id_sub_actual ?? 1;

$modo = $dispositivo ? 'Editar Dispositivo' : 'Añadir Dispositivo';
?>

<script>
    const catalogoServicios = <?php echo json_encode($servicios); ?>;
    const catalogoVersiones = <?php echo json_encode($versiones); ?>;
    const serviciosAsignadosIniciales = <?php echo json_encode($servicios_asignados); ?>;
</script>

<div class="container-fluid px-4 py-3">
    
    <div class="d-flex align-items-center mb-4 gap-3">
        <a href="dispositivos/" class="btn btn-light border shadow-sm rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
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
                            
                            <button class="nav-link text-start px-3 py-3 fw-bold mb-1 rounded-3 d-flex align-items-center" id="v-pills-asig-tab" data-bs-toggle="pill" data-bs-target="#v-pills-asig" type="button" role="tab" aria-selected="false">
                                <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-3 icon-box" style="width: 35px; height: 35px;">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                Asignación de Personal
                            </button>

                            <button class="nav-link text-start px-3 py-3 fw-bold rounded-3 d-flex align-items-center" id="v-pills-serv-tab" data-bs-toggle="pill" data-bs-target="#v-pills-serv" type="button" role="tab" aria-selected="false">
                                <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-3 icon-box" style="width: 35px; height: 35px;">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                Servicios Instalados
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
                                <h5 class="fw-bold text-guinda mb-4 border-bottom border-secondary-subtle pb-3">Características Físicas del Equipo</h5>
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
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="v-pills-net" role="tabpanel" aria-labelledby="v-pills-net-tab">
                                <h5 class="fw-bold text-guinda mb-4 border-bottom border-secondary-subtle pb-3">Configuración Lógica y de Red</h5>
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
                                <h5 class="fw-bold text-guinda mb-4 border-bottom border-secondary-subtle pb-3">Asignación de Personal Operativo</h5>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Personal Asignado (Uso Diario)</label>
                                        <select class="form-select focus-guinda border-secondary-subtle" name="asig_po_id">
                                            <option value="">-- Sin asignar --</option>
                                            <?php foreach($personal_operativo as $po): ?>
                                                <option value="<?php echo $po['id_po']; ?>" <?php echo (isset($dispositivo) && $dispositivo['asig_po_id'] == $po['id_po']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($po['nombre_completo']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-dark mb-1">Personal Responsable (Resguardo Legal)</label>
                                        <select class="form-select focus-guinda border-secondary-subtle" name="resp_po_id">
                                            <option value="">-- Sin asignar --</option>
                                            <?php foreach($personal_operativo as $po): ?>
                                                <option value="<?php echo $po['id_po']; ?>" <?php echo (isset($dispositivo) && $dispositivo['resp_po_id'] == $po['id_po']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($po['nombre_completo']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="v-pills-serv" role="tabpanel" aria-labelledby="v-pills-serv-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary-subtle pb-2">
                                    <h5 class="fw-bold text-guinda mb-0"><i class="fas fa-cogs me-2"></i>Servicios Instalados</h5>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary fw-bold me-2" id="btn-gestionar-catalogo">
                                            <i class="fas fa-list me-1"></i> Catálogo
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-guinda fw-bold" id="btn-add-servicio">
                                            <i class="fas fa-plus me-1"></i> Añadir Servicio
                                        </button>
                                    </div>
                                </div>

                                <div class="alert alert-light border border-info border-start border-5 shadow-sm d-flex align-items-center py-2 mb-4">
                                    <i class="fas fa-info-circle text-info fa-lg me-3"></i>
                                    <small class="text-muted">Marque los servicios instalados en el equipo. Si un servicio aparece resaltado en <strong class="text-danger">rojo</strong>, indica que la versión instalada está obsoleta respecto al catálogo oficial.</small>
                                </div>

                                <div id="contenedor-servicios" class="list-group shadow-sm border-secondary-subtle mb-3">
                                    <?php if (!empty($servicios_asignados)): ?>
                                        <?php foreach ($servicios_asignados as $sa): 
                                            $es_obsoleto = ($sa['id_ver_instalada'] != $sa['id_ver_oficial']);
                                            $clase_bg = $es_obsoleto ? 'bg-danger bg-opacity-10 border-danger border-opacity-50' : 'bg-white';
                                            $clase_texto = $es_obsoleto ? 'text-danger' : 'text-dark';
                                        ?>
                                            <label class="list-group-item d-flex justify-content-between align-items-center py-3 list-group-item-action <?php echo $clase_bg; ?>">
                                                <div class="d-flex align-items-start gap-3">
                                                    <input type="hidden" name="servicios_instalados[]" value="<?php echo $sa['id_serv']; ?>">
                                                    <input type="hidden" name="versiones_instaladas[<?php echo $sa['id_serv']; ?>]" value="<?php echo $sa['id_ver_instalada']; ?>">
                                                    <div>
                                                        <h6 class="mb-0 fw-bold <?php echo $clase_texto; ?>">
                                                            <?php if($es_obsoleto): ?><i class="fas fa-exclamation-triangle me-1"></i><?php endif; ?>
                                                            <?php echo htmlspecialchars($sa['nombre_servicio']); ?>
                                                        </h6>
                                                        <small class="<?php echo $es_obsoleto ? 'text-danger' : 'text-muted'; ?> d-block mt-1">
                                                            Instalado: <strong><?php echo htmlspecialchars($sa['version_instalada']); ?></strong>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="text-end d-flex align-items-center gap-2">
                                                    <?php if ($es_obsoleto): ?>
                                                        <span class="badge bg-danger text-white rounded-pill px-3 py-1 shadow-sm me-2">Obsoleto</span>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-actualizar-serv" data-id-serv="<?php echo $sa['id_serv']; ?>" title="Actualizar a versión oficial">
                                                            <i class="fas fa-sync-alt me-1"></i> Actualizar
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-2 shadow-sm me-2">
                                                            <i class="fas fa-check-circle me-1"></i> Al día
                                                        </span>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-quitar-serv" data-id-serv="<?php echo $sa['id_serv']; ?>" title="Desinstalar servicio">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-4 text-center text-muted" id="mensaje-sin-servicios">
                                            No hay servicios registrados en este equipo.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-2 mb-5">
                    <a href="dispositivos/" class="btn btn-outline-secondary px-4 fw-bold shadow-sm">Cancelar</a>
                    <button type="submit" class="btn btn-guinda px-4 fw-bold shadow-sm" id="btnGuardarDispositivo">
                        <i class="fas fa-save me-2"></i> Guardar Dispositivo
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>
<!-- modal para consultar servicios -->
<div class="modal fade" id="modalCatalogoServicios" tabindex="-1" aria-labelledby="modalCatalogoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: #9D2449;">
                <h5 class="modal-title fw-bold" id="modalCatalogoLabel">
                    <i class="fas fa-list me-2"></i> Catálogo Oficial de Servicios
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-4">Servicio / Software</th>
                                <th>Versión Oficial (Actual)</th>
                                <th>Otras Versiones Históricas</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-catalogo-servicios">
                            </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary px-4 fw-bold shadow-sm" data-bs-dismiss="modal">Cerrar Catálogo</button>
            </div>
        </div>
    </div>
</div>
