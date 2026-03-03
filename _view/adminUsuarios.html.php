<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0" style="color: #333;">Administración de Usuarios</h3>
            <p class="text-secondary mb-0">Gestión de cuentas y permisos del sistema (RBAC)</p>
        </div>
        <button class="btn btn-guinda px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario">
            <i class="fas fa-plus me-2"></i> Nuevo Usuario
        </button>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-4">
                <i class="fas fa-users text-guinda me-2"></i> Usuarios Activos del Sistema
            </h6>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Usuario (Nickname)</th>
                            <th>Nombre Completo</th>
                            <th>Rol Asignado</th>
                            <th>Permisos</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($usuarios && count($usuarios) > 0): ?>
                            
                            <?php foreach ($usuarios as $usr): ?>
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <?php echo htmlspecialchars($usr['nom_usuar']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($usr['nombres'] . ' ' . $usr['ape_pa'] . ' ' . $usr['ape_ma']); ?>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-secondary px-3 py-2">
                                            <?php echo htmlspecialchars($usr['nom_rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo isset($usr['permisos_lista']) ? htmlspecialchars($usr['permisos_lista']) : 'Sin permisos asignados'; ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-editar" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalUsuario"
                                                data-id="<?php echo $usr['id_usuar']; ?>" 
                                                data-nom_usuar="<?php echo htmlspecialchars($usr['nom_usuar']); ?>"
                                                data-nombres="<?php echo htmlspecialchars($usr['nombres']); ?>"
                                                data-ape_pa="<?php echo htmlspecialchars($usr['ape_pa']); ?>"
                                                data-ape_ma="<?php echo htmlspecialchars($usr['ape_ma']); ?>"
                                                data-id_rol="<?php echo $usr['id_rol']; ?>"
                                                data-permisos='<?php echo isset($usr['permisos_ids']) ? json_encode($usr['permisos_ids']) : "[]"; ?>'
                                                title="Editar Usuario">
                                            <i class="fas fa-edit" style="pointer-events: none;"></i>
                                        </button>
                                        
                                        <button type="button" id="data-id" class="btn btn-sm btn-outline-danger btn-baja" data-id="<?php echo $usr['id_usuar']; ?>" title="Dar de baja (estatus=0)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted"> 
                                    <i class="fas fa-shield-alt fa-3x mb-3" style="color: #e0e0e0;"></i><br>
                                    No se encontraron usuarios activos en el sistema.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            
            <div class="modal-header border-0 pb-0 mt-2 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalUsuarioLabel">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body pt-1 px-4">
                <p class="text-muted small mb-4">Complete la información del usuario en el sistema</p>
                
                <form id="formUsuario">
                    <input type="hidden" id="peticion_input" name="peticion" value="INSERT">
                    <input type="hidden" id="id_usuar_input" name="id_usuar" value="">

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark mb-1">Usuario*</label>
                        <input type="text" class="form-control focus-guinda border-secondary-subtle" id="nom_usuar_input" name="nom_usuar" placeholder="Ej: jperez" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark mb-1">Nombre(s) *</label>
                        <input type="text" class="form-control focus-guinda border-secondary-subtle" id="nombres_input" name="nombres" placeholder="Ej: Juan" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">Apellido Paterno *</label>
                            <input type="text" class="form-control focus-guinda border-secondary-subtle" id="ape_pa_input" name="ape_pa" placeholder="Ej: Pérez" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">Apellido Materno *</label>
                            <input type="text" class="form-control focus-guinda border-secondary-subtle" id="ape_ma_input" name="ape_ma" placeholder="Ej: López" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark mb-1">Contraseña</label>
                        <input type="password" class="form-control focus-guinda border-secondary-subtle" id="password_input" name="password" placeholder="Ej: 123456">
                        <div class="form-text small text-muted mt-1">Déjalo en blanco si no deseas cambiar la contraseña al editar.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-dark mb-1">Rol *</label>
                        <select class="form-select focus-guinda border-secondary-subtle" id="id_rol_input" name="id_rol" required>
                            <option value="" selected disabled>Seleccione un rol...</option>
                            <?php if(isset($roles_disponibles)): ?>
                                <?php foreach($roles_disponibles as $rol): ?>
                                    <option value="<?php echo $rol['id_rol']; ?>"><?php echo htmlspecialchars($rol['nom_rol']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold small text-dark mb-3">Permisos del Sistema</label>
                        
                        <div class="permisos-container">
                            <?php 
                            $permisos_del_usuario = isset($permisos_usuario_actual) ? $permisos_usuario_actual : []; 
                            
                            if(isset($permisos_disponibles) && count($permisos_disponibles) > 0): 
                                foreach($permisos_disponibles as $permiso): 
                                    $tiene_permiso = in_array($permiso['id_perm'], $permisos_del_usuario) ? 'checked' : '';
                            ?>
                                <div class="form-check custom-checkbox-guinda mb-3 d-flex align-items-center">
                                    <input class="form-check-input me-2 mt-0 check-permiso" type="checkbox" name="permisos[]" value="<?php echo $permiso['id_perm']; ?>" id="perm_<?php echo $permiso['id_perm']; ?>" style="transform: scale(1.2);" <?php echo $tiene_permiso; ?>>
                                    <label class="form-check-label text-dark pt-1" for="perm_<?php echo $permiso['id_perm']; ?>">
                                        <?php echo htmlspecialchars($permiso['nom_perm']); ?>
                                    </label>
                                </div>
                            <?php 
                                endforeach; 
                            else:
                            ?>
                                <p class="text-muted small">No hay permisos disponibles en la base de datos.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 mb-2">
                        <button type="button" class="btn btn-outline-secondary me-3 px-4 fw-bold" data-bs-dismiss="modal" style="border-radius: 8px;">Cancelar</button>
                        <button type="submit" class="btn btn-guinda px-4 fw-bold" style="border-radius: 8px;" id="btnGuardarUsuario">Guardar Usuario</button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</div>