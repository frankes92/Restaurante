<?php /* Modal reutilizable para enviar WhatsApp. Incluir en vistas que necesiten envío. */ ?>

<!-- MODAL POST-COBRO: Imprimir o WhatsApp -->
<div class="modal-overlay" id="modal-post-cobro">
    <div class="modal" style="max-width: 520px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-circle-check" style="color:var(--green);"></i> ¡Cobro confirmado!</div>
            <button class="modal-close" onclick="closeModal('modal-post-cobro')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div id="post-cobro-info" style="background:var(--bg-light);padding:14px;border-radius:10px;margin-bottom:18px;text-align:center;">
                <div style="font-size:11px;color:var(--text-muted);font-weight:700;letter-spacing:.5px;">COMPROBANTE</div>
                <div id="pc-numero" style="font-size:20px;font-weight:800;color:var(--primary);margin-top:4px;">—</div>
                <div id="pc-total"  style="font-size:14px;color:var(--text-dark);margin-top:4px;">—</div>
            </div>

            <div style="text-align:center;font-size:14px;font-weight:600;color:var(--text-dark);margin-bottom:14px;">
                ¿Cómo entregar el comprobante al cliente?
            </div>

            <div style="display:grid;grid-template-columns:1fr;gap:14px;">
                <button class="post-cobro-btn" onclick="postCobroImprimir()" style="background:var(--primary);color:#fff;">
                    <i class="fa-solid fa-print" style="font-size:32px;"></i>
                    <div style="font-size:13px;font-weight:700;margin-top:8px;">IMPRIMIR</div>
                    <div style="font-size:10px;opacity:.85;margin-top:2px;">Ticket / A4</div>
                </button>
                <!-- INICIO WHATSAPP DESHABILITADO (para reactivar: descomenta el button y cambia grid-template-columns:1fr 1fr arriba)
                <button class="post-cobro-btn" data-perm="whatsapp_enviar" onclick="postCobroWhatsapp()" style="background:#25d366;color:#fff;">
                    <i class="fa-brands fa-whatsapp" style="font-size:32px;"></i>
                    <div style="font-size:13px;font-weight:700;margin-top:8px;">WHATSAPP</div>
                    <div style="font-size:10px;opacity:.85;margin-top:2px;">Enviar al celular</div>
                </button>
                FIN WHATSAPP DESHABILITADO -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-post-cobro')">Cerrar sin acción</button>
        </div>
    </div>
</div>

<!-- MODAL DE ENVIO WHATSAPP -->
<div class="modal-overlay" id="modal-whatsapp">
    <div class="modal" style="max-width: 580px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-brands fa-whatsapp" style="color:#25d366;"></i> Enviar comprobante por WhatsApp</div>
            <button class="modal-close" onclick="closeModal('modal-whatsapp')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <!-- Cliente -->
            <div style="background:#f0fdf4;border:1px dashed #25d366;padding:10px 12px;border-radius:8px;margin-bottom:12px;font-size:12px;">
                <div id="wa-cliente-info" style="line-height:1.5;">—</div>
            </div>

            <!-- Numero -->
            <div class="input-group">
                <label class="input-label">Número de WhatsApp <small style="color:var(--text-muted);">(con o sin +51)</small></label>
                <div style="display:flex;gap:8px;">
                    <input type="text" id="wa-numero" class="input-field" placeholder="987 654 321" style="flex:1;">
                    <button type="button" class="btn btn-sm" onclick="waBuscarNumero()" title="Buscar número guardado">
                        <i class="fa-solid fa-search"></i>
                    </button>
                </div>
                <label style="display:flex;align-items:center;gap:6px;font-size:12px;margin-top:8px;cursor:pointer;">
                    <input type="checkbox" id="wa-guardar-numero" checked>
                    Guardar este número en el cliente para futuros envíos
                </label>
            </div>

            <!-- Plantilla -->
            <div class="input-group">
                <label class="input-label">Plantilla</label>
                <select id="wa-plantilla" class="input-field" onchange="waCargarPlantilla()"></select>
            </div>

            <!-- Mensaje -->
            <div class="input-group">
                <label class="input-label">
                    Mensaje
                    <small style="color:var(--text-muted);font-weight:400;">(editable)</small>
                </label>
                <textarea id="wa-mensaje" class="input-field" rows="9" style="font-family:'Segoe UI Emoji','Apple Color Emoji','Noto Color Emoji',-apple-system,sans-serif;font-size:13px;resize:vertical;"></textarea>
                <div style="margin-top:6px;display:flex;gap:6px;align-items:center;">
                    <button type="button" class="btn btn-sm" onclick="toggleEmojis('wa-mensaje','wa-emoji-picker')" style="background:#fef3c7;border-color:#f59e0b;color:#92400e;">
                        <i class="fa-regular fa-face-smile"></i> Insertar emoji
                    </button>
                    <span style="font-size:10px;color:var(--text-muted);">
                        Los emojis se preservan tal cual al enviar.
                    </span>
                </div>
                <div id="wa-emoji-picker" class="emoji-picker" style="display:none;"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-whatsapp')">Cancelar</button>
            <button class="btn" style="background:#25d366;color:#fff;" onclick="waEnviar()">
                <i class="fa-brands fa-whatsapp"></i> Abrir WhatsApp
            </button>
        </div>
    </div>
</div>

<style>
.post-cobro-btn {
    border: 0;
    border-radius: 14px;
    padding: 24px 16px;
    cursor: pointer;
    font-family: inherit;
    transition: transform .15s, box-shadow .15s;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}
.post-cobro-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 18px rgba(0,0,0,0.18); }
.post-cobro-btn:active { transform: scale(0.98); }
</style>
