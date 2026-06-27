<?php
/**
 * Genera el PDF A4 oficial de un comprobante electronico SUNAT.
 * Incluye logo, datos emisor, receptor, items, totales y QR con la
 * representacion impresa segun resolucion SUNAT 097-2012/SUNAT.
 *
 * QR contiene: RUC|TIPO|SERIE|NUMERO|IGV|TOTAL|FECHA|TIPO_DOC_RECEPTOR|NUM_DOC_RECEPTOR|HASH
 */

require_once __DIR__ . '/fpdf/fpdf.php';
require_once __DIR__ . '/phpqrcode/qrlib.php';

class PdfComprobanteSunat
{
    private $pdf;
    private $comp;
    private $simbolo;
    private $tmpQr;

    public function generar(array $comp, $logoPath = null)
    {
        $this->comp = $comp;
        $this->simbolo = $comp['simbolo_moneda'] ?? 'S/';

        $this->pdf = new FPDF('P', 'mm', 'A4');
        $this->pdf->SetMargins(12, 12, 12);
        $this->pdf->SetAutoPageBreak(true, 14);
        $this->pdf->AddPage();
        $this->pdf->SetFont('Arial', '', 10);

        $this->cabecera($logoPath);
        $this->cuadroDoc();
        $this->datosCliente();
        $this->tablaItems();
        $this->totales();
        $this->codigoLetras();
        $this->qrCode();
        $this->footer();

        // Limpiar QR temporal
        if ($this->tmpQr && file_exists($this->tmpQr)) @unlink($this->tmpQr);
    }

    public function output($nombre = 'comprobante.pdf')
    {
        return $this->pdf->Output('I', $this->utf8($nombre));
    }

    public function outputFile($path)
    {
        return $this->pdf->Output('F', $path);
    }

    public function outputDownload($nombre = 'comprobante.pdf')
    {
        return $this->pdf->Output('D', $this->utf8($nombre));
    }

    // ========= Secciones del PDF =========

    private function cabecera($logoPath)
    {
        $c = $this->comp;
        // Logo
        if ($logoPath && file_exists($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png'], true)) {
                $this->pdf->Image($logoPath, 12, 12, 38, 0);
            }
        }

        $this->pdf->SetXY(56, 14);
        $this->pdf->SetFont('Arial', 'B', 14);
        $this->pdf->Cell(110, 6, $this->utf8($c['empresa_nombre_comercial'] ?: $c['empresa_razon']), 0, 2, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        if ($c['empresa_razon'] !== ($c['empresa_nombre_comercial'] ?? '')) {
            $this->pdf->Cell(110, 4, $this->utf8($c['empresa_razon']), 0, 2, 'L');
        }
        $this->pdf->Cell(110, 4, $this->utf8($c['empresa_direccion'] ?? ''), 0, 2, 'L');
        $this->pdf->Cell(110, 4, $this->utf8(($c['empresa_distrito'] ?? '') . ' - ' . ($c['empresa_provincia'] ?? '') . ' - ' . ($c['empresa_departamento'] ?? '')), 0, 2, 'L');
    }

    private function cuadroDoc()
    {
        $c = $this->comp;
        $tipoLabels = [
            '01' => 'FACTURA ELECTRONICA',
            '03' => 'BOLETA DE VENTA ELECTRONICA',
            '07' => 'NOTA DE CREDITO ELECTRONICA',
            '08' => 'NOTA DE DEBITO ELECTRONICA',
        ];
        $tipoLabel = $tipoLabels[$c['tipo_documento']] ?? 'COMPROBANTE';

        $this->pdf->SetXY(150, 13);
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->SetDrawColor(91, 61, 245);
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Cell(50, 7, 'RUC ' . $c['empresa_ruc'], 1, 2, 'C');
        $this->pdf->Cell(50, 7, $tipoLabel, 1, 2, 'C');
        $this->pdf->SetFont('Arial', 'B', 12);
        $numero = $c['serie'] . '-' . (ltrim($c['numero'], '0') ?: '0');
        $this->pdf->Cell(50, 8, $numero, 1, 2, 'C');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetXY(12, 36);
    }

    private function datosCliente()
    {
        $c = $this->comp;
        $this->pdf->Ln(8);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(35, 5, 'Fecha de emisión:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(80, 5, $this->utf8(date('d/m/Y', strtotime($c['fecha_emision']))), 0, 0, 'L');
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(35, 5, 'Moneda:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(0, 5, $this->utf8($c['tipo_moneda']), 0, 1, 'L');

        $this->pdf->SetFont('Arial', 'B', 9);
        $tipoDocCli = $c['cliente_tipo_doc'] === '6' ? 'RUC:' : 'DNI:';
        $this->pdf->Cell(35, 5, $tipoDocCli, 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(80, 5, $this->utf8($c['cliente_num_doc']), 0, 0, 'L');
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(35, 5, 'Forma de pago:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(0, 5, $this->utf8(strtoupper($c['metodo_pago'] ?? '')), 0, 1, 'L');

        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(35, 5, 'Cliente:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(0, 5, $this->utf8($c['cliente_razon']), 0, 1, 'L');

        if (!empty($c['cliente_direccion'])) {
            $this->pdf->SetFont('Arial', 'B', 9);
            $this->pdf->Cell(35, 5, 'Dirección:', 0, 0, 'L');
            $this->pdf->SetFont('Arial', '', 9);
            $this->pdf->Cell(0, 5, $this->utf8($c['cliente_direccion']), 0, 1, 'L');
        }

        // Si es NC/ND, mostrar referencia y motivo
        if (!empty($c['ref_serie'])) {
            $this->pdf->Ln(2);
            $this->pdf->SetFont('Arial', 'B', 9);
            $this->pdf->Cell(35, 5, 'Doc. modificado:', 0, 0, 'L');
            $this->pdf->SetFont('Arial', '', 9);
            $this->pdf->Cell(0, 5, $this->utf8($c['ref_serie'] . '-' . ltrim($c['ref_numero'], '0')), 0, 1, 'L');

            $this->pdf->SetFont('Arial', 'B', 9);
            $this->pdf->Cell(35, 5, 'Motivo:', 0, 0, 'L');
            $this->pdf->SetFont('Arial', '', 9);
            $this->pdf->Cell(0, 5, $this->utf8($c['motivo_descripcion'] ?? ''), 0, 1, 'L');
        }
    }

    private function tablaItems()
    {
        $c = $this->comp;
        $this->pdf->Ln(3);

        // Encabezado
        $this->pdf->SetFillColor(26, 31, 54);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(15, 7, 'CANT.', 1, 0, 'C', true);
        $this->pdf->Cell(20, 7, 'UNIDAD', 1, 0, 'C', true);
        $this->pdf->Cell(80, 7, 'DESCRIPCIÓN', 1, 0, 'C', true);
        $this->pdf->Cell(25, 7, 'P. UNIT.', 1, 0, 'C', true);
        $this->pdf->Cell(10, 7, 'AFEC.', 1, 0, 'C', true);
        $this->pdf->Cell(36, 7, 'IMPORTE', 1, 1, 'C', true);

        // Detalle
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9);
        $fill = false;
        foreach ($c['items'] as $i) {
            $this->pdf->SetFillColor(245, 247, 251);
            $this->pdf->Cell(15, 6, number_format((float)$i['cantidad'], 2), 1, 0, 'C', $fill);
            $this->pdf->Cell(20, 6, $this->utf8($i['unidad_medida']), 1, 0, 'C', $fill);
            $this->pdf->Cell(80, 6, $this->utf8(substr($i['descripcion'], 0, 60)), 1, 0, 'L', $fill);
            $this->pdf->Cell(25, 6, $this->money($i['precio_unitario']), 1, 0, 'R', $fill);
            $afect = $i['codigo_afectacion'] ?? '10';
            $afectLabel = $afect === '10' ? 'G' : ($afect === '20' ? 'E' : ($afect === '30' ? 'I' : '?'));
            $this->pdf->Cell(10, 6, $afectLabel, 1, 0, 'C', $fill);
            $this->pdf->Cell(36, 6, $this->money($i['total_item']), 1, 1, 'R', $fill);
            $fill = !$fill;
        }
    }

    private function totales()
    {
        $c = $this->comp;
        $this->pdf->Ln(3);

        $x = 130;
        $w1 = 30; $w2 = 36;

        $rows = [];
        if ((float)$c['subtotal_gravado'] > 0) {
            $rows[] = ['Op. Gravadas:', $this->money($c['subtotal_gravado'])];
        }
        if ((float)$c['subtotal_exonerado'] > 0) {
            $rows[] = ['Op. Exoneradas:', $this->money($c['subtotal_exonerado'])];
        }
        if ((float)$c['subtotal_inafecto'] > 0) {
            $rows[] = ['Op. Inafectas:', $this->money($c['subtotal_inafecto'])];
        }
        if ((float)$c['igv'] > 0) {
            $rows[] = ['IGV (' . number_format(((float)$c['tasa_igv']) * 100, 0) . '%):', $this->money($c['igv'])];
        }

        foreach ($rows as $r) {
            $this->pdf->SetX($x);
            $this->pdf->SetFont('Arial', '', 9);
            $this->pdf->Cell($w1, 5, $this->utf8($r[0]), 0, 0, 'R');
            $this->pdf->Cell($w2, 5, $r[1], 0, 1, 'R');
        }

        // Total final
        $this->pdf->SetX($x);
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetFillColor(91, 61, 245);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell($w1, 7, $this->utf8('TOTAL ' . $c['tipo_moneda'] . ':'), 0, 0, 'R', true);
        $this->pdf->Cell($w2, 7, $this->money($c['total']), 0, 1, 'R', true);
        $this->pdf->SetTextColor(0, 0, 0);
    }

    private function codigoLetras()
    {
        $c = $this->comp;
        if (empty($c['total_letras'])) return;
        $this->pdf->Ln(4);
        $this->pdf->SetFont('Arial', 'I', 8);
        $this->pdf->MultiCell(0, 4, $this->utf8($c['total_letras']), 0, 'L');
    }

    private function qrCode()
    {
        $c = $this->comp;

        // Construir cadena QR segun SUNAT
        $cadena = implode('|', [
            $c['empresa_ruc'],
            $c['tipo_documento'],
            $c['serie'],
            ltrim($c['numero'], '0') ?: '0',
            number_format((float)$c['igv'], 2, '.', ''),
            number_format((float)$c['total'], 2, '.', ''),
            date('d/m/Y', strtotime($c['fecha_emision'])),
            $c['cliente_tipo_doc'],
            $c['cliente_num_doc'],
            $c['xml_hash'] ?? '',
        ]);

        $tmpDir = sys_get_temp_dir();
        $this->tmpQr = $tmpDir . DIRECTORY_SEPARATOR . 'yapez_qr_' . uniqid() . '.png';
        QRcode::png($cadena, $this->tmpQr, QR_ECLEVEL_L, 4, 1);

        $this->pdf->Ln(2);
        $y = $this->pdf->GetY();
        $this->pdf->Image($this->tmpQr, 12, $y, 32, 32);

        // Codigo del CDR
        $this->pdf->SetXY(48, $y);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(0, 4, $this->utf8('Representación impresa de la ' . $this->tipoLabel($c['tipo_documento'])), 0, 2, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(0, 4, $this->utf8('Verifique el comprobante en SUNAT con el código:'), 0, 2, 'L');
        $this->pdf->SetFont('Courier', '', 8);
        $this->pdf->MultiCell(0, 4, $this->utf8($c['xml_hash'] ?? '(pendiente)'), 0, 'L');

        $this->pdf->SetY($y + 34);
    }

    private function footer()
    {
        $c = $this->comp;
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetTextColor(110, 110, 110);
        $this->pdf->Cell(0, 4, $this->utf8('Documento generado el ' . date('d/m/Y H:i:s')), 0, 1, 'C');
        if (!empty($c['cdr_descripcion'])) {
            $this->pdf->Cell(0, 4, $this->utf8('Estado SUNAT: ' . $c['cdr_descripcion']), 0, 1, 'C');
        }
    }

    // ========= Utilidades =========

    private function tipoLabel($cod)
    {
        $m = ['01'=>'Factura','03'=>'Boleta de Venta','07'=>'Nota de Crédito','08'=>'Nota de Débito'];
        return $m[$cod] ?? 'Comprobante';
    }

    private function money($n)
    {
        return $this->simbolo . ' ' . number_format((float)$n, 2);
    }

    private function utf8($s)
    {
        // FPDF requiere ISO-8859-1
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s);
    }
}
