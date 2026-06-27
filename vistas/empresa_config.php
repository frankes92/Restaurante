<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('config_empresa');
$activePage = 'empresa_config';
$pageTitle  = 'PUERTO HABANA POS — Empresa';
require __DIR__ . '/template/head.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
#crop-stage { max-height: 60vh; }
#crop-stage img { max-width: 100%; display: block; }
.bridge-field { font-family: 'Courier New', monospace; }
.logo-box { display: flex; align-items: center; gap: 16px; padding: 14px; border: 1px dashed var(--border); border-radius: 12px; background: var(--bg-light); }
.logo-preview { width: 110px; height: 110px; border-radius: 12px; background: #fff; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; overflow: hidden; }
.logo-preview img { max-width: 100%; max-height: 100%; }
.logo-preview .placeholder { font-size: 11px; color: var(--text-muted); }
.format-pick { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.format-card { border: 2px solid var(--border); border-radius: 10px; padding: 12px; cursor: pointer; text-align: center; transition: all .2s; }
.format-card:hover { border-color: #c4b5fd; }
.format-card.active { border-color: var(--primary); background: #f5f3ff; }
.format-card i { font-size: 22px; color: var(--primary); margin-bottom: 6px; }
.format-card .label { font-size: 13px; font-weight: 700; color: var(--text-dark); }
.format-card .desc { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
</style>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Empresa Emisora</div>
                <div class="page-subtitle">Datos de la empresa que emite los comprobantes electrónicos</div>
            </div>
        </div>

        <div class="page-content">
            <div class="card" style="max-width:900px;">
                <div class="card-title">Logo de la empresa</div>
                <div class="logo-box">
                    <div class="logo-preview">
                        <img id="logo-preview" src="" style="display:none;">
                        <span class="placeholder" id="logo-placeholder">Sin logo</span>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:13px;color:var(--text-dark);margin-bottom:6px;">
                            Sube el logo en <b>PNG, JPG, WEBP o SVG</b> (máx. 2 MB).
                        </div>
                        <div style="display:flex;gap:8px;">
                            <input type="file" id="logo-file" accept="image/png,image/jpeg,image/webp,image/svg+xml" style="display:none;">
                            <button class="btn btn-primary" data-perm="config_logo" type="button" onclick="document.getElementById('logo-file').click();"><i class="fa-solid fa-upload"></i> Subir nuevo logo</button>
                            <button class="btn" id="btn-borrar-logo" data-perm="config_logo" type="button" onclick="borrarLogo()" style="display:none;"><i class="fa-solid fa-trash" style="color:var(--red);"></i> Quitar logo</button>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">
                            Aparece en el sidebar y en los comprobantes (ticket / A4).
                        </div>
                    </div>
                </div>

                <div class="card-title" style="margin-top:24px;">QR de pago (Yape / Plin)</div>
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">
                    Sube la imagen del QR para que el cliente escanee al pagar. Puedes usar
                    <b>un solo QR para ambos</b> o uno distinto para Yape y para Plin.
                </div>
                <label style="display:flex;align-items:center;gap:10px;font-size:13px;cursor:pointer;font-weight:600;margin-bottom:12px;">
                    <input type="checkbox" id="qr-compartido-chk" checked onchange="toggleQrCompartido()">
                    Usar el mismo QR para Yape y Plin
                </label>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <!-- QR Yape (o compartido) -->
                    <div class="logo-box">
                        <div class="logo-preview">
                            <img id="qr-yape-preview" src="" style="display:none;">
                            <span class="placeholder" id="qr-yape-placeholder">Sin QR</span>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:13px;color:var(--text-dark);font-weight:600;margin-bottom:6px;" id="qr-yape-titulo">QR Yape</div>
                            <input type="file" id="qr-yape-file" accept="image/png,image/jpeg,image/webp" style="display:none;">
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <button class="btn btn-primary btn-sm" type="button" onclick="document.getElementById('qr-yape-file').click();"><i class="fa-solid fa-upload"></i> Subir</button>
                                <button class="btn btn-sm" id="btn-borrar-qr-yape" type="button" onclick="borrarQr('yape')" style="display:none;"><i class="fa-solid fa-trash" style="color:var(--red);"></i> Quitar</button>
                            </div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">PNG, JPG o WEBP · máx. 2 MB.</div>
                        </div>
                    </div>

                    <!-- QR Plin -->
                    <div class="logo-box" id="qr-plin-box">
                        <div class="logo-preview">
                            <img id="qr-plin-preview" src="" style="display:none;">
                            <span class="placeholder" id="qr-plin-placeholder">Sin QR</span>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:13px;color:var(--text-dark);font-weight:600;margin-bottom:6px;">QR Plin</div>
                            <input type="file" id="qr-plin-file" accept="image/png,image/jpeg,image/webp" style="display:none;">
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <button class="btn btn-primary btn-sm" type="button" onclick="document.getElementById('qr-plin-file').click();"><i class="fa-solid fa-upload"></i> Subir</button>
                                <button class="btn btn-sm" id="btn-borrar-qr-plin" type="button" onclick="borrarQr('plin')" style="display:none;"><i class="fa-solid fa-trash" style="color:var(--red);"></i> Quitar</button>
                            </div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">PNG, JPG o WEBP · máx. 2 MB.</div>
                        </div>
                    </div>
                </div>

                <div class="card-title" style="margin-top:24px;">Formato de impresión por defecto</div>
                <div class="format-pick">
                    <div class="format-card" data-format="ticket" onclick="selectFormat('ticket')">
                        <i class="fa-solid fa-receipt"></i>
                        <div class="label">Ticket 80mm</div>
                        <div class="desc">Para impresoras térmicas</div>
                    </div>
                    <div class="format-card" data-format="a4" onclick="selectFormat('a4')">
                        <i class="fa-solid fa-file-lines"></i>
                        <div class="label">Hoja A4</div>
                        <div class="desc">Para impresoras estándar</div>
                    </div>
                </div>
                <input type="hidden" id="formato_comprobante" value="ticket">

                <div class="card-title" style="margin-top:24px;">Datos generales</div>
                <form id="form-empresa">
                    <input type="hidden" id="idempresa" value="1">
                    <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;">
                        <div class="input-group"><label class="input-label">RUC *</label><input id="numero_ruc" class="input-field" maxlength="11"></div>
                        <div class="input-group"><label class="input-label">Razón Social *</label><input id="razon_social" class="input-field"></div>
                        <div class="input-group"><label class="input-label">Nombre Comercial</label><input id="nombre_comercial" class="input-field"></div>
                        <div class="input-group"><label class="input-label">Domicilio Fiscal *</label><input id="domicilio_fiscal" class="input-field"></div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">
                        <div class="input-group"><label class="input-label">Ubigeo</label><input id="ubigeo" class="input-field" maxlength="6" placeholder="150101"></div>
                        <div class="input-group"><label class="input-label">Departamento</label><input id="departamento" class="input-field"></div>
                        <div class="input-group"><label class="input-label">Provincia</label><input id="provincia" class="input-field"></div>
                        <div class="input-group"><label class="input-label">Distrito</label><input id="distrito" class="input-field"></div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                        <div class="input-group"><label class="input-label">Teléfono</label><input id="telefono" class="input-field"></div>
                        <div class="input-group"><label class="input-label">Correo</label><input type="email" id="correo" class="input-field"></div>
                        <div class="input-group"><label class="input-label">Web</label><input id="web" class="input-field"></div>
                    </div>

                    <div class="card-title" style="margin-top:20px;">Configuración Fiscal</div>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
                        <div class="input-group">
                            <label class="input-label">Tasa de IGV / IVA</label>
                            <select id="tasa_igv" class="input-field">
                                <option value="0.18">18% (Perú)</option>
                                <option value="0.10">10%</option>
                                <option value="0.12">12% (Ecuador)</option>
                                <option value="0.16">16% (México)</option>
                                <option value="0.19">19% (Colombia/Chile)</option>
                                <option value="0.21">21% (Argentina/España)</option>
                                <option value="0.00">0% (Exonerado)</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label class="input-label">Código de moneda</label>
                            <select id="codigo_moneda" class="input-field">
                                <option value="PEN">PEN — Sol Peruano</option>
                                <option value="USD">USD — Dólar</option>
                                <option value="EUR">EUR — Euro</option>
                                <option value="MXN">MXN — Peso Mexicano</option>
                                <option value="COP">COP — Peso Colombiano</option>
                                <option value="CLP">CLP — Peso Chileno</option>
                                <option value="ARS">ARS — Peso Argentino</option>
                                <option value="BRL">BRL — Real Brasileño</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label class="input-label">Símbolo</label>
                            <input id="simbolo_moneda" class="input-field" maxlength="5" placeholder="S/, $, €, R$">
                        </div>
                    </div>

                    <div class="card-title" style="margin-top:20px;">Credenciales SOL (SUNAT)</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                        <div class="input-group"><label class="input-label">Usuario SOL</label><input id="usuario_sol" class="input-field" placeholder="MODDATOS"></div>
                        <div class="input-group"><label class="input-label">Clave SOL <small style="color:var(--text-muted);">(dejar vacío para no cambiar)</small></label><input type="password" id="clave_sol" class="input-field"></div>
                        <div class="input-group">
                            <label class="input-label">Ambiente</label>
                            <select id="ambiente" class="input-field">
                                <option value="beta">BETA / Pruebas</option>
                                <option value="produccion">PRODUCCIÓN</option>
                            </select>
                        </div>
                    </div>

                    <!-- Envio automatico a SUNAT -->
                    <div style="margin-top:16px;background:var(--bg-light);border:1px solid var(--border);border-radius:10px;padding:14px 16px;">
                        <label style="display:flex;align-items:center;gap:12px;cursor:pointer;">
                            <input type="checkbox" id="envio_sunat_automatico" style="width:18px;height:18px;cursor:pointer;flex-shrink:0;">
                            <span>
                                <span style="font-weight:700;font-size:14px;color:var(--text-dark);">
                                    <i class="fa-solid fa-paper-plane" style="color:var(--primary);"></i>
                                    Enviar a SUNAT automáticamente al emitir
                                </span>
                                <small style="display:block;color:var(--text-muted);font-size:12px;margin-top:3px;">
                                    Si está activo, al cobrar una <b>boleta o factura electrónica</b> se envía a SUNAT
                                    en el momento. Si está desactivado, el comprobante queda en cola para enviarlo
                                    manualmente desde "Comprobantes SUNAT".
                                </small>
                            </span>
                        </label>
                    </div>

                    <!-- Glosa interna en nota de venta / ticket -->
                    <div style="margin-top:16px;background:var(--bg-light);border:1px solid var(--border);border-radius:10px;padding:14px 16px;">
                        <label style="display:flex;align-items:center;gap:12px;cursor:pointer;">
                            <input type="checkbox" id="mostrar_glosa_interna" style="width:18px;height:18px;cursor:pointer;flex-shrink:0;">
                            <span>
                                <span style="font-weight:700;font-size:14px;color:var(--text-dark);">
                                    <i class="fa-solid fa-receipt" style="color:var(--primary);"></i>
                                    Mostrar glosa interna en nota de venta / ticket
                                </span>
                                <small style="display:block;color:var(--text-muted);font-size:12px;margin-top:3px;">
                                    Si está activo, al final de la nota de venta/ticket aparece el texto
                                    “Documento interno de uso comercial. No constituye comprobante de pago electrónico SUNAT.”
                                    Desactívalo para que no se imprima.
                                </small>
                            </span>
                        </label>
                    </div>

                    <div style="margin-top:18px;text-align:right;">
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Guardar</button>
                    </div>
                </form>
            </div>

            <!-- ============ BRIDGE DE IMPRESIÓN ============ -->
            <div class="card" style="max-width:900px;margin-top:18px;">
                <div class="card-title"><i class="fa-solid fa-print" style="color:var(--primary);"></i> Bridge de impresión</div>
                <div style="font-size:13px;color:var(--text-muted);line-height:1.6;margin-bottom:14px;">
                    El <b>Bridge</b> es un programa que corre en una PC del local (la conectada a las impresoras térmicas).
                    Cada empresa tiene su <b>token único</b>. Genera aquí el archivo <code>config.php</code> con los datos
                    de <b>esta</b> empresa y reemplázalo en la carpeta <code>bridge\</code> de la PC del local.
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="input-group">
                        <label class="input-label">URL del servidor (CLOUD_URL)</label>
                        <input id="bridge-url" class="input-field bridge-field" placeholder="https://miempresa.ripasoft.com">
                        <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block;">
                            La dirección donde está instalado este sistema. Sin barra al final.
                        </small>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Intervalo de consulta (segundos)</label>
                        <input id="bridge-poll" type="number" min="1" max="30" value="3" class="input-field bridge-field">
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Token de autenticación (TOKEN)</label>
                    <div style="display:flex;gap:8px;">
                        <input id="bridge-token" class="input-field bridge-field" readonly style="flex:1;background:var(--bg-light);">
                        <button class="btn" type="button" onclick="copiarToken()"><i class="fa-solid fa-copy"></i> Copiar</button>
                    </div>
                    <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block;">
                        Este token es propio de esta empresa y ya coincide con el del servidor. No lo compartas.
                    </small>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                    <button class="btn btn-primary" type="button" onclick="descargarBridgeConfig()">
                        <i class="fa-solid fa-download"></i> Descargar config.php
                    </button>
                </div>

                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 14px;margin-top:14px;font-size:12px;color:#1e40af;line-height:1.7;">
                    <b>Pasos en la PC del local:</b>
                    <ol style="margin:6px 0 0 18px;padding:0;">
                        <li>Descarga el <code>config.php</code> con el botón de arriba.</li>
                        <li>Cópialo dentro de la carpeta <code>bridge\</code> (reemplaza el que estaba de la otra empresa).</li>
                        <li>Reinicia el Bridge (cierra y vuelve a ejecutar <code>bridge.php</code> / el servicio).</li>
                        <li>Listo: las impresiones de esta empresa ya saldrán por sus impresoras.</li>
                    </ol>
                </div>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<!-- ============ MODAL RECORTE DE IMAGEN ============ -->
<div class="modal-overlay" id="modal-crop">
    <div class="modal" style="max-width:520px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-crop-simple" style="color:var(--primary);"></i> Recortar imagen</div>
            <button class="modal-close" onclick="closeModal('modal-crop')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">
                Ajusta el recuadro sobre el QR y recorta. Puedes arrastrar y hacer zoom.
            </div>
            <div id="crop-stage" style="background:#1f2937;border-radius:10px;overflow:hidden;">
                <img id="crop-img" src="" alt="">
            </div>
            <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">
                <button class="btn btn-sm" type="button" onclick="cropZoom(0.1)"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
                <button class="btn btn-sm" type="button" onclick="cropZoom(-0.1)"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
                <button class="btn btn-sm" type="button" onclick="cropRotate(-90)"><i class="fa-solid fa-rotate-left"></i></button>
                <button class="btn btn-sm" type="button" onclick="cropRotate(90)"><i class="fa-solid fa-rotate-right"></i></button>
                <button class="btn btn-sm" type="button" onclick="cropReset()"><i class="fa-solid fa-arrows-rotate"></i> Reiniciar</button>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-crop')">Cancelar</button>
            <button class="btn btn-primary" id="btn-crop-confirm" onclick="confirmarRecorte()"><i class="fa-solid fa-check"></i> Recortar y subir</button>
        </div>
    </div>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
$(function () {
    const CAMPOS = ['numero_ruc','razon_social','nombre_comercial','domicilio_fiscal','ubigeo',
                    'departamento','provincia','distrito','telefono','correo','web',
                    'usuario_sol','ambiente','tasa_igv','simbolo_moneda','codigo_moneda','formato_comprobante'];

    function pintarLogo(ruta) {
        const img = $('#logo-preview');
        const ph  = $('#logo-placeholder');
        const btnBorrar = $('#btn-borrar-logo');
        if (ruta) {
            img.attr('src', '../' + ruta + '?t=' + Date.now()).show();
            ph.hide();
            btnBorrar.show();
        } else {
            img.hide();
            ph.show();
            btnBorrar.hide();
        }
    }

    window.selectFormat = function (val) {
        $('.format-card').removeClass('active');
        $('.format-card[data-format="' + val + '"]').addClass('active');
        $('#formato_comprobante').val(val);
    };

    function pintarQr(tipo, ruta) {
        const img = $('#qr-' + tipo + '-preview');
        const ph  = $('#qr-' + tipo + '-placeholder');
        const btn = $('#btn-borrar-qr-' + tipo);
        if (ruta) {
            img.attr('src', '../' + ruta + '?t=' + Date.now()).show();
            ph.hide(); btn.show();
        } else {
            img.hide(); ph.show(); btn.hide();
        }
    }

    // Modo compartido: si NO hay plin_qr, se usa el de Yape para ambos
    window.toggleQrCompartido = function () {
        const compartido = $('#qr-compartido-chk').is(':checked');
        $('#qr-plin-box').toggle(!compartido);
        $('#qr-yape-titulo').text(compartido ? 'QR (Yape y Plin)' : 'QR Yape');
    };

    // Cargar datos
    $.get('../ajax/empresa.php?op=mostrar&idempresa=1', function (e) {
        CAMPOS.forEach(k => $('#' + k).val(e[k] || ''));
        selectFormat(e.formato_comprobante || 'ticket');
        $('#envio_sunat_automatico').prop('checked', Number(e.envio_sunat_automatico) === 1);
        // Por defecto activa (si la empresa es antigua y no trae el campo, se muestra).
        $('#mostrar_glosa_interna').prop('checked', Number(e.mostrar_glosa_interna) !== 0);
        pintarLogo(e.logo || '');
        pintarQr('yape', e.yape_qr || '');
        pintarQr('plin', e.plin_qr || '');
        // Si hay QR de Plin distinto => modo separado; si no => compartido
        $('#qr-compartido-chk').prop('checked', !e.plin_qr);
        toggleQrCompartido();
    }, 'json');

    function subirQrArchivo(tipo, file, onDone, onErr) {
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) { showToast('La imagen excede 2 MB', 'error'); if (onErr) onErr(); return; }
        const fd = new FormData();
        fd.append('idempresa', '1');
        fd.append('tipo', tipo);
        fd.append('archivo', file);
        $.ajax({
            url: '../ajax/empresa.php?op=subirQr',
            type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json'
        }).done(function (r) {
            if (r.ok) {
                showToast('QR ' + tipo + ' actualizado', 'success');
                pintarQr(tipo, r.qr);
                if (onDone) onDone();
            } else {
                showToast(r.msg || 'No se pudo subir', 'error');
                if (onErr) onErr();
            }
        }).fail(function () { showToast('Error de red al subir', 'error'); if (onErr) onErr(); });
    }
    // ====== RECORTE DE IMAGEN (Cropper.js) ======
    let cropper = null;
    let cropTipo = null;     // 'yape' | 'plin'
    let cropName = 'qr.png';

    function abrirRecorte(tipo, file) {
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) { showToast('La imagen excede 2 MB', 'error'); return; }
        cropTipo = tipo;
        cropName = (file.name || 'qr.png');
        const reader = new FileReader();
        reader.onload = function (ev) {
            $('#crop-img').attr('src', ev.target.result);
            openModal('modal-crop');
            // Esperar a que el modal sea visible para inicializar Cropper
            setTimeout(function () {
                if (cropper) { cropper.destroy(); cropper = null; }
                const imgEl = document.getElementById('crop-img');
                cropper = new Cropper(imgEl, {
                    aspectRatio: 1,        // QR cuadrado
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false,
                    responsive: true
                });
            }, 200);
        };
        reader.readAsDataURL(file);
    }

    window.cropZoom   = (d) => { if (cropper) cropper.zoom(d); };
    window.cropRotate = (deg) => { if (cropper) cropper.rotate(deg); };
    window.cropReset  = () => { if (cropper) cropper.reset(); };

    window.confirmarRecorte = function () {
        if (!cropper) return;
        const btn = document.getElementById('btn-crop-confirm');
        const canvas = cropper.getCroppedCanvas({ maxWidth: 1000, maxHeight: 1000, imageSmoothingQuality: 'high' });
        if (!canvas) { showToast('No se pudo recortar', 'error'); return; }
        btn.disabled = true;
        canvas.toBlob(function (blob) {
            if (!blob) { btn.disabled = false; showToast('No se pudo procesar la imagen', 'error'); return; }
            // Nombre con extension png (siempre exportamos png)
            const file = new File([blob], (cropName.replace(/\.[^.]+$/, '') || 'qr') + '.png', { type: 'image/png' });
            subirQrArchivo(cropTipo, file, function () {
                btn.disabled = false;
                closeModal('modal-crop');
                if (cropper) { cropper.destroy(); cropper = null; }
            }, function () { btn.disabled = false; });
        }, 'image/png', 0.92);
    };

    $('#qr-yape-file').on('change', function () { abrirRecorte('yape', this.files[0]); this.value = ''; });
    $('#qr-plin-file').on('change', function () { abrirRecorte('plin', this.files[0]); this.value = ''; });

    window.borrarQr = async function (tipo) {
        if (!(await swalConfirm('¿Quitar el QR de ' + tipo + '?', { title: 'Quitar QR', icon: 'warning' }))) return;
        $.post('../ajax/empresa.php?op=eliminarQr', { idempresa: 1, tipo: tipo }, function (r) {
            if (r.ok) { showToast('QR eliminado', 'success'); pintarQr(tipo, ''); }
            else showToast('Error al eliminar', 'error');
        }, 'json');
    };

    // Auto-rellenar simbolo segun codigo de moneda
    const SIMBOLOS = { PEN: 'S/', USD: '$', EUR: '€', MXN: '$', COP: '$', CLP: '$', ARS: '$', BRL: 'R$' };
    $('#codigo_moneda').on('change', function () {
        const sim = SIMBOLOS[this.value];
        if (sim) $('#simbolo_moneda').val(sim);
    });

    // Subir logo
    $('#logo-file').on('change', function () {
        const f = this.files[0];
        if (!f) return;
        if (f.size > 2 * 1024 * 1024) { showToast('El logo excede 2 MB', 'error'); this.value = ''; return; }
        const fd = new FormData();
        fd.append('idempresa', '1');
        fd.append('archivo', f);
        $.ajax({
            url: '../ajax/empresa.php?op=subirLogo',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (r) {
            if (r.ok) {
                showToast('Logo actualizado', 'success');
                pintarLogo(r.logo);
            } else {
                showToast(r.msg || 'No se pudo subir', 'error');
            }
        });
        this.value = '';
    });

    window.borrarLogo = async function () {
        if (!(await swalConfirm('¿Quitar el logo actual?', { title: 'Quitar logo', icon: 'warning' }))) return;
        $.post('../ajax/empresa.php?op=eliminarLogo', { idempresa: 1 }, function (r) {
            if (r.ok) { showToast('Logo eliminado', 'success'); pintarLogo(''); }
            else showToast('Error al eliminar', 'error');
        }, 'json');
    };

    // ====== BRIDGE DE IMPRESIÓN ======
    $.get('../ajax/empresa.php?op=bridgeInfo', function (b) {
        $('#bridge-url').val(b.cloud_url || '');
        $('#bridge-token').val(b.token || '');
        $('#bridge-poll').val(b.poll_sec || 3);
    }, 'json');

    window.copiarToken = function () {
        const t = document.getElementById('bridge-token');
        t.select(); t.setSelectionRange(0, 99999);
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(t.value);
            } else {
                document.execCommand('copy');
            }
            showToast('Token copiado', 'success');
        } catch (e) { showToast('Cópialo manualmente', ''); }
    };

    window.descargarBridgeConfig = function () {
        let url = ($('#bridge-url').val() || '').trim().replace(/\/+$/, '');
        if (!url) { showToast('Ingresa la URL del servidor', 'error'); return; }
        let poll = parseInt($('#bridge-poll').val(), 10); if (!(poll >= 1)) poll = 3;
        const q = '../ajax/empresa.php?op=bridgeConfig'
                + '&cloud_url=' + encodeURIComponent(url)
                + '&poll_sec=' + poll;
        window.location = q;   // descarga directa (sesión por cookie)
    };

    $('#form-empresa').on('submit', function (ev) {
        ev.preventDefault();
        const payload = { idempresa: $('#idempresa').val(), clave_sol: $('#clave_sol').val() };
        if (!payload.clave_sol) delete payload.clave_sol;
        CAMPOS.forEach(k => { payload[k] = $('#' + k).val(); });
        payload.envio_sunat_automatico = $('#envio_sunat_automatico').is(':checked') ? 1 : 0;
        payload.mostrar_glosa_interna  = $('#mostrar_glosa_interna').is(':checked') ? 1 : 0;
        $.post('../ajax/empresa.php?op=editar', payload, function (r) {
            if (r.ok) {
                showToast('Empresa actualizada — recargando...', 'success');
                setTimeout(() => location.reload(), 800);
            } else showToast(r.msg || 'Error al guardar', 'error');
        }, 'json');
    });
});
</script>
</body>
</html>
