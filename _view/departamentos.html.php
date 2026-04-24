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
            <button class="nav-link active fw-bold px-4" id="deptos-tab" data-bs-toggle="pill" data-bs-target="#deptos" type="button" role="tab">
                <i class="fas fa-building me-2"></i>Departamentos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4" id="puestos-tab" data-bs-toggle="pill" data-bs-target="#puestos" type="button" role="tab">
                <i class="fas fa-briefcase me-2"></i>Puestos
            </button>
        </li>
    </ul>

    <div class="tab-content" id="catalogoTabsContent">
        
        <div class="tab-pane fade show active" id="deptos" role="tabpanel">
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-guinda shadow-sm fw-bold" id="btnNuevoDepto">
                    <i class="fas fa-plus-circle me-1"></i> Agregar Departamento
                </button>
            </div>
            
            <div class="table-responsive bg-white rounded-3 shadow-sm border ring-1 ring-secondary-subtle">
                <table class="table table-hover align-middle mb-0">
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
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-building-user fa-3x mb-3 opacity-25 d-block"></i>
                                El catálogo de departamentos está vacío.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($departamentos as $dep): ?>
                            <tr>
                                <td class="ps-4">
                                    <?php if(!empty($dep['clave_dep'])): ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle">
                                            <?php echo htmlspecialchars($dep['clave_dep']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-dark">
                                    <?php echo htmlspecialchars($dep['nom_dep']); ?>
                                </td>
                                <td>
                                    <?php if($dep['total_empleados'] > 0): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1">
                                            <i class="fas fa-users me-1"></i> <?php echo $dep['total_empleados']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-2 py-1">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light text-secondary border btn-editar-dep me-1" 
                                            title="Editar"
                                            data-id="<?php echo $dep['id_dep']; ?>" 
                                            data-clave="<?php echo htmlspecialchars($dep['clave_dep'] ?? ''); ?>" 
                                            data-nom="<?php echo htmlspecialchars($dep['nom_dep']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light text-danger border btn-eliminar-dep" 
                                            title="Eliminar"
                                            data-id="<?php echo $dep['id_dep']; ?>" 
                                            data-nom="<?php echo htmlspecialchars($dep['nom_dep']); ?>"
                                            data-total="<?php echo $dep['total_empleados']; ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="puestos" role="tabpanel">
            <div class="alert alert-secondary border-0 border-start border-4 border-secondary shadow-sm mb-3">
                <i class="fas fa-info-circle me-2"></i> <strong>Aviso:</strong> Catálogo de solo lectura. Los puestos se agregan automáticamente al registrar a un trabajador.
            </div>
            
            <div class="table-responsive bg-white rounded-3 shadow-sm border ring-1 ring-secondary-subtle">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-muted small text-uppercase">Nombre del Puesto</th>
                            <th class="py-3 text-muted small text-uppercase">Estado</th>
                            <th class="text-end pe-4 py-3 text-muted small text-uppercase">Personal Activo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($puestos)): ?>
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                <i class="fas fa-user-tie fa-3x mb-3 opacity-25 d-block"></i>
                                Aún no hay puestos registrados en el personal.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($puestos as $puesto): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark">
                                    <i class="fas fa-briefcase text-secondary me-2"></i> <?php echo htmlspecialchars($puesto['puesto']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">Activo</span>
                                </td>
                                <td class="text-end pe-4">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1">
                                        <i class="fas fa-user-tie me-1"></i> <?php echo $puesto['total_empleados']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalFormDepto" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="tituloModalDepto"><i class="fas fa-building me-2 text-guinda"></i>Gestión de Departamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDepto" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_dep" id="dep_id">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Clave (Opcional)</label>
                            <input type="text" class="form-control focus-guinda" name="clave_dep" id="dep_clave" placeholder="Ej. 101" maxlength="20">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold small">Nombre del Depto. *</label>
                            <input type="text" class="form-control focus-guinda" name="nom_dep" id="dep_nom" placeholder="Ej. Recursos Humanos" required maxlength="255">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-guinda fw-bold"><i class="fas fa-save me-1"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
