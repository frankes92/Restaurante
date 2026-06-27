/* Caja movil */
const mcState = { sesion: null, resumen: {}, movs: [] };

async function mcInit() {
    mcState.sesion = await API.cajaSesion();
    if (!mcState.sesion || !mcState.sesion.idsesion) {
        document.getElementById('m-caja-sub').textContent = 'Cerrada';
        document.getElementById('m-caja-estado').innerHTML =
            '<div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:14px;border-radius:12px;margin-bottom:14px;font-size:13px;font-weight:700;text-align:center;">'
            + '<i class="fa-solid fa-circle-exclamation"></i> No hay caja abierta</div>';
        document.getElementById('m-caja-acciones').innerHTML = can('caja')
            ? '<button class="m-btn success" onclick="mcAbrirSesion()"><i class="fa-solid fa-cash-register"></i> Abrir Caja</button>'
            : '<div style="font-size:12px;color:var(--m-muted);text-align:center;">Sin permiso para abrir caja</div>';
        document.getElementById('m-caja-kpis').innerHTML = '';
        document.getElementById('m-caja-movs').innerHTML = '';
        return;
    }
    [mcState.resumen, mcState.movs] = await Promise.all([
        API.cajaResumen(mcState.sesion.idsesion),
        API.cajaMovimientos(mcState.sesion.idsesion)
    ]);
    if (!Array.isArray(mcState.movs)) {
        // datos puede venir como rs => convertir
        const arr = []; if (mcState.movs && typeof mcState.movs[Symbol.iterator] === 'function') { for (const r of mcState.movs) arr.push(r); }
        mcState.movs = arr;
    }
    mcRender();
}

function mcRender() {
    const r = mcState.resumen || {};
    document.getElementById('m-caja-sub').textContent = 'Abierta · ' + (mcState.sesion.fecha_apertura || '').slice(0,16).replace('T',' ');

    document.getElementById('m-caja-estado').innerHTML =
        '<div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:12px 14px;border-radius:12px;margin-bottom:14px;font-size:12px;font-weight:700;display:flex;align-items:center;gap:10px;">'
        + '<i class="fa-solid fa-circle-check" style="color:#10b981;"></i>'
        + 'Caja abierta · Apertura ' + fmt.money(mcState.sesion.monto_inicial)
        + '</div>';

    const ventasTotal = Number(r.total_ventas || 0);
    const ventasEfe   = Number(r.total_efectivo || 0);
    const ventasTar   = Number(r.total_tarjeta  || 0);
    const ventasYap   = Number(r.total_yape     || 0);

    document.getElementById('m-caja-kpis').innerHTML = `
        <div class="m-kpi green">
            <div class="m-kpi-lbl">VENTAS</div>
            <div class="m-kpi-val">${fmt.money(ventasTotal)}</div>
            <div class="m-kpi-sub">${r.total_ordenes || 0} órdenes</div>
        </div>
        <div class="m-kpi">
            <div class="m-kpi-lbl">EFECTIVO</div>
            <div class="m-kpi-val">${fmt.money(ventasEfe)}</div>
            <div class="m-kpi-sub">en caja</div>
        </div>
        <div class="m-kpi blue">
            <div class="m-kpi-lbl">TARJETA</div>
            <div class="m-kpi-val">${fmt.money(ventasTar)}</div>
        </div>
        <div class="m-kpi orange">
            <div class="m-kpi-lbl">YAPE/PLIN</div>
            <div class="m-kpi-val">${fmt.money(ventasYap)}</div>
        </div>
    `;

    let acciones = '';
    if (can('caja')) {
        acciones += '<button class="m-btn ghost" onclick="mcAgregarMov(\'ingreso\')"><i class="fa-solid fa-arrow-down" style="color:#10b981;"></i> Registrar Ingreso</button>';
        acciones += '<button class="m-btn ghost" onclick="mcAgregarMov(\'egreso\')"><i class="fa-solid fa-arrow-up" style="color:#dc2626;"></i> Registrar Egreso</button>';
        acciones += '<button class="m-btn warning" onclick="mcCerrarSesion()"><i class="fa-solid fa-lock"></i> Cerrar Caja</button>';
    }
    document.getElementById('m-caja-acciones').innerHTML = acciones;

    const movs = mcState.movs || [];
    if (!movs.length) {
        document.getElementById('m-caja-movs').innerHTML = '<div class="m-empty"><i class="fa-solid fa-list"></i><h3>Sin movimientos</h3><p>Aún no hay movimientos registrados</p></div>';
    } else {
        document.getElementById('m-caja-movs').innerHTML = movs.map(m => {
            const monto = Number(m.monto);
            const esEgreso = m.tipo === 'egreso';
            const signo = esEgreso ? '-' : '+';
            const iconClass = m.tipo === 'venta' ? 'venta' : (esEgreso ? 'egreso' : 'ingreso');
            const icon = m.tipo === 'venta' ? 'fa-cart-shopping' : (esEgreso ? 'fa-arrow-up' : 'fa-arrow-down');
            return `
                <div class="m-mov-item">
                    <div class="m-mov-icon ${iconClass}"><i class="fa-solid ${icon}"></i></div>
                    <div class="m-mov-info">
                        <div class="m-mov-nota">${mcEsc(m.nota || (m.tipo === 'venta' ? 'Venta' : '—'))}</div>
                        <div class="m-mov-meta">${(m.fecha || '').slice(11,16)} · ${m.metodo_pago || ''}</div>
                    </div>
                    <div class="m-mov-monto ${esEgreso ? 'neg' : 'pos'}">${signo}${fmt.money(monto).replace(/^\D+\s*/, '')}</div>
                </div>`;
        }).join('');
    }
}

window.mcAbrirSesion = async () => {
    const { value: monto } = await Swal.fire({
        title: 'Abrir Caja',
        input: 'number',
        inputLabel: 'Monto inicial en efectivo (S/)',
        inputPlaceholder: '0.00',
        inputAttributes: { min: 0, step: 0.10 },
        showCancelButton: true,
        confirmButtonText: 'Abrir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#10b981',
        reverseButtons: true
    });
    if (monto == null) return;   // null/undefined = canceló (vacío se toma como 0)
    const r = await API.cajaAbrir({ monto_inicial: parseFloat(monto) || 0 });
    if (r.ok) { showToast('Caja abierta', 'success'); await mcInit(); }
    else      { showToast(r.msg || 'Error', 'error'); }
};

window.mcAgregarMov = async (tipo) => {
    const titulo = tipo === 'ingreso' ? 'Registrar ingreso' : 'Registrar egreso';
    const { value: form } = await Swal.fire({
        title: titulo,
        html: '<input id="m-mv-monto" class="swal2-input" type="number" min="0.10" step="0.10" placeholder="Monto S/">' +
              '<input id="m-mv-nota" class="swal2-input" placeholder="Concepto / nota">',
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#5b3df5',
        reverseButtons: true,
        preConfirm: () => {
            const monto = parseFloat(document.getElementById('m-mv-monto').value);
            const nota  = document.getElementById('m-mv-nota').value.trim();
            if (!monto || monto <= 0) { Swal.showValidationMessage('Monto inválido'); return false; }
            return { monto, nota };
        }
    });
    if (!form) return;
    const r = await API.cajaAgregarMov({
        idsesion: mcState.sesion.idsesion,
        tipo, monto: form.monto, nota: form.nota,
        metodo_pago: 'efectivo'
    });
    if (r.ok) { showToast('Movimiento registrado', 'success'); await mcInit(); }
    else      { showToast(r.msg || 'Error', 'error'); }
};

window.mcCerrarSesion = async () => {
    const r = await API.cajaResumen(mcState.sesion.idsesion);
    const efe = Number(r.total_efectivo || 0) + Number(mcState.sesion.monto_inicial || 0);
    const { value: form } = await Swal.fire({
        title: 'Cerrar Caja',
        html: '<div style="font-size:13px;color:#6b7280;margin-bottom:10px;">Total esperado en efectivo: <b>' + fmt.money(efe) + '</b></div>' +
              '<input id="m-cl-monto" class="swal2-input" type="number" min="0" step="0.10" placeholder="Monto contado en efectivo">' +
              '<textarea id="m-cl-nota" class="swal2-textarea" placeholder="Notas de cierre (opcional)"></textarea>',
        showCancelButton: true,
        confirmButtonText: 'Cerrar caja',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        reverseButtons: true,
        preConfirm: () => {
            const monto = parseFloat(document.getElementById('m-cl-monto').value);
            const nota  = document.getElementById('m-cl-nota').value.trim();
            if (monto == null || isNaN(monto)) { Swal.showValidationMessage('Monto inválido'); return false; }
            return { monto, nota };
        }
    });
    if (!form) return;
    const rc = await API.cajaCerrar({
        idsesion: mcState.sesion.idsesion,
        monto_final: form.monto,
        nota: form.nota
    });
    if (rc.ok) { showToast('Caja cerrada', 'success'); await mcInit(); }
    else       { showToast(rc.msg || 'Error', 'error'); }
};

function mcEsc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

mcInit();
