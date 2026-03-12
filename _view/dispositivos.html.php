<div class="container-fluid px-4 py-3" id="main-dispositivos-container" data-id-sub="<?php echo $id_sub_actual ?? ''; ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="input-group" style="max-width: 400px;">
            <span class="input-group-text bg-white border-secondary-subtle"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="buscarDispositivo" class="form-control border-secondary-subtle focus-guinda" placeholder="Buscar por hostname, serie, marca o IP...">
        </div>
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
                                                <i class="fas fa-cogs me-1"></i><?php echo htmlspecialchars($disp['nom_servicio']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">Ninguno</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="dispositivos/formulario/<?php echo $id_sub_actual; ?>/<?php echo $disp['id_disp']; ?>" 
                                            class="btn btn-sm btn-outline-primary me-1"
                                            title="Editar Detalles">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-disp"
                                            data-id="<?php echo $disp['id_disp']; ?>"
                                            title="Eliminar Dispositivo">
                                            <i class="fas fa-trash-alt" style="pointer-events: none;"></i>
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