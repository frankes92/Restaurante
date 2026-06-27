<?php /* Modal de cobro reutilizable. Incluir en vistas que necesitan cobrar. */ ?>
<div class="modal-overlay" id="modal-cobro">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-cash-register" style="color:var(--primary);"></i> Cobrar Orden</div>
            <button class="modal-close" onclick="closeModal('modal-cobro')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="background: var(--bg-light); border-radius: 10px; padding: 14px; margin-bottom: 16px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size: 11px; color: var(--text-muted); font-weight: 600;">TOTAL A COBRAR</div>
                    <div style="font-size: 28px; font-weight: 700; color: var(--primary);" id="cobro-total">S/ 0.00</div>
                </div>
                <div style="text-align:right;font-size:12px;color:var(--text-muted);">
                    <div>Orden <span id="cobro-numero" style="color:var(--text-dark);font-weight:600;">—</span></div>
                    <div id="cobro-mesa">—</div>
                </div>
            </div>

            <div class="input-group">
                <label class="input-label">Tipo de comprobante</label>
                <div style="display:grid;grid-template-columns: 1fr 1fr 1fr;gap:8px;" id="tipo-comp-grid">
                    <button type="button" class="pay-btn active" data-tipo="nota_venta"><i class="fa-regular fa-file-lines"></i><br>Nota Venta</button>
                    <button type="button" class="pay-btn"        data-tipo="boleta" data-perm="emitir_boleta"><i class="fa-regular fa-file"></i><br>Boleta<br><small style="font-size:9px;color:var(--green);">SUNAT</small></button>
                    <button type="button" class="pay-btn"        data-tipo="factura" data-perm="emitir_factura"><i class="fa-solid fa-file-invoice"></i><br>Factura<br><small style="font-size:9px;color:var(--green);">SUNAT</small></button>
                </div>
            </div>

            <!-- Datos del cliente para boleta/factura -->
            <div id="cliente-datos" style="display:none;background:#f5f3ff;border:1px dashed var(--primary);border-radius:10px;padding:12px;margin-top:12px;">
                <div style="font-size:11px;font-weight:700;color:var(--primary);margin-bottom:8px;">DATOS DEL RECEPTOR</div>
                <div style="display:grid;grid-template-columns:120px 1fr auto;gap:8px;align-items:end;">
                    <div class="input-group" style="margin:0;">
                        <label class="input-label">Tipo Doc</label>
                        <select id="cli-tipo-doc" class="input-field"></select>
                    </div>
                    <div class="input-group" style="margin:0;">
                        <label class="input-label">Nº Documento *</label>
                        <input id="cli-num-doc" class="input-field" maxlength="15">
                    </div>
                    <button class="btn" type="button" onclick="buscarCliente()" title="Buscar"><i class="fa-solid fa-search"></i></button>
                </div>
                <div class="input-group" style="margin-top:8px;">
                    <label class="input-label">Razón Social / Nombre *</label>
                    <input id="cli-razon" class="input-field">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
                    <div class="input-group" style="margin:0;">
                        <label class="input-label">Dirección</label>
                        <input id="cli-direccion" class="input-field">
                    </div>
                    <div class="input-group" style="margin:0;">
                        <label class="input-label">Email</label>
                        <input id="cli-email" type="email" class="input-field">
                    </div>
                </div>
            </div>

            <div class="input-group" style="margin-top:14px;">
                <label class="input-label">Método de pago</label>
                <div style="display:grid;grid-template-columns: repeat(5, 1fr);gap:8px;" id="metodo-pago-grid">
                    <button type="button" class="pay-btn active" data-metodo="efectivo"><i class="fa-solid fa-money-bill" style="color:var(--green);"></i><br>Efectivo</button>
                    <button type="button" class="pay-btn"        data-metodo="tarjeta"><i class="fa-regular fa-credit-card" style="color:var(--blue);"></i><br>Tarjeta</button>
                    <button type="button" class="pay-btn"        data-metodo="yape"><i class="fa-solid fa-mobile-screen" style="color:#742384;"></i><br>Yape</button>
                    <button type="button" class="pay-btn"        data-metodo="plin"><i class="fa-solid fa-mobile-screen" style="color:#00bcd4;"></i><br>Plin</button>
                    <button type="button" class="pay-btn"        data-metodo="transferencia"><i class="fa-solid fa-building-columns" style="color:var(--orange);"></i><br>Transfer.</button>
                </div>
            </div>

            <div id="campo-efectivo" class="metodo-fields" style="margin-top:14px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="input-group">
                        <label class="input-label">Monto recibido (S/)</label>
                        <input type="number" id="cobro-recibido" class="input-field" step="0.10" placeholder="0.00">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Vuelto</label>
                        <div style="background: var(--bg-light); border-radius: 10px; padding: 14px; font-size: 18px; font-weight: 700; color: var(--green);" id="cobro-vuelto">S/ 0.00</div>
                    </div>
                </div>
                <div style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap;">
                    <button type="button" class="btn btn-sm" onclick="setMontoRecibido('exacto')">Exacto</button>
                    <button type="button" class="btn btn-sm" onclick="setMontoRecibido(20)">S/ 20</button>
                    <button type="button" class="btn btn-sm" onclick="setMontoRecibido(50)">S/ 50</button>
                    <button type="button" class="btn btn-sm" onclick="setMontoRecibido(100)">S/ 100</button>
                    <button type="button" class="btn btn-sm" onclick="setMontoRecibido(200)">S/ 200</button>
                </div>

                <!-- Pago combinado: parte en Yape/Plin -->
                <div style="margin-top:12px;border-top:1px dashed var(--border);padding-top:10px;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;font-weight:600;">
                        <input type="checkbox" id="cobro-combinado-chk" onchange="toggleCombinado()">
                        <i class="fa-solid fa-mobile-screen" style="color:var(--primary);"></i>
                        Pagar una parte con Yape/Plin
                    </label>
                    <div id="cobro-combinado-box" style="display:none;margin-top:10px;background:#f5f3ff;border:1px dashed var(--primary);border-radius:10px;padding:12px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:end;">
                            <div class="input-group" style="margin:0;">
                                <label class="input-label">Monto digital (S/)</label>
                                <input type="number" id="cobro-comb-monto" class="input-field" step="0.10" placeholder="0.00" oninput="actualizarCombinado()">
                            </div>
                            <div class="input-group" style="margin:0;">
                                <label class="input-label">¿Yape o Plin?</label>
                                <select id="cobro-comb-metodo" class="input-field" onchange="actualizarCombinado()">
                                    <option value="yape">Yape</option>
                                    <option value="plin">Plin</option>
                                </select>
                            </div>
                        </div>
                        <!-- QR para que el cliente escanee la parte digital -->
                        <div style="text-align:center;margin-top:10px;">
                            <div id="cobro-comb-qr-wrap" style="display:none;">
                                <div style="font-size:11px;color:var(--text-muted);font-weight:600;margin-bottom:5px;" id="cobro-comb-qr-titulo">Escanea para pagar</div>
                                <img id="cobro-comb-qr-img" src="" alt="QR" style="max-width:150px;max-height:150px;border:1px solid var(--border);border-radius:10px;padding:5px;background:#fff;">
                            </div>
                            <div id="cobro-comb-qr-vacio" style="display:none;font-size:11px;color:var(--text-muted);background:#fff;border-radius:8px;padding:8px;">
                                No has configurado el QR. Súbelo en <b>Empresa</b>.
                            </div>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:12px;">
                            <span style="color:var(--text-muted);font-weight:600;">Efectivo a cobrar:</span>
                            <span style="font-weight:700;color:var(--green);" id="cobro-comb-efectivo">S/ 0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="campo-tarjeta" class="metodo-fields" style="margin-top:14px;display:none;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="input-group"><label class="input-label">Últimos 4 dígitos</label><input type="text" id="cobro-tarjeta-4" class="input-field" maxlength="4" placeholder="1234"></div>
                    <div class="input-group"><label class="input-label">Nº de voucher</label><input type="text" id="cobro-tarjeta-voucher" class="input-field" placeholder="000123"></div>
                </div>
            </div>

            <!-- Campo Yape / Plin: muestra el QR para escanear -->
            <div id="campo-yape" class="metodo-fields" style="margin-top:14px;display:none;">
                <div style="text-align:center;">
                    <div id="cobro-qr-wrap" style="display:none;">
                        <div style="font-size:12px;color:var(--text-muted);font-weight:600;margin-bottom:6px;" id="cobro-qr-titulo">Escanea para pagar</div>
                        <img id="cobro-qr-img" src="" alt="QR" style="max-width:200px;max-height:200px;border:1px solid var(--border);border-radius:10px;padding:6px;background:#fff;">
                    </div>
                    <div id="cobro-qr-vacio" style="display:none;font-size:12px;color:var(--text-muted);background:var(--bg-light);border-radius:8px;padding:12px;">
                        No has configurado el QR. Súbelo en <b>Empresa</b>.
                    </div>
                </div>
            </div>

            <div id="campo-transferencia" class="metodo-fields" style="margin-top:14px;display:none;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="input-group"><label class="input-label">Banco</label>
                        <select id="cobro-trans-banco" class="input-field">
                            <option value="BCP">BCP</option>
                            <option value="BBVA">BBVA</option>
                            <option value="Interbank">Interbank</option>
                            <option value="Scotiabank">Scotiabank</option>
                            <option value="Banco Nación">Banco de la Nación</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="input-group"><label class="input-label">Nº de operación</label><input type="text" id="cobro-trans-op" class="input-field" placeholder="123456789"></div>
                </div>
            </div>

            <div id="info-sunat" style="background: #ecfdf5; border-radius: 8px; padding: 10px 12px; margin-top: 14px; font-size: 12px; color: #065f46; display:none;">
                <i class="fa-solid fa-circle-check"></i>
                <b>Boleta/Factura electrónica:</b> el comprobante quedará en cola de envío a SUNAT. Podrás enviarlo desde "Comprobantes SUNAT".
            </div>

            <div id="info-ticket" style="background: #fef3c7; border-radius: 8px; padding: 10px 12px; margin-top: 14px; font-size: 12px; color: #92400e;">
                <i class="fa-solid fa-info-circle"></i>
                Documento interno (no SUNAT). Para emitir comprobante electrónico elige Boleta o Factura.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-cobro')">Cancelar</button>
            <button class="btn btn-primary" id="btn-confirmar-cobro" onclick="confirmarCobro()">
                <i class="fa-solid fa-check"></i> Confirmar Cobro
            </button>
        </div>
    </div>
</div>
