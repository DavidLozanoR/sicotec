<?php
$personal = $personal ?? [];
$id_sub_actual = $id_sub_actual ?? 4;
?>
<link rel="stylesheet" href="css/dispositivos.css">

<div class="container-fluid px-4 py-3">
    <input type="hidden" id="id_sub_responsiva" value="<?php echo $id_sub_actual; ?>">

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h3 class="fw-bold text-guinda mb-1"><i class="fas fa-exchange-alt me-2"></i>Gestión de Responsivas</h3>
            <p class="text-secondary mb-0 small">Trazabilidad y traspaso de activos entre el Personal Operativo.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-2">
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-users me-2 text-guinda"></i>Directorio de Personal</h6>
                    
                    <div class="mb-3">
                        <div class="input-group input-group-sm mb-2 shadow-sm">
                            <span class="input-group-text bg-light border-secondary-subtle"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="buscarPO" class="form-control border-secondary-subtle focus-guinda" placeholder="Buscar por nombre o No. Cobro...">
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input focus-guinda cursor-pointer" type="checkbox" id="filtroConEquipos" checked>
                            <label class="form-check-label small cursor-pointer" for="filtroConEquipos">Solo personal con equipos asignados</label>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div class="list-group list-group-flush rounded-bottom" id="lista-po" style="max-height: 60vh; overflow-y: auto;">
                        <?php foreach($personal as $po): ?>
                            <button type="button" 
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 po-item border-start border-5 border-transparent" 
                                data-id="<?php echo $po['id_po']; ?>" 
                                data-nombre="<?php echo htmlspecialchars($po['nombre_completo']); ?>" 
                                data-cobro="<?php echo htmlspecialchars($po['num_cobro']); ?>" 
                                data-equipos="<?php echo $po['total_equipos']; ?>">
                                
                                <div class="text-truncate me-2">
                                    <h6 class="mb-0 fw-bold po-nombre text-truncate"><?php echo htmlspecialchars($po['nombre_completo']); ?></h6>
                                    <small class="text-muted"><i class="fas fa-id-badge me-1"></i>No. Cobro: <?php echo htmlspecialchars($po['num_cobro'] ?: 'Sin Asignar'); ?></small>
                                </div>
                                
                                <span class="badge rounded-pill shadow-sm badge-equipos <?php echo $po['total_equipos'] > 0 ? 'bg-guinda text-white' : 'bg-light text-secondary border border-secondary-subtle'; ?>">
                                    <?php echo $po['total_equipos']; ?>
                                </span>
                            </button>
                        <?php endforeach; ?>
                        
                        <div class="p-4 text-center text-muted d-none" id="msg-no-results">
                            <i class="fas fa-search-minus fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0 small">No hay coincidencias con sus filtros.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3 h-100" style="min-height: 60vh;">
                <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-laptop me-2 text-guinda"></i>Equipos en Resguardo</h6>
                        <small class="text-muted d-block mt-1" id="lbl-po-activo">Seleccione un responsable de la lista</small>
                    </div>
                    <span class="badge bg-light text-dark border shadow-sm d-none" id="badge-contador-right">0 equipos</span>
                </div>
                
                <div class="card-body bg-light bg-opacity-50 p-4">
                    <div id="loader-equipos" class="text-center py-5 d-none">
                        <div class="spinner-border text-guinda mb-2" role="status"></div>
                        <p class="text-muted small">Cargando inventario...</p>
                    </div>

                    <div id="contenedor-equipos" class="row g-3">
                        <div class="col-12 text-center py-5 text-muted" id="estado-inicial">
                            <i class="fas fa-arrow-left fa-3x mb-3 opacity-25"></i>
                            <h5 class="fw-bold text-secondary">Ningún usuario seleccionado</h5>
                            <p class="mb-0 small">Navegue por el directorio izquierdo para gestionar responsivas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTransferir" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white border-bottom-0" style="background-color: #9D2449;">
                <h5 class="modal-title fw-bold"><i class="fas fa-exchange-alt me-2"></i>Transferir Responsiva</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTransferir">
                <div class="modal-body p-4 bg-light">
                    <input type="hidden" id="transf_id_disp" name="id_disp">
                    <input type="hidden" id="transf_po_origen" name="id_po_origen">
                    
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body text-center">
                            <h6 class="fw-bold text-dark mb-1" id="lbl-equipo-trasferir">Laptop</h6>
                            <span class="badge bg-secondary text-white font-monospace mb-2" id="lbl-serie-transferir">SN: </span>
                            <div class="text-muted small"><i class="fas fa-user-minus text-danger me-1"></i> Se retirará de: <strong id="lbl-origen-nombre"></strong></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark"><i class="fas fa-user-plus text-success me-1"></i> Nuevo Responsable (Destino): *</label>
                        <select class="form-select border-secondary-subtle focus-guinda shadow-sm" name="id_po_destino" id="select-po-destino" required>
                            <option value="" disabled selected>-- Elija al nuevo titular --</option>
                            <?php foreach($personal as $po): ?>
                                <option value="<?php echo $po['id_po']; ?>">
                                    <?php echo htmlspecialchars($po['nombre_completo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-muted small mt-2"><i class="fas fa-info-circle me-1"></i> La responsiva legal y asignación pasará inmediatamente al nuevo usuario en la base de datos.</div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold shadow-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-guinda px-4 fw-bold shadow-sm" id="btnConfirmarTransferencia">
                        <i class="fas fa-check-circle me-2"></i> Confirmar Traspaso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .cursor-pointer { cursor: pointer; }
    .border-transparent { border-color: transparent !important; }
    
    .po-item.active {
        background-color: #f8f9fa;
        border-color: #9D2449 !important;
        color: #333;
    }
    .po-item:hover:not(.active) { background-color: #fdfdfd; }
    .po-item.active .po-nombre { color: #9D2449; }

    /* =========================================
       NUEVOS ESTILOS SOLICITADOS
       ========================================= */

    /* 1. Asegurar que el color guinda se aplique al fondo y texto del badge */
    .bg-guinda {
        background-color: #9D2449 !important;
        color: #ffffff !important;
    }
    .text-guinda {
        color: #9D2449 !important;
    }

    /* 2. Atrapar las tarjetas creadas por JS y darles el estilo "General" */
    #contenedor-equipos .card {
        border-radius: 8px;
        border: 1px solid #eaeaea !important;
        border-left: 5px solid #9D2449 !important; /* Borde izquierdo distintivo */
        box-shadow: 0 2px 5px rgba(0,0,0,0.05) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    #contenedor-equipos .card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1) !important;
    }

    /* Darle un tono rojizo muy tenue al contenedor del icono dentro de la tarjeta */
    #contenedor-equipos .card .bg-light {
        background-color: rgba(157, 36, 73, 0.08) !important;
    }

    /* Botón de transferir con bordes y hover limpios */
    .btn-outline-guinda {
        color: #9D2449;
        border-color: #9D2449;
    }
    .btn-outline-guinda:hover {
        background-color: #9D2449;
        color: #ffffff;
    }
</style>