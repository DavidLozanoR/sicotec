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
            
            <div class="row g-4">
                <?php if(empty($departamentos)): ?>
                    <div class="col-12 text-center py-5 text-muted border border-dashed rounded-3 bg-light">
                        <i class="fas fa-building-user fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">El catálogo de departamentos está vacío.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($departamentos as $dep): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100 shadow-sm border-0 ring-1 ring-secondary-subtle">
                            <div class="card-body p-4 position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-3 me-3 fs-4 text-guinda">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 220px;" title="<?php echo htmlspecialchars($dep['nom_dep']); ?>">
                                                <?php echo htmlspecialchars($dep['nom_dep']); ?>
                                            </h5>
                                            <?php if(!empty($dep['clave_dep'])): ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle mt-1">
                                                Clave: <?php echo htmlspecialchars($dep['clave_dep']); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-light text-muted border mt-1">Sin Clave</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border-0 text-muted" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item py-2 btn-editar-dep" href="#" 
                                                   data-id="<?php echo $dep['id_dep']; ?>" 
                                                   data-clave="<?php echo htmlspecialchars($dep['clave_dep'] ?? ''); ?>" 
                                                   data-nom="<?php echo htmlspecialchars($dep['nom_dep']); ?>">
                                                   <i class="fas fa-edit me-2 text-secondary"></i> Editar Datos
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="puestos" role="tabpanel">
            <div class="alert alert-secondary border-0 border-start border-4 border-secondary shadow-sm mb-4">
                <i class="fas fa-info-circle me-2"></i> <strong>Aviso:</strong> Este catálogo es de solo lectura. Los nuevos puestos se agregan automáticamente al sistema cuando se registran en el expediente de un trabajador en el módulo de Personal Operativo.
            </div>
            
            <div class="row g-3">
                <?php if(empty($puestos)): ?>
                    <div class="col-12 text-center py-5 text-muted border border-dashed rounded-3 bg-light">
                        <i class="fas fa-user-tie fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">Aún no hay puestos registrados en el personal operativo.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($puestos as $puesto): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                        <div class="card shadow-sm border-0 ring-1 ring-secondary-subtle">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="bg-light rounded p-2 me-3 text-secondary">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($puesto['puesto']); ?>">
                                        <?php echo htmlspecialchars($puesto['puesto']); ?>
                                    </h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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

