<style>
    /* Forzar colores institucionales sobre Bootstrap */
    .bg-guinda { background-color: #9D2449 !important; }
    .text-guinda { color: #9D2449 !important; }
    .focus-guinda:focus { border-color: #9D2449 !important; box-shadow: 0 0 0 0.25rem rgba(157, 36, 73, 0.25) !important; }
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h3 class="fw-bold text-guinda mb-0"><i class="fas fa-desktop me-2"></i><i class="fas fa-tags me-2"></i>Marcas y Modelos de Dispositivos</h3>
        <input type="hidden" id="id_sub_actual" value="<?php echo htmlspecialchars($id_sub ?? 1); ?>">
    </div>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #13322B;">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-copyright me-2"></i> Marcas Registradas</h6>
                    <?php if($puede_editar ?? false): ?>
                        <button class="btn btn-sm btn-light text-success fw-bold" onclick="abrirModalMarca()">
                            <i class="fas fa-plus"></i> Nueva
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="listaMarcas" style="max-height: 500px; overflow-y: auto;">
                        <?php if(empty($marcas)): ?>
                            <div class="p-4 text-center text-muted">No hay marcas registradas.</div>
                        <?php else: ?>
                            <?php foreach($marcas as $m): ?>
                                <button id="btn-marca-<?php echo $m['id_marca']; ?>" type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="seleccionarMarca(<?php echo $m['id_marca']; ?>, '<?php echo addslashes($m['nom_marca']); ?>')">
                                    
                                    <span class="fw-bold text-secondary text-marca-label"><?php echo htmlspecialchars($m['nom_marca']); ?></span>
                                    
                                    <div>
                                        <?php if($puede_editar ?? false): ?>
                                            <i class="fas fa-edit text-secondary me-3" style="cursor: pointer;" onclick="event.stopPropagation(); abrirModalMarca(<?php echo $m['id_marca']; ?>, '<?php echo addslashes($m['nom_marca']); ?>');" title="Editar Marca"></i>
                                        <?php endif; ?>
                                        <?php if($puede_eliminar ?? false): ?>
                                            <i class="fas fa-trash-alt text-danger" style="cursor: pointer;" onclick="event.stopPropagation(); eliminarRegistro('marcas', 'id_marca', <?php echo $m['id_marca']; ?>);" title="Eliminar Marca"></i>
                                        <?php endif; ?>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-guinda text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-laptop me-2"></i> Modelos: <span id="lblMarcaSeleccionada" class="text-light text-decoration-underline ms-1">Seleccione una marca</span></h6>
                    <?php if($puede_editar ?? false): ?>
                        <button class="btn btn-sm btn-light text-guinda fw-bold" id="btnNuevoModelo" onclick="abrirModalModelo()" disabled>
                            <i class="fas fa-plus"></i> Añadir Modelo
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-3">Nombre del Modelo</th>
                                    <?php if($puede_eliminar ?? false || $puede_editar ?? false): ?><th class="text-end pe-4" width="120">Acciones</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="tbodyModelos">
                                <tr><td colspan="2" class="text-center text-muted py-4">Seleccione una marca a la izquierda para ver sus modelos.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCatalogo" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #13322B;">
                <h6 class="modal-title fw-bold" id="modalCatalogoTitle">Añadir</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tipoOperacion">
                <input type="hidden" id="idRegistroEdicion" value="">
                
                <label class="form-label small fw-bold text-muted">Nombre:</label>
                <input type="text" id="txtNombreCatalogo" class="form-control focus-guinda text-uppercase" placeholder="Ej. LENOVO o THINKPAD" autocomplete="off" maxlength="25">
                <div id="msgErrorCatalogo" class="invalid-feedback fw-bold"></div>
            </div>
            <div class="modal-footer bg-light border-top-0 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarCatalogo" class="btn btn-sm text-white fw-bold px-3 bg-guinda" onclick="guardarCatalogo()" disabled>Guardar</button>
            </div>
        </div>
    </div>
</div>