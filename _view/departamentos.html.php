<?php
$departamentos = $departamentos ?? [];
$puestos = $puestos ?? []; 
$id_sub_actual = $_SESSION['submodulo_actual'] ?? 3; 
?>

<link rel="stylesheet" href="css/departamentos.css">

<div class="container-fluid px-4 py-3">
    <input type="hidden" id="id_sub_dep" value="<?php echo $id_sub_actual; ?>">

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h3 class="fw-bold text-guinda mb-1"><i class="fas fa-sitemap me-2"></i>Estructura Organizacional</h3>
            <p class="text-secondary mb-0 small">Gestión de departamentos institucionales y catálogo de puestos.</p>
        </div>
    </div>

    <ul class="nav nav-pills mb-4 gap-2" id="catalogoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold px-4" id="deptos-tab" data-bs-toggle="pill" data-bs-target="#deptos" type="button" role="tab" aria-controls="deptos" aria-selected="true">
                <i class="fas fa-building me-2"></i>Departamentos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4" id="puestos-tab" data-bs-toggle="pill" data-bs-target="#puestos" type="button" role="tab" aria-controls="puestos" aria-selected="false">
                <i class="fas fa-briefcase me-2"></i>Puestos
            </button>
        </li>
    </ul>

    <div class="tab-content" id="catalogoTabsContent">
            
        <div class="tab-pane fade show active" id="deptos" role="tabpanel" aria-labelledby="deptos-tab">
            
            <div class="row mb-3 align-items-center g-3">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="fas fa-search"></i></span>
                        <input type="text" id="buscadorDeptos" class="form-control border-start-0 focus-guinda" placeholder="Buscar depto o clave..." autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-8 d-grid d-md-block text-md-end">
                    <button class="btn btn-guinda shadow-sm fw-bold px-4" id="btnNuevoDepto">
                        <i class="fas fa-plus-circle me-1"></i> Agregar Departamento
                    </button>
                </div>
            </div>

            <div class="table-responsive bg-white rounded-3 shadow-sm border mb-3">
                <table class="table table-hover align-middle mb-0" id="tablaDeptos">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-muted small text-uppercase">Clave</th>
                            <th class="py-3 text-muted small text-uppercase">Nombre del Departamento</th>
                            <th class="py-3 text-muted small text-uppercase">Personal Asignado</th>
                            <th class="text-end pe-4 py-3 text-muted small text-uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($departamentos)): ?>
                        <tr class="fila-vacia"><td colspan="4" class="text-center py-4 text-muted">No hay departamentos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach($departamentos as $dep): ?>
                            <tr class="fila-registro">
                                <td class="ps-4">
                                    <?php if(!empty($dep['clave_dep'])): ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle"><?php echo htmlspecialchars($dep['clave_dep']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-dark" data-lable="Nombre del Depto."><?php echo htmlspecialchars($dep['nom_dep']); ?></td>
                                <td>
                                    <?php if($dep['total_empleados'] > 0): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1"><i class="fas fa-users me-1"></i> <?php echo $dep['total_empleados']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-2 py-1">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light text-secondary border btn-editar-dep me-1" title="Editar" data-id="<?php echo $dep['id_dep']; ?>" data-clave="<?php echo htmlspecialchars($dep['clave_dep'] ?? ''); ?>" data-nom="<?php echo htmlspecialchars($dep['nom_dep']); ?>"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-light text-danger border btn-eliminar-dep" title="Eliminar" data-id="<?php echo $dep['id_dep']; ?>" data-nom="<?php echo htmlspecialchars($dep['nom_dep']); ?>" data-total="<?php echo $dep['total_empleados']; ?>"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="puestos" role="tabpanel" aria-labelledby="puestos-tab">
            
            <div class="alert alert-secondary border-0 border-start border-4 border-secondary shadow-sm mb-4">
                <i class="fas fa-info-circle me-2"></i> <strong>Catálogo de solo lectura:</strong> Los puestos se sincronizan automáticamente desde el sistema de personal.
            </div>
            <div class="row mb-3 align-items-center g-3">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="fas fa-search"></i></span>
                        <input type="text" id="buscadorPuestos" class="form-control border-start-0 focus-guinda" placeholder="Buscar puesto..." autocomplete="off">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive bg-white rounded-3 shadow-sm border mb-3">
                <table class="table table-hover align-middle mb-0" id="tablaPuestos">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-muted small text-uppercase">Nombre del Puesto</th>
                            <th class="text-end pe-4 py-3 text-muted small text-uppercase">Personal Activo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($puestos)): ?>
                        <tr class="fila-vacia"><td colspan="2" class="text-center py-4 text-muted">No hay puestos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach($puestos as $puesto): ?>
                            <tr class="fila-registro">
                                <td class="ps-4 fw-bold text-dark"><i class="fas fa-briefcase text-secondary me-2"></i> <?php echo htmlspecialchars($puesto['puesto']); ?></td>
                                <td class="text-end pe-4"><span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1"><i class="fas fa-user-tie me-1"></i> <?php echo $puesto['total_empleados']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 border-top pt-3">
        <span class="small text-muted fw-bold mb-2 mb-md-0" id="contador-registros">Cargando registros...</span>
        <nav aria-label="Navegación de tabla">
            <ul class="pagination pagination-sm mb-0 shadow-sm" id="paginacionTabla"></ul>
        </nav>
    </div>
</div>

<div class="modal fade" id="modalFormDepto" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="tituloModalDepto">Gestión de Departamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDepto" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_dep" id="dep_id">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Clave (Numérica)</label>
                            <input type="text" class="form-control focus-guinda" name="clave_dep" id="dep_clave" placeholder="Ej. 101" maxlength="20" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold small">Nombre del Depto. *</label>
                            <input type="text" class="form-control focus-guinda" name="nom_dep" id="dep_nom" placeholder="Ej. Recursos Humanos" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-guinda fw-bold px-4"><i class="fas fa-save me-1"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>