<div class="container-fluid px-4 py-3" id="main-dispositivos-container" data-id-sub="<?php echo $id_sub_actual ?? ''; ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0" style="color: #333;">Catálogo de Dispositivos</h3>
            <p class="text-secondary mb-0">Consulta y gestión de equipos autorizados</p>
        </div>
        <button class="btn btn-guinda px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDispositivo">
            <i class="fas fa-plus me-2"></i> Nuevo Dispositivo
        </button>
    </div>

    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-4">
                <i class="fas fa-laptop text-guinda me-2"></i> Lista de Dispositivos
            </h6>

            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Hostname</th>
                            <th>Marca / Modelo</th>
                            <th>No. Serie</th>
                            <th>Red (IP)</th>
                            <th>Servicio Asignado</th>
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
                                    <td>
                                        <?php if($disp['ip']): ?>
                                            <span class="badge bg-info text-dark">
                                                <i class="fas fa-network-wired me-1"></i><?php echo htmlspecialchars($disp['ip']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin red asignada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($disp['nom_servicio']): ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-box me-1"></i><?php echo htmlspecialchars($disp['nom_servicio']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">Ninguno</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-editar-disp"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalDispositivo"
                                            data-id="<?php echo $disp['id_disp']; ?>"
                                            data-host="<?php echo htmlspecialchars($disp['nom_host']); ?>"
                                            data-marca="<?php echo htmlspecialchars($disp['marca']); ?>"
                                            data-modelo="<?php echo htmlspecialchars($disp['modelo']); ?>"
                                            data-serie="<?php echo htmlspecialchars($disp['num_ser']); ?>"
                                            data-inv="<?php echo htmlspecialchars($disp['num_inv']); ?>"
                                            data-dom="<?php echo htmlspecialchars($disp['dom']); ?>"
                                            data-hw="<?php echo htmlspecialchars($disp['especs_hw']); ?>"
                                            data-resp="<?php echo htmlspecialchars($disp['resp_po_id']); ?>"
                                            data-asig="<?php echo htmlspecialchars($disp['asig_po_id']); ?>"
                                            data-id_red="<?php echo htmlspecialchars($disp['id_red']); ?>"
                                            data-ip_actual="<?php echo htmlspecialchars($disp['ip']); ?>"
                                            data-id_serv="<?php echo htmlspecialchars($disp['id_serv']); ?>"
                                            title="Ver / Editar Detalles">
                                            <i class="fas fa-edit" style="pointer-events: none;"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
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

<div class="modal fade" id="modalDispositivo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header border-0 pb-0 mt-2 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalDispositivoLabel">Detalles del Dispositivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1 px-4">
                <form id="formDispositivo" novalidate>
                    <input type="hidden" id="peticion_disp" name="peticion" value="INSERT">
                    <input type="hidden" id="id_disp_input" name="id_disp" value="">

                    <div class="row mb-3 mt-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">Hostname *</label>
                            <input type="text" class="form-control focus-guinda border-secondary-subtle" id="nom_host_input" name="nom_host" maxlength="100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">Dominio</label>
                            <input type="text" class="form-control focus-guinda border-secondary-subtle" id="dom_input" name="dom" maxlength="100">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">Marca *</label>
                            <input type="text" class="form-control focus-guinda border-secondary-subtle" id="marca_input" name="marca" maxlength="50" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">Modelo *</label>
                            <input type="text" class="form-control focus-guinda border-secondary-subtle" id="modelo_input" name="modelo" maxlength="50" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">Número de Serie</label>
                            <input type="text" class="form-control focus-guinda border-secondary-subtle" id="num_ser_input" name="num_ser" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">Número de Inventario</label>
                            <input type="text" class="form-control focus-guinda border-secondary-subtle" id="num_inv_input" name="num_inv" maxlength="100">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">Asignación de Red (IP)</label>
                            <select class="form-select focus-guinda border-secondary-subtle" id="id_red_input" name="id_red">
                                <option value="">Seleccione una IP disponible...</option>
                                <?php if ($redes_disponibles): ?>
                                    <?php foreach ($redes_disponibles as $red): ?>
                                        <option value="<?php echo $red['id_red']; ?>">
                                            <?php echo htmlspecialchars($red['ip'] . ' (' . $red['mac'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">Servicio Asignado</label>
                            <select class="form-select focus-guinda border-secondary-subtle" id="id_serv_input" name="id_serv">
                                <option value="">Seleccione un servicio...</option>
                                <?php if ($servicios): ?>
                                    <?php foreach ($servicios as $serv): ?>
                                        <option value="<?php echo $serv['id_serv']; ?>">
                                            <?php echo htmlspecialchars($serv['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark mb-1">Especificaciones de Hardware</label>
                        <textarea class="form-control focus-guinda border-secondary-subtle" id="especs_hw_input" name="especs_hw" rows="3"></textarea>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">ID Responsable (PO)</label>
                            <input type="number" class="form-control focus-guinda border-secondary-subtle" id="resp_po_id_input" name="resp_po_id">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">ID Asignado (PO)</label>
                            <input type="number" class="form-control focus-guinda border-secondary-subtle" id="asig_po_id_input" name="asig_po_id">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 mb-2">
                        <button type="button" class="btn btn-outline-secondary me-3 px-4 fw-bold" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-guinda px-4 fw-bold" id="btnGuardarDispositivo">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>