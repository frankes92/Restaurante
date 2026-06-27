/* whatsapp.js — funciones globales para modales de WhatsApp */

const WA_API = '../ajax/whatsapp.php';

// =====================================================================
// EMOJI PICKER (reusable)
// Categorias y emojis comunes para mensajeria/marketing.
// Se insertan como caracteres UTF-8 directamente en el textarea.
// =====================================================================
const WA_EMOJIS = {
    'Caras':       ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🥳','🤩','🥺','😢','😭','😤','😠','😡','🤔','🤗','🤭','🤫','🤥','😶','😐','😑','😬','🙄','😯','😦','😧','😮','😲','😴','🤤','😪','😵','🤐','🥴','🤢','🤮','🤧','😷','🤒','🤕','🤑','🤠','😈','👿','👹','👺','🤡','💩','👻','💀','👽','🤖'],
    'Manos':       ['👋','🤚','🖐','✋','🖖','👌','🤌','🤏','✌','🤞','🤟','🤘','🤙','👈','👉','👆','🖕','👇','☝','👍','👎','✊','👊','🤛','🤜','👏','🙌','👐','🤲','🤝','🙏','✍','💅','🤳','💪','🦾','🦿','🦵','🦶','👂','🦻','👃','🧠','🫀','🫁','🦷','🦴','👀','👁','👅','👄'],
    'Personas':    ['👶','🧒','👦','👧','🧑','👨','👩','🧓','👴','👵','🙍','🙎','🙅','🙆','💁','🙋','🧏','🙇','🤦','🤷','👮','💂','🥷','👷','🤴','👸','👳','👲','🧕','🤵','👰','🤰','🤱','👼','🎅','🤶','🦸','🦹','🧙','🧚','🧛','🧜','🧝','🧞','🧟','💆','💇','🚶','🧍','🧎','🏃','💃','🕺','🕴','🤺','🏇','⛷','🏂','🏌','🏄','🚣','🏊','⛹','🏋','🚴','🚵'],
    'Comida':      ['🍔','🍟','🍕','🌭','🥪','🌮','🌯','🥙','🧆','🥚','🍳','🥘','🍲','🥣','🥗','🍿','🧈','🧂','🥫','🍱','🍘','🍙','🍚','🍛','🍜','🍝','🍠','🍢','🍣','🍤','🍥','🥮','🍡','🥟','🥠','🥡','🦀','🦞','🦐','🦑','🦪','🍦','🍧','🍨','🍩','🍪','🎂','🍰','🧁','🥧','🍫','🍬','🍭','🍮','🍯','🍼','🥛','☕','🍵','🍶','🍾','🍷','🍸','🍹','🍺','🍻','🥂','🥃','🥤','🧃','🧉','🧊','🥢','🍽','🍴','🥄'],
    'Frutas':      ['🍇','🍈','🍉','🍊','🍋','🍌','🍍','🥭','🍎','🍏','🍐','🍑','🍒','🍓','🫐','🥝','🍅','🫒','🥥','🥑','🍆','🥔','🥕','🌽','🌶','🫑','🥒','🥬','🥦','🧄','🧅','🍄','🥜','🌰','🍞','🥐','🥖','🫓','🥨','🥯','🥞','🧇','🧀','🍖','🍗','🥩','🥓'],
    'Festivo':     ['🎉','🎊','🎈','🎁','🎀','🎆','🎇','✨','🎄','🎃','🎗','🎫','🎖','🏆','🥇','🥈','🥉','⚽','🏀','🏈','⚾','🎾','🏐','🏉','🎱','🪀','🏓','🏸','🥊','🥋','🥅','⛳','⛸','🎣','🤿','🎽','🎿','🛷','🥌','🎯','🪁','🎮','🕹','🎰','🎲','🧩','🧸','🪅','🪆','♠','♥','♦','♣','♟','🃏','🀄','🎴','🎭','🖼','🎨','🧵','🧶'],
    'Trabajo':     ['💼','📁','📂','📅','📆','🗓','📇','📈','📉','📊','📋','📌','📍','📎','🖇','📏','📐','✂','🗃','🗄','🗑','🔒','🔓','🔏','🔐','🔑','🗝','🔨','🪓','⛏','⚒','🛠','🗡','⚔','🔫','🪃','🏹','🛡','🪚','🔧','🪛','🔩','⚙','🗜','⚖','🦯','🔗','⛓','🪝','🧰','🧲','🪜'],
    'Símbolos':    ['❤','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣','💕','💞','💓','💗','💖','💘','💝','💟','☮','✝','☪','🕉','☸','✡','🔯','🕎','☯','☦','🛐','⛎','♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓','🆔','⚛','🉑','☢','☣','📴','📳','🈶','🈚','🈸','🈺','🈷','✴','🆚','💮','🉐','㊙','㊗','🈴','🈵','🈹','🈲','🅰','🅱','🆎','🆑','🅾','🆘','❌','⭕','🛑','⛔','📛','🚫','💯','💢','♨','🚷','🚯','🚳','🚱','🔞','📵','🚭','❗','❕','❓','❔','‼','⁉','🔅','🔆','〽','⚠','🚸','🔱','⚜','🔰','♻','✅','🈯','💹','❇','✳','❎','🌐','💠','Ⓜ','🌀','💤','🏧','🚾','♿','🅿','🛗','🈳','🈂','🛂','🛃','🛄','🛅','🚹','🚺','🚼','⚧','🚻','🚮','🎦','📶','🈁','🔣','ℹ','🔤','🔡','🔠','🆖','🆗','🆙','🆒','🆕','🆓','0️⃣','1️⃣','2️⃣','3️⃣','4️⃣','5️⃣','6️⃣','7️⃣','8️⃣','9️⃣','🔟'],
    'Banderas':    ['🇵🇪','🇦🇷','🇧🇴','🇧🇷','🇨🇱','🇨🇴','🇪🇨','🇲🇽','🇪🇸','🇺🇸','🇨🇦','🇨🇺','🇩🇴','🇸🇻','🇬🇹','🇭🇳','🇳🇮','🇵🇦','🇵🇾','🇺🇾','🇻🇪','🇮🇹','🇫🇷','🇩🇪','🇬🇧','🇯🇵','🇨🇳','🇰🇷','🏳','🏴','🏁','🚩','🏳‍🌈','🏳‍⚧'],
};

/**
 * Construye el HTML del picker de emojis (categorias en pestañas).
 * Si ya existe contenido, no se vuelve a construir.
 */
window.buildEmojiPicker = function (pickerId, targetId) {
    const cont = document.getElementById(pickerId);
    if (!cont) return;
    if (cont.dataset.built === '1') return;

    let tabsHtml = '<div class="emoji-tabs">';
    let panelsHtml = '<div class="emoji-panels">';
    let i = 0;
    for (const cat in WA_EMOJIS) {
        const id = pickerId + '-cat-' + i;
        tabsHtml += `<button type="button" class="emoji-tab ${i===0?'active':''}" data-target="${id}">${cat}</button>`;
        panelsHtml += `<div class="emoji-panel ${i===0?'active':''}" id="${id}">`
            + WA_EMOJIS[cat].map(e => `<button type="button" class="emoji-btn" data-emoji="${e}">${e}</button>`).join('')
            + '</div>';
        i++;
    }
    tabsHtml += '</div>';
    panelsHtml += '</div>';
    cont.innerHTML = tabsHtml + panelsHtml;
    cont.dataset.built = '1';

    // Listener tabs
    cont.querySelectorAll('.emoji-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            cont.querySelectorAll('.emoji-tab').forEach(t => t.classList.remove('active'));
            cont.querySelectorAll('.emoji-panel').forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(tab.dataset.target).classList.add('active');
        });
    });
    // Listener emojis
    cont.querySelectorAll('.emoji-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(targetId);
            if (!target) return;
            const start = target.selectionStart, end = target.selectionEnd;
            const val = target.value;
            target.value = val.substring(0, start) + btn.dataset.emoji + val.substring(end);
            target.focus();
            target.selectionStart = target.selectionEnd = start + btn.dataset.emoji.length;
            // Disparar evento input para que se actualicen previews
            target.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
};

window.toggleEmojis = function (targetId, pickerId) {
    const p = document.getElementById(pickerId);
    if (!p) return;
    if (p.style.display === 'none' || !p.style.display) {
        buildEmojiPicker(pickerId, targetId);
        p.style.display = 'block';
    } else {
        p.style.display = 'none';
    }
};

window.insertarVarEn = function (targetId, v) {
    const ta = document.getElementById(targetId);
    if (!ta) return;
    const start = ta.selectionStart, end = ta.selectionEnd;
    ta.value = ta.value.substring(0, start) + v + ta.value.substring(end);
    ta.focus();
    ta.selectionEnd = start + v.length;
    ta.dispatchEvent(new Event('input', { bubbles: true }));
};

// CSS inyectado para el picker (una sola vez)
(function () {
    if (document.getElementById('wa-emoji-styles')) return;
    const css = `
    .emoji-picker { margin-top: 10px; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; background: #fff; }
    .emoji-tabs { display: flex; gap: 0; background: #f9fafb; border-bottom: 1px solid var(--border); overflow-x: auto; }
    .emoji-tab { padding: 8px 12px; background: transparent; border: 0; cursor: pointer; font-size: 11px; font-weight: 600; color: var(--text-muted); white-space: nowrap; font-family: inherit; }
    .emoji-tab:hover { color: var(--primary); }
    .emoji-tab.active { color: var(--primary); border-bottom: 2px solid var(--primary); background: #fff; }
    .emoji-panels { padding: 8px; max-height: 200px; overflow-y: auto; }
    .emoji-panel { display: none; flex-wrap: wrap; gap: 2px; }
    .emoji-panel.active { display: flex; }
    .emoji-btn { width: 36px; height: 36px; background: transparent; border: 0; cursor: pointer; font-size: 22px; line-height: 1; padding: 0; border-radius: 6px; transition: background .12s; font-family: 'Segoe UI Emoji', 'Apple Color Emoji', 'Noto Color Emoji', sans-serif; }
    .emoji-btn:hover { background: #f3f4f6; transform: scale(1.15); }
    `;
    const style = document.createElement('style');
    style.id = 'wa-emoji-styles';
    style.textContent = css;
    document.head.appendChild(style);
})();

// Estado del modal de envio actual
window._waEstado = {
    idcomprobante: null,
    idclifact:     null,
    idcliente:     null,
    nombre:        '',
    documento:     '',
    tipo_doc:      '',
    comprobante:   '',
    tipo_documento:'',  // 01 / 03
    total:         0,
    link_pdf:      '',
    plantillas:    [],
    plantillaActual: null,
    pendienteImprimirCallback: null,  // callback que abre el ticket si elige imprimir
};

// =====================================================================
// MODAL POST-COBRO
// =====================================================================
window.mostrarPostCobro = function (datos, imprimirCallback) {
    Object.assign(window._waEstado, datos);
    window._waEstado.pendienteImprimirCallback = imprimirCallback;

    const sim = (window.YAPEZ_CONFIG && window.YAPEZ_CONFIG.simbolo_moneda) || 'S/';
    document.getElementById('pc-numero').textContent = datos.comprobante || ('Orden #' + (datos.numero_interno || ''));
    document.getElementById('pc-total').textContent  = sim + ' ' + Number(datos.total || 0).toFixed(2);

    openModal('modal-post-cobro');
    if (window.applyPermissionsDOM) applyPermissionsDOM(document.getElementById('modal-post-cobro'));
};

window.postCobroImprimir = function () {
    closeModal('modal-post-cobro');
    if (typeof window._waEstado.pendienteImprimirCallback === 'function') {
        window._waEstado.pendienteImprimirCallback();
    }
};

window.postCobroWhatsapp = async function () {
    closeModal('modal-post-cobro');
    await abrirModalWhatsapp(window._waEstado);
};

// =====================================================================
// MODAL DE ENVIO WHATSAPP
// =====================================================================
window.abrirModalWhatsapp = async function (datos) {
    Object.assign(window._waEstado, datos);

    // Header con datos del cliente
    const tipoLbl = (datos.tipo_doc === '6' ? 'RUC' : 'DNI');
    document.getElementById('wa-cliente-info').innerHTML =
        '<b>' + (datos.nombre || 'Cliente') + '</b><br>' +
        tipoLbl + ': ' + (datos.documento || '—') +
        (datos.comprobante ? '<br>Comprobante: <b>' + datos.comprobante + '</b>' : '');

    // Cargar plantillas
    if (!window._waEstado.plantillas || !window._waEstado.plantillas.length) {
        try {
            window._waEstado.plantillas = await Http.get(WA_API + '?op=plantillas&solo_activas=1');
        } catch(e) { window._waEstado.plantillas = []; }
    }
    const sel = document.getElementById('wa-plantilla');
    sel.innerHTML = '';
    window._waEstado.plantillas.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.idplantilla;
        opt.textContent = p.nombre;
        opt.dataset.codigo = p.codigo;
        sel.appendChild(opt);
    });

    // Auto-seleccionar plantilla segun tipo de comprobante
    let codigoDefault = 'gracias';
    if (datos.tipo_documento === '01') codigoDefault = 'factura';
    else if (datos.tipo_documento === '03') codigoDefault = 'boleta';

    const plantillaDefault = window._waEstado.plantillas.find(p => p.codigo === codigoDefault);
    if (plantillaDefault) {
        sel.value = plantillaDefault.idplantilla;
    }

    // Auto-completar numero (busca por documento)
    document.getElementById('wa-numero').value = datos.numero_whatsapp || '';
    if (!document.getElementById('wa-numero').value && datos.documento) {
        await waBuscarNumero();
    }

    // Cargar plantilla en el textarea
    waCargarPlantilla();

    openModal('modal-whatsapp');
};

window.waCargarPlantilla = async function () {
    const sel = document.getElementById('wa-plantilla');
    const idp = sel.value;
    if (!idp) return;
    const plantilla = window._waEstado.plantillas.find(p => Number(p.idplantilla) === Number(idp));
    if (!plantilla) return;
    window._waEstado.plantillaActual = plantilla;

    // Renderizar mensaje con datos reales (preview en backend)
    try {
        const r = await Http.post(WA_API + '?op=preview', {
            mensaje:        plantilla.mensaje,
            nombre:         window._waEstado.nombre,
            documento:      window._waEstado.documento,
            tipo_doc:       window._waEstado.tipo_doc,
            comprobante:    window._waEstado.comprobante,
            tipo_documento: window._waEstado.tipo_documento,
            total:          window._waEstado.total,
            link_pdf:       window._waEstado.link_pdf,
            numero:         document.getElementById('wa-numero').value,
        });
        document.getElementById('wa-mensaje').value = r.mensaje || plantilla.mensaje;
    } catch (e) {
        document.getElementById('wa-mensaje').value = plantilla.mensaje;
    }
};

window.waBuscarNumero = async function () {
    const doc = window._waEstado.documento;
    if (!doc) { showToast('Sin documento del cliente para buscar', 'info'); return; }
    try {
        const r = await Http.get(WA_API + '?op=buscarPorDocumento&documento=' + encodeURIComponent(doc));
        if (r && r.numero) {
            document.getElementById('wa-numero').value = r.numero;
            showToast('Número encontrado y precargado', 'success');
        } else {
            showToast('No hay número guardado para este cliente', 'info');
        }
    } catch (e) {}
};

/**
 * Normaliza un numero peruano a formato internacional (51XXXXXXXXX).
 */
window.waNormalizarNumeroJs = function (numero) {
    const solo = String(numero).replace(/\D+/g, '');
    if (!solo) return '';
    if (solo.length >= 11 && solo.startsWith('51')) return solo;
    if (solo.length === 9 && solo[0] === '9') return '51' + solo;
    if (solo.length < 9) return '';
    return solo;
};

/**
 * Construye el link de WhatsApp usando api.whatsapp.com (oficial)
 * y encodeURIComponent del navegador, que maneja emojis correctamente.
 */
window.waConstruirLink = function (numeroNorm, mensaje) {
    return 'https://api.whatsapp.com/send?phone=' + numeroNorm
         + '&text=' + encodeURIComponent(mensaje);
};

window.waEnviar = async function () {
    const numero    = document.getElementById('wa-numero').value.trim();
    const mensajeUI = document.getElementById('wa-mensaje').value.trim();
    if (!numero)    { showToast('Ingresa el número del cliente', 'error'); return; }
    if (!mensajeUI) { showToast('Mensaje vacío', 'error'); return; }

    // Normalizar numero localmente
    const numeroNorm = waNormalizarNumeroJs(numero);
    if (!numeroNorm) {
        showToast('Número inválido. Usa formato 9XXXXXXXX (9 dígitos)', 'error');
        return;
    }

    // Renderizar el mensaje (reemplazar variables) en el backend
    // SOLO si el mensaje aún tiene placeholders sin reemplazar.
    let mensajeFinal = mensajeUI;
    if (/\{[a-z_]+\}/i.test(mensajeUI)) {
        try {
            const r = await Http.post(WA_API + '?op=preview', {
                mensaje:        mensajeUI,
                numero:         numeroNorm,
                nombre:         window._waEstado.nombre,
                documento:      window._waEstado.documento,
                tipo_doc:       window._waEstado.tipo_doc,
                comprobante:    window._waEstado.comprobante,
                tipo_documento: window._waEstado.tipo_documento,
                total:          window._waEstado.total,
                link_pdf:       window._waEstado.link_pdf,
            });
            mensajeFinal = (r && r.mensaje) ? r.mensaje : mensajeUI;
        } catch (e) {
            mensajeFinal = mensajeUI;
        }
    }

    // CONSTRUIR EL URL EN EL CLIENTE (encodeURIComponent del navegador
    // maneja emojis 4-byte UTF-8 correctamente y WhatsApp los recibe intactos).
    const url = waConstruirLink(numeroNorm, mensajeFinal);

    // Registrar el envio en el historial (no critico)
    try {
        await Http.post(WA_API + '?op=registrarEnvio', {
            idcliente:      window._waEstado.idcliente || '',
            idclifact:      window._waEstado.idclifact || '',
            idcomprobante:  window._waEstado.idcomprobante || '',
            idplantilla:    window._waEstado.plantillaActual ? window._waEstado.plantillaActual.idplantilla : '',
            numero:         numeroNorm,
            nombre_cliente: window._waEstado.nombre,
            documento:      window._waEstado.documento,
            mensaje:        mensajeFinal,
            tipo:           window._waEstado.idcomprobante ? 'cobro' : 'manual',
            guardar_numero: document.getElementById('wa-guardar-numero').checked ? 1 : 0,
        });
    } catch (e) {}

    // Abrir WhatsApp en nueva pestaña
    window.open(url, '_blank');
    closeModal('modal-whatsapp');
    showToast('WhatsApp abierto. Confirma el envío en la pestaña.', 'success');
};
