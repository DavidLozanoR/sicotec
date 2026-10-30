<?php
$catalogos_requeridos = [
    ['datos' => $roles_disponibles ?? [], 'nombre' => 'Roles de Acceso', 'url' => 'roles/']
];

$faltantes = [];
foreach ($catalogos_requeridos as $catalogo) {
    if (empty($catalogo['datos'])) {
        $faltantes[] = [
            'nombre' => $catalogo['nombre'],
            'url'    => $catalogo['url']
        ];
    }
}
$bloquear_formulario = !empty($faltantes);
?>
<div class="container-fluid px-4 py-3" id="main-admin-container" data-id-sub="<?php echo $id_sub_actual ?? 7; ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0" style="color: #333;">Administración de Usuarios</h3>
            <p class="text-secondary mb-0">Gestión de cuentas de acceso al sistema</p>
        </div>
        <button class="btn btn-guinda px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario" <?php echo $bloquear_formulario ? 'disabled' : ''; ?>>
            <i class="fas fa-plus me-2"></i> Nuevo Usuario
        </button>
    </div>

    <?php if ($bloquear_formulario): ?>
        <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger mb-4">
            <h5 class="fw-bold text-danger"><i class="fas fa-exclamation-triangle me-2"></i>¡Atención! Faltan Catálogos Obligatorios</h5>
            <p class="mb-3 text-dark">Para poder registrar o editar un usuario, el sistema requiere que primero exista información en los siguientes catálogos:</p>
            
            <ul class="list-unstyled mb-0 ms-2">
                <?php foreach ($faltantes as $faltante): ?>
                    <li class="mb-2 d-flex align-items-center bg-white p-2 rounded-3 shadow-sm border border-danger-subtle" style="max-width: 500px;">
                        <i class="fas fa-times-circle text-danger mx-2 fs-5"></i>
                        <span class="fw-bold text-danger me-auto"><?php echo $faltante['nombre']; ?></span>
                        <a href="<?php echo $faltante['url']; ?>" class="btn btn-sm btn-danger fw-bold rounded-pill px-3 shadow-sm" target="_blank">
                            Ir al catálogo <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-4">
                <i class="fas fa-users text-guinda me-2"></i> Usuarios Activos del Sistema
            </h6>

            <div class="table-responsive" id="contenedor-tabla-usuarios" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Usuario (Nickname)</th>
                            <th>Nombre Completo</th>
                            <th>Rol Asignado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($usuarios && count($usuarios) > 0): ?>
                            <?php foreach ($usuarios as $usr): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($usr['nom_usuar']); ?></td>
                                    <td><?php echo htmlspecialchars($usr['nombres'] . ' ' . $usr['ape_pa'] . ' ' . $usr['ape_ma']); ?></td>
                                    <td><span class="badge rounded-pill bg-secondary px-3 py-2"><?php echo htmlspecialchars($usr['nom_rol']); ?></span></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-editar"
                                                data-bs-toggle="modal" data-bs-target="#modalUsuario"
                                                data-id="<?php echo $usr['id_usuar']; ?>"
                                                data-nom_usuar="<?php echo htmlspecialchars($usr['nom_usuar']); ?>"
                                                data-nombres="<?php echo htmlspecialchars($usr['nombres']); ?>"
                                                data-ape_pa="<?php echo htmlspecialchars($usr['ape_pa']); ?>"
                                                data-ape_ma="<?php echo htmlspecialchars($usr['ape_ma']); ?>"
                                                data-id_rol="<?php echo $usr['id_rol']; ?>" title="Editar Usuario" <?php echo $bloquear_formulario ? 'disabled' : ''; ?>>
                                            <i class="fas fa-edit" style="pointer-events: none;"></i>
                                        </button>
                                        
                                        <?php if ($usr['id_rol'] != 1): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-baja" data-id="<?php echo $usr['id_usuar']; ?>" title="Dar de baja">
                                                <i class="fas fa-trash-alt" style="pointer-events: none;"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-users fa-3x mb-3" style="color: #e0e0e0;"></i><br>
                                    No se encontraron usuarios activos.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header border-0 pb-0 mt-2 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalUsuarioLabel">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1 px-4">
                <p class="text-muted small mb-4">Complete la información del usuario en el sistema</p>
                <form id="formUsuario" novalidate>
                    <input type="hidden" id="peticion_input" name="peticion" value="INSERT">
                    <input type="hidden" name="id_sub" class="dynamic-id-sub" value="">
                    <input type="hidden" id="id_usuar_input" name="id_usuar" value="">

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark mb-1">Usuario (Nickname) *</label>
                        <input type="text" class="form-control focus-guinda border-secondary-subtle" id="nom_usuar_input" name="nom_usuar" maxlength="50" required>
                        <div class="invalid-feedback fw-bold small">El nombre de usuario es obligatorio.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark mb-1">Nombre(s) *</label>
                        <input type="text" class="form-control focus-guinda border-secondary-subtle" id="nombres_input" name="nombres" maxlength="100" required>
                        <div class="invalid-feedback fw-bold small">El nombre es obligatorio.</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">Apellido Paterno *</label>
                            <input type="text" class="form-control focus-guinda border-secondary-subtle" id="ape_pa_input" name="ape_pa" maxlength="100" required>
                            <div class="invalid-feedback fw-bold small">El apellido paterno es obligatorio.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark mb-1">Apellido Materno</label>
                            <input type="text" class="form-control focus-guinda border-secondary-subtle" id="ape_ma_input" name="ape_ma" maxlength="100">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark mb-1">Contraseña</label>
                        <input type="password" class="form-control focus-guinda border-secondary-subtle" id="password_input" name="password" maxlength="8">
                        <div class="invalid-feedback fw-bold small">Debe asignar una contraseña para un nuevo usuario.</div>
                        <div class="form-text small text-muted mt-1">Déjalo en blanco si no deseas cambiar la contraseña al editar.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-dark mb-1">Rol de Acceso *</label>
                        <select class="form-select focus-guinda border-secondary-subtle" id="id_rol_input" name="id_rol" required>
                            <option value="" selected disabled>Seleccione un rol...</option>
                            <option value="1" id="opt-admin" style="display:none;" disabled>Administrador (Protegido)</option>
                            <?php if(isset($roles_disponibles)): ?>
                                <?php foreach($roles_disponibles as $rol): ?>
                                    <option value="<?php echo $rol['id_rol']; ?>"><?php echo htmlspecialchars($rol['nom_rol']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback fw-bold small">Debe seleccionar un rol de la lista.</div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 mb-2">
                        <button type="button" class="btn btn-outline-secondary me-3 px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-guinda px-4 fw-bold" id="btnGuardarUsuario">Guardar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>