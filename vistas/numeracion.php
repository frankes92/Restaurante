<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('config_numeracion');
$activePage = 'numeracion';
$pageTitle  = 'PUERTO HABANA POS — Numeración';
require __DIR__ . '/template/head.php';
?>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Numeración de Comprobantes</div>
                <div class="page-subtitle">Series y correlativos por tipo de documento</div>
            </div>
            <button class="btn btn-primary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Nueva serie</button>
        </div>

        <div class="page-content">
            <div class="card" style="padding:16px;">
                <table id="tbl-num" class="data-table" style="width:100%;">
                    <thead><tr>
                        <th>#</th><th>Tipo Doc</th><th>Serie</th><th>Último Nº</th><th>Próximo Nº</th><th>Descripción</th><th>Estado</th><th></th>
                    </tr></thead>
                </table>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<div class="modal-overlay" id="modal-num">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title" id="modal-num-title">Nueva serie</div>
            <button class="modal-close" onclick="closeModal('modal-num')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="n-id" value="">
            <input type="hidden" id="n-emp" value="1">
            <div class="input-group">
                <label class="input-label">Tipo de documento *</label>
                <select id="n-tipo" class="input-field">
                    <option value="01">01 — Factura</option>
                    <option value="03">03 — Boleta de venta</option>
                    <option value="07">07 — Nota de crédito</option>
                    <option value="08">08 — Nota de débito</option>
                </select>
            </div>
            <div class="input-group">
                <label class="input-label">Serie *</label>
                <input id="n-serie" class="input-field" maxlength="4" placeholder="F001 / B001 / FC01 / FD01">
            </div>
            <div class="input-group">
                <label class="input-label">Último número emitido</label>
                <input type="number" id="n-ult" class="input-field" min="0" value="0">
            </div>
            <div class="input-group">
                <label class="input-label">Descripción</label>
                <input id="n-desc" class="input-field">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal('modal-num')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardar()">Guardar</button>
        </div>
    </div>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script>
let dt;
$(function () {
    dt = $('#tbl-num').DataTable({
        ajax: { url: '../ajax/numeracion.php?op=listar&idempresa=1', dataSrc: '' },
        columns: [
            { data: 'idnumeracion' },
            { data: 'tipo_documento' },
            { data: 'serie', render: v => `<b>${v}</b>` },
            { data: 'ultimo_numero' },
            { data: null, render: r => '<span style="color:var(--primary);font-weight:600;">' + String(Number(r.ultimo_numero)+1).padStart(8,'0') + '</span>' },
            { data: 'descripcion' },
            { data: 'estado', render: v => Number(v)===1
                ? '<span class="badge badge-green">Activa</span>'
                : '<span class="badge badge-red">Inactiva</span>' },
            { data: null, orderable: false, render: r => `
                <div style="text-align:right;">
                    <button class="btn btn-sm btn-icon" onclick="editar(${r.idnumeracion})"><i class="fa-solid fa-pen"></i></button>
                    ${Number(r.estado)===1
                        ? `<button class="btn btn-sm btn-icon" onclick="toggle(${r.idnumeracion}, 0)"><i class="fa-solid fa-ban"></i></button>`
                        : `<button class="btn btn-sm btn-icon" onclick="toggle(${r.idnumeracion}, 1)"><i class="fa-solid fa-check"></i></button>`}
                </div>`
            }
        ],
        order: [[0,'asc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
    });
});

window.openAdd = () => {
    $('#modal-num-title').text('Nueva serie');
    $('#n-id').val(''); $('#n-tipo').val('03'); $('#n-serie').val(''); $('#n-ult').val(0); $('#n-desc').val('');
    openModal('modal-num');
};

window.editar = async (id) => {
    const r = await Http.post('../ajax/numeracion.php?op=mostrar', { idnumeracion: id });
    if (!r) return;
    $('#modal-num-title').text('Editar serie');
    $('#n-id').val(r.idnumeracion);
    $('#n-tipo').val(r.tipo_documento);
    $('#n-serie').val(r.serie);
    $('#n-ult').val(r.ultimo_numero);
    $('#n-desc').val(r.descripcion);
    openModal('modal-num');
};

window.guardar = async () => {
    const payload = {
        idnumeracion:    $('#n-id').val(),
        idempresa:       $('#n-emp').val(),
        tipo_documento:  $('#n-tipo').val(),
        serie:           $('#n-serie').val(),
        ultimo_numero:   $('#n-ult').val(),
        descripcion:     $('#n-desc').val()
    };
    const r = await Http.post('../ajax/numeracion.php?op=guardaryeditar', payload);
    if (r.ok) { showToast('Guardado', 'success'); closeModal('modal-num'); dt.ajax.reload(null, false); }
    else showToast(r.msg || 'Error', 'error');
};

window.toggle = async (id, activar) => {
    const op = activar === 1 ? 'activar' : 'desactivar';
    const r = await Http.post('../ajax/numeracion.php?op=' + op, { idnumeracion: id });
    if (r.ok) dt.ajax.reload(null, false);
};
</script>
</body>
</html>
