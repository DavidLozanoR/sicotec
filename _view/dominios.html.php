<link rel="stylesheet" href="css/master.css">
<style>
    .bg-guinda { background-color: #9D2449 !important; }
    .text-guinda { color: #9D2449 !important; }
    .border-guinda { border-color: #9D2449 !important; }
    .focus-guinda:focus { border-color: #9D2449; box-shadow: 0 0 0 0.25rem rgba(157, 36, 73, 0.25); }
    .cursor-pointer { cursor: pointer; }
</style>

<?php
// Variables inyectadas desde el controlador
$id_sub_actual = $id_sub_actual ?? 1;
$puede_editar = $puede_editar ?? false;
$puede_eliminar = $puede_eliminar ?? false;
$dominios = $dominios ?? [];
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <div>
            <h3 class="fw-bold text-guinda mb-0"><i class="fas fa-globe me-2"></i>Catálogo de Dominios</h3>
            <p class="text-muted small mb-0">Gestión de dominios institucionales de red (Ej. ineadom.gob.mx)</p>
        </div>
        <input type="hidden" id="id_sub_actual" value="<?php echo htmlspecialchars($id_sub_actual); ?>">
        
        <?php if ($puede_editar): ?>
        <button class="btn bg-guinda text-white fw-bold shadow-sm" onclick="abrirModalDominio()">
            <i class="fas fa-plus-circle me-2"></i>Nuevo Dominio
        </button>
        <?php endif; ?>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-list me-2"></i>Dominios Registrados</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group input-group-sm shadow-sm">
                                <span class="input-group-text bg-white border-end-0 text-guinda"><i class="fas fa-search"></i></span>
                                <input type="text" id="inputBuscarDominio" class="form-control border-start-0 focus-guinda" placeholder="Buscar dominio..." autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" id="tablaDominios">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Nombre del Dominio</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyDominios">
                                <?php if (empty($dominios)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-5">
                                            <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                            No hay dominios registrados o activos.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dominios as $dom): ?>
                                    <tr class="fila-dominio">
                                        <td class="ps-4 fw-bold text-secondary">#<?php echo htmlspecialchars($dom['id_dom']); ?></td>
                                        <td class="fw-bold text-dark nombre-dominio-txt"><?php echo htmlspecialchars($dom['nom_dom']); ?></td>
                                        <td class="text-end pe-4">
                                            <?php if ($puede_editar): ?>
                                                <button class="btn btn-sm btn-outline-secondary border-0 me-1" onclick="abrirModalDominio(<?php echo $dom['id_dom']; ?>, '<?php echo htmlspecialchars(addslashes($dom['nom_dom'])); ?>')" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($puede_eliminar): ?>
                                                <button class="btn btn-sm btn-outline-danger border-0" onclick="eliminarDominio(<?php echo $dom['id_dom']; ?>)" title="Eliminar">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light text-muted small fw-bold py-2">
                    <span id="lblTotalDominios">Total: <?php echo count($dominios); ?> registros</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDominio" tabindex="-1" aria-labelledby="modalDominioTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: #9D2449;">
                <h5 class="modal-title fw-bold" id="modalDominioTitle"><i class="fas fa-globe me-2"></i>Nuevo Dominio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="idRegistroEdicion" value="">
                
                <div class="mb-3">
                    <label for="txtNombreDominio" class="form-label fw-bold text-dark small">Nombre del Dominio <span class="text-danger">*</span></label>
                    <input type="text" class="form-control focus-guinda" id="txtNombreDominio" placeholder="Ej. ineadom.gob.mx" autocomplete="off" maxlength="100">
                    <small id="msgErrorDominio" class="text-danger fw-bold d-block mt-1"></small>
                </div>
                
                <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-0 mt-3 p-2">
                    <i class="fas fa-info-circle fa-lg me-2"></i>
                    <small style="font-size: 0.75rem;">Ingrese el nombre de dominio exactamente como es detectado por la red. No utilice "http" ni "www".</small>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn bg-guinda text-white px-4 fw-bold shadow-sm" id="btnGuardarDominio" onclick="guardarDominio()" disabled>
                    <i class="fas fa-save me-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>