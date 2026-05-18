<div class="container-fluid px-4 py-3" id="main-roles-container" data-id-sub="<?php echo $id_sub_actual ?? 9; ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0" style="color: #333;">Administración de Roles</h3>
            <p class="text-secondary mb-0">Gestión de roles y sus permisos por submódulo (RBAC)</p>
        </div>
        <button class="btn btn-guinda px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRol">
            <i class="fas fa-plus me-2"></i> Nuevo Rol
        </button>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-4">
                <i class="fas fa-shield-alt text-guinda me-2"></i> Roles del Sistema
            </h6>

            <div class="table-responsive" id="contenedor-tabla-roles" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Nombre del Rol</th>
                            <th>Nivel de Acceso</th>
                            <th class="text-center">Usuarios Activos</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($roles) && count($roles) > 0): ?>
                            <?php foreach ($roles as $rol): ?>
                                <?php $protegido = ($rol['id_rol'] == 1); ?>
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <?php echo htmlspecialchars($rol['nom_rol']); ?>
                                        
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-key me-1"></i> <?php echo $rol['total_permisos']; ?> permisos en <?php echo $rol['total_submodulos']; ?> submódulos
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill <?php echo $rol['total_usuarios'] > 0 ? 'bg-success' : 'bg-secondary'; ?> px-3 py-2">
                                            <?php echo $rol['total_usuarios']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!$protegido): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-editar-rol"
                                                    data-bs-toggle="modal" data-bs-target="#modalRol"
                                                    data-id="<?php echo $rol['id_rol']; ?>"
                                                    data-nom_rol="<?php echo htmlspecialchars($rol['nom_rol']); ?>"
                                                    data-permisos='<?php echo json_encode($rol['permisos_json']); ?>'
                                                    title="Editar Rol">
                                                <i class="fas fa-edit" style="pointer-events: none;"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-rol"
                                                    data-id="<?php echo $rol['id_rol']; ?>"
                                                    data-nom="<?php echo htmlspecialchars($rol['nom_rol']); ?>"
                                                    data-usuarios="<?php echo $rol['total_usuarios']; ?>"
                                                    title="Eliminar Rol">
                                                <i class="fas fa-trash-alt" style="pointer-events: none;"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">Sin acciones</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-shield-alt fa-3x mb-3" style="color: #e0e0e0;"></i><br>
                                    No se encontraron roles en el sistema.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRol" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header border-0 pb-0 mt-2 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalRolLabel">Configuración de Rol</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-1 px-4">
                <form id="formRol" novalidate>
                    <input type="hidden" id="peticion_rol_input" name="peticion" value="INSERT_ROL">
                    <input type="hidden" name="id_sub" class="dynamic-id-sub" value="">
                    <input type="hidden" id="id_rol_hidden" name="id_rol" value="">

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-dark mb-1">Nombre del Rol *</label>
                        <input type="text" class="form-control focus-guinda border-secondary-subtle" id="nom_rol_input" name="nom_rol" maxlength="100" required>
                        <div class="invalid-feedback fw-bold small">El nombre del rol es obligatorio.</div>
                    </div>

                    <label class="form-label fw-bold small text-dark mb-3">Permisos de Acceso por Submódulo</label>
                    <div class="permisos-container p-3 bg-light rounded border border-secondary-subtle" style="max-height: 400px; overflow-y: auto;">
                        <?php if(isset($arbol_modulos) && count($arbol_modulos) > 0): ?>
                            <?php foreach($arbol_modulos as $mod): ?>
                                <div class="mb-4">
                                    <h6 class="fw-bold text-guinda border-bottom border-secondary-subtle pb-2 mb-3">
                                        <i class="<?php echo htmlspecialchars($mod['icono']); ?> me-2"></i><?php echo htmlspecialchars($mod['nom_mod']); ?>
                                    </h6>
                                    
                                    <?php if(isset($mod['submodulos']) && count($mod['submodulos']) > 0): ?>
                                        <div class="row">
                                            <?php foreach($mod['submodulos'] as $sub): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="card border-0 shadow-sm h-100">
                                                        <div class="card-body p-3">
                                                            <strong class="small text-dark d-block mb-2"><?php echo htmlspecialchars($sub['nom_sub']); ?></strong>
                                                            <div class="d-flex flex-wrap gap-3">
                                                                <?php foreach($permisos_catalogo as $perm): ?>
                                                                    <div class="form-check custom-checkbox-guinda">
                                                                        <input class="form-check-input check-permiso-rol" 
                                                                               type="checkbox" 
                                                                               name="permisos[<?php echo $sub['id_sub']; ?>][]" 
                                                                               value="<?php echo $perm['id_perm']; ?>" 
                                                                               id="sub<?php echo $sub['id_sub']; ?>_perm<?php echo $perm['id_perm']; ?>">
                                                                        <label class="form-check-label small" for="sub<?php echo $sub['id_sub']; ?>_perm<?php echo $perm['id_perm']; ?>">
                                                                            <?php echo htmlspecialchars($perm['nom_perm']); ?>
                                                                        </label>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted small ms-3">Sin submódulos registrados.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end mt-4 mb-2">
                        <button type="button" class="btn btn-outline-secondary me-3 px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-guinda px-4 fw-bold" id="btnGuardarRol">Guardar Rol</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>