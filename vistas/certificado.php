<?php
require_once __DIR__ . "/../config/auth.php";
requirePermiso('config_certificado');
$activePage = 'certificado';
$pageTitle  = 'PUERTO HABANA POS — Certificado';
require __DIR__ . '/template/head.php';
?>
<body>
<div class="app">
    <?php require __DIR__ . '/template/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Certificado Digital</div>
                <div class="page-subtitle">Sube y gestiona el certificado .pfx para firmar comprobantes SUNAT</div>
            </div>
        </div>

        <div class="page-content">
            <div class="card" style="max-width:700px;">
                <div class="card-title">Subir nuevo certificado</div>
                <form id="form-cert" enctype="multipart/form-data">
                    <input type="hidden" name="idempresa" value="1">
                    <div class="input-group">
                        <label class="input-label">Archivo .pfx o .p12</label>
                        <input type="file" name="archivo" accept=".pfx,.p12" class="input-field" required>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Clave del certificado *</label>
                        <input type="password" name="clave" class="input-field" required>
                    </div>
                    <div style="background:#fef3c7;border-radius:8px;padding:10px 12px;margin-bottom:12px;font-size:12px;color:#92400e;">
                        <i class="fa-solid fa-info-circle"></i>
                        El sistema valida la clave abriendo el certificado antes de guardarlo.
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-upload"></i> Subir certificado</button>
                </form>
            </div>

            <div class="card" style="max-width:900px;margin-top:20px;padding:16px;">
                <div class="card-title">Certificados cargados</div>
                <table id="tbl-certs" class="data-table" style="width:100%;">
                    <thead>
                        <tr><th>#</th><th>Archivo</th><th>Tipo</th><th>Fecha carga</th><th>Activo</th><th></th></tr>
                    </thead>
                </table>
            </div>
        </div>

        <?php require __DIR__ . '/template/footer.php'; ?>
    </main>
</div>

<script src="scripts/core.js?v=<?php echo filemtime(__DIR__ . '/scripts/core.js'); ?>"></script>
<script>
let dt;
function recargar() {
    if (dt) dt.ajax.reload(null, false);
}
$(function () {
    dt = $('#tbl-certs').DataTable({
        processing: true,
        ajax: { url: '../ajax/cargacertificado.php?op=listar&idempresa=1', dataSrc: '' },
        columns: [
            { data: 'idcertificado' },
            { data: 'nombre_archivo' },
            { data: 'tipo', render: v => `<span class="badge ${v==='produccion'?'badge-green':'badge-orange'}">${v}</span>` },
            { data: 'fecha_carga', render: v => v ? fmt.datetime(v) : '—' },
            { data: 'activo', render: v => Number(v)===1 ? '<span class="badge badge-green">Activo</span>' : '—' },
            { data: null, orderable: false, render: r => `
                <div style="text-align:right;">
                    ${Number(r.activo)===1 ? '' : `<button class="btn btn-sm" onclick="activar(${r.idcertificado})"><i class="fa-solid fa-check"></i> Activar</button>`}
                    <button class="btn btn-sm btn-icon" onclick="eliminar(${r.idcertificado})"><i class="fa-solid fa-trash"></i></button>
                </div>`
            }
        ],
        order: [[0,'desc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
    });

    $('#form-cert').on('submit', function (ev) {
        ev.preventDefault();
        const fd = new FormData(this);
        $.ajax({
            url: '../ajax/cargacertificado.php?op=subir',
            type: 'POST', data: fd, contentType: false, processData: false, dataType: 'json',
            success: function (r) {
                if (r.ok) { showToast('Certificado subido y activado', 'success'); $('#form-cert')[0].reset(); recargar(); }
                else showToast(r.msg || 'Error', 'error');
            }
        });
    });
});

window.activar = (id) => {
    $.post('../ajax/cargacertificado.php?op=activar', { idempresa: 1, idcertificado: id }, function (r) {
        if (r.ok) { showToast('Certificado activado', 'success'); recargar(); }
    }, 'json');
};
window.eliminar = async (id) => {
    if (!(await swalConfirm('¿Eliminar este certificado de la lista? Esta acción no se puede deshacer.', { title: 'Eliminar certificado', icon: 'warning', confirmText: 'Sí, eliminar' }))) return;
    $.post('../ajax/cargacertificado.php?op=eliminar', { idcertificado: id }, function (r) {
        if (r.ok) { showToast('Certificado eliminado', 'success'); recargar(); }
    }, 'json');
};
</script>
</body>
</html>
