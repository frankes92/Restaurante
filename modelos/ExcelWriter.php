<?php
/**
 * ExcelWriter — generador de archivos XLSX (Office Open XML) sin dependencias.
 *
 * Soporta:
 *   - Multiples hojas
 *   - Estilos: bold, italic, color, bg, size, align, border, formato (money/int/date)
 *   - Anchos de columna y altos de fila
 *   - Celdas combinadas
 *
 * Uso minimo:
 *   $xls = new ExcelWriter();
 *   $xls->addSheet('Hoja1');
 *   $xls->setCell('A1', 'Hola', ['bold' => true]);
 *   $xls->download('archivo.xlsx');
 */
class ExcelWriter
{
    private $sheets = [];
    private $currentSheet = -1;
    private $sharedStrings = [];
    private $sharedStringsIndex = [];
    private $stylesUsed = [];      // map style key → numFmt index in xf
    private $styleKeyToXf = [];    // map estilo serializado → indice de xf
    private $fonts = [];           // [{size, bold, italic, color}]
    private $fills = [];           // [{color}]
    private $borders = [];         // [bool]
    private $numberFormats = [];   // [{code}]

    // numero base de formatos predefinidos en XLSX
    private static $builtinFormats = [
        'money'  => '"S/" #,##0.00',
        'money0' => '"S/" #,##0',
        'int'    => '#,##0',
        'percent'=> '0.0%',
        'date'   => 'yyyy-mm-dd hh:mm',
    ];

    public function __construct()
    {
        // Registros base
        $this->fonts[] = ['size' => 11, 'bold' => false, 'italic' => false, 'color' => '000000'];
        $this->fills[] = ['type' => 'none'];
        $this->fills[] = ['type' => 'gray125']; // requerido por Excel
        $this->borders[] = false;
        // xf 0 = default (sin estilo)
        $this->styleKeyToXf['__default__'] = 0;
    }

    public function addSheet($name)
    {
        $this->sheets[] = [
            'name'    => $this->safeSheetName($name),
            'rows'    => [],   // [row => [col => ['v' => value, 's' => xf_index]]]
            'colWidths' => [],
            'rowHeights' => [],
            'merges'  => [],
            'freeze'  => null,     // ['row' => int, 'col' => int]  filas/cols congeladas
            'autoFilter' => null,  // 'A3:N3' rango de encabezado
            'hideGrid' => false,
        ];
        $this->currentSheet = count($this->sheets) - 1;
    }

    public function setCell($cellRef, $value, $style = [])
    {
        if ($this->currentSheet < 0) $this->addSheet('Hoja1');
        list($col, $row) = $this->parseRef($cellRef);
        $xfIndex = empty($style) ? 0 : $this->getStyleIndex($style);
        $format  = $style['format'] ?? '';
        $isNumeric = is_numeric($value) && in_array($format, ['money','int'], true);

        $this->sheets[$this->currentSheet]['rows'][$row][$col] = [
            'v'   => $value,
            's'   => $xfIndex,
            'num' => $isNumeric,
        ];
    }

    public function setColWidth($col, $width)
    {
        if ($this->currentSheet < 0) return;
        $colIdx = $this->colLetterToIndex($col);
        $this->sheets[$this->currentSheet]['colWidths'][$colIdx] = $width;
    }

    public function setRowHeight($row, $height)
    {
        if ($this->currentSheet < 0) return;
        $this->sheets[$this->currentSheet]['rowHeights'][$row] = $height;
    }

    public function mergeCells($from, $to)
    {
        if ($this->currentSheet < 0) return;
        $this->sheets[$this->currentSheet]['merges'][] = $from . ':' . $to;
    }

    /**
     * Congela filas/columnas. $rows = nº de filas superiores fijas,
     * $cols = nº de columnas izquierdas fijas.
     */
    public function freezePanes($rows, $cols = 0)
    {
        if ($this->currentSheet < 0) return;
        $this->sheets[$this->currentSheet]['freeze'] = ['row' => (int)$rows, 'col' => (int)$cols];
    }

    /** Activa el autofiltro sobre un rango, ej. 'A3:N3'. */
    public function setAutoFilter($range)
    {
        if ($this->currentSheet < 0) return;
        $this->sheets[$this->currentSheet]['autoFilter'] = $range;
    }

    /** Oculta las líneas de cuadrícula de la hoja (look más limpio). */
    public function hideGridlines($hide = true)
    {
        if ($this->currentSheet < 0) return;
        $this->sheets[$this->currentSheet]['hideGrid'] = (bool)$hide;
    }

    public function download($filename)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $this->save($tmp);
        if (!headers_sent()) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
            header('Content-Length: ' . filesize($tmp));
            header('Cache-Control: max-age=0');
        }
        readfile($tmp);
        @unlink($tmp);
    }

    public function save($path)
    {
        // IMPORTANTE: construir PRIMERO las hojas para poblar la tabla de
        // sharedStrings (addSharedString se llama dentro de buildSheet).
        // Si se generara sharedStrings.xml antes, quedaria vacio y Excel
        // descartaria todos los textos al "reparar" el archivo.
        $sheetsXml = [];
        foreach ($this->sheets as $idx => $sheet) {
            $sheetsXml[$idx] = $this->buildSheet($idx);
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('No se pudo crear el archivo XLSX');
        }
        $zip->addFromString('[Content_Types].xml', $this->buildContentTypes());
        $zip->addFromString('_rels/.rels', $this->buildRootRels());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->buildWorkbookRels());
        $zip->addFromString('xl/workbook.xml', $this->buildWorkbook());
        $zip->addFromString('xl/styles.xml', $this->buildStyles());
        $zip->addFromString('xl/sharedStrings.xml', $this->buildSharedStrings());
        foreach ($sheetsXml as $idx => $xml) {
            $zip->addFromString('xl/worksheets/sheet' . ($idx + 1) . '.xml', $xml);
        }
        $zip->close();
    }

    // ===================== Helpers de estilo =====================

    private function getStyleIndex(array $style)
    {
        $key = json_encode($style);
        if (isset($this->styleKeyToXf[$key])) return $this->styleKeyToXf[$key];

        // Font
        $fontKey = ($style['size'] ?? 11) . '|' . (!empty($style['bold']) ? '1' : '0')
                 . '|' . (!empty($style['italic']) ? '1' : '0') . '|' . ($style['color'] ?? '000000');
        $fontIdx = -1;
        foreach ($this->fonts as $i => $f) {
            $fk = $f['size'] . '|' . ($f['bold'] ? '1' : '0') . '|' . ($f['italic'] ? '1' : '0') . '|' . $f['color'];
            if ($fk === $fontKey) { $fontIdx = $i; break; }
        }
        if ($fontIdx === -1) {
            $this->fonts[] = [
                'size'   => $style['size']   ?? 11,
                'bold'   => !empty($style['bold']),
                'italic' => !empty($style['italic']),
                'color'  => $style['color']  ?? '000000',
            ];
            $fontIdx = count($this->fonts) - 1;
        }

        // Fill
        $fillIdx = 0;
        if (!empty($style['bg'])) {
            $fillKey = strtoupper($style['bg']);
            $found = -1;
            foreach ($this->fills as $i => $f) {
                if (($f['type'] ?? '') === 'solid' && ($f['color'] ?? '') === $fillKey) { $found = $i; break; }
            }
            if ($found === -1) {
                $this->fills[] = ['type' => 'solid', 'color' => $fillKey];
                $found = count($this->fills) - 1;
            }
            $fillIdx = $found;
        }

        // Border
        $borderIdx = 0;
        if (!empty($style['border'])) {
            $borderIdx = 1;
            if (!isset($this->borders[1])) $this->borders[1] = true;
        }

        // Number format
        $numFmtId = 0;
        if (!empty($style['format'])) {
            $code = self::$builtinFormats[$style['format']] ?? null;
            if ($code) {
                $found = -1;
                foreach ($this->numberFormats as $i => $f) {
                    if ($f['code'] === $code) { $found = $i; break; }
                }
                if ($found === -1) {
                    $this->numberFormats[] = ['code' => $code];
                    $found = count($this->numberFormats) - 1;
                }
                $numFmtId = 164 + $found; // custom IDs comienzan en 164
            }
        }

        // Alignment
        $align = $style['align'] ?? '';

        // Index del xf — guardo definicion para construir despues
        $this->stylesUsed[] = [
            'fontIdx'   => $fontIdx,
            'fillIdx'   => $fillIdx,
            'borderIdx' => $borderIdx,
            'numFmtId'  => $numFmtId,
            'align'     => $align,
        ];
        $xfIdx = count($this->stylesUsed); // 0 reservado para default
        $this->styleKeyToXf[$key] = $xfIdx;
        return $xfIdx;
    }

    // ===================== Builders =====================

    private function buildContentTypes()
    {
        $sheets = '';
        foreach ($this->sheets as $i => $s) {
            $sheets .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
. $sheets . '</Types>';
    }

    private function buildRootRels()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
    }

    private function buildWorkbookRels()
    {
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
        $idCounter = 3;
        foreach ($this->sheets as $i => $s) {
            $rels .= '<Relationship Id="rId' . $idCounter . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
            $idCounter++;
        }
        $rels .= '</Relationships>';
        return $rels;
    }

    private function buildWorkbook()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>';
        foreach ($this->sheets as $i => $s) {
            $xml .= '<sheet name="' . $this->esc($s['name']) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 3) . '"/>';
        }
        $xml .= '</sheets>';

        // definedNames: cada hoja con autoFilter requiere su _xlnm._FilterDatabase
        // (scoped a la hoja con localSheetId) o Excel "repara" el archivo.
        $defs = '';
        foreach ($this->sheets as $i => $s) {
            if (empty($s['autoFilter'])) continue;
            $ref = $this->absRange($s['name'], $s['autoFilter']);
            $defs .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $i . '" hidden="1">' . $ref . '</definedName>';
        }
        if ($defs !== '') $xml .= '<definedNames>' . $defs . '</definedNames>';

        $xml .= '</workbook>';
        return $xml;
    }

    /**
     * Convierte 'A4:M55' + nombre de hoja en una referencia absoluta para
     * defined names: 'NombreHoja'!$A$4:$M$55
     */
    private function absRange($sheetName, $range)
    {
        $parts = explode(':', $range);
        $abs = [];
        foreach ($parts as $p) {
            if (preg_match('/^([A-Z]+)(\d+)$/i', trim($p), $m)) {
                $abs[] = '$' . strtoupper($m[1]) . '$' . $m[2];
            } else {
                $abs[] = $p;
            }
        }
        // Escapar comillas simples del nombre duplicandolas (sintaxis de referencia)
        $sn = str_replace("'", "''", $sheetName);
        return "'" . $this->esc($sn) . "'!" . $this->esc(implode(':', $abs));
    }

    private function buildStyles()
    {
        // Number formats custom
        $numFmts = '';
        foreach ($this->numberFormats as $i => $f) {
            $numFmts .= '<numFmt numFmtId="' . (164 + $i) . '" formatCode="' . $this->esc($f['code']) . '"/>';
        }
        if ($numFmts) $numFmts = '<numFmts count="' . count($this->numberFormats) . '">' . $numFmts . '</numFmts>';

        // Fonts
        $fontsXml = '';
        foreach ($this->fonts as $f) {
            $b = $f['bold']   ? '<b/>' : '';
            $i = $f['italic'] ? '<i/>' : '';
            $fontsXml .= '<font>' . $b . $i .
                '<sz val="' . (float)$f['size'] . '"/>' .
                '<color rgb="FF' . $f['color'] . '"/>' .
                '<name val="Calibri"/>' .
                '<family val="2"/>' .
            '</font>';
        }
        $fonts = '<fonts count="' . count($this->fonts) . '">' . $fontsXml . '</fonts>';

        // Fills
        $fillsXml = '';
        foreach ($this->fills as $f) {
            if (($f['type'] ?? '') === 'none')      $fillsXml .= '<fill><patternFill patternType="none"/></fill>';
            elseif (($f['type'] ?? '') === 'gray125') $fillsXml .= '<fill><patternFill patternType="gray125"/></fill>';
            else $fillsXml .= '<fill><patternFill patternType="solid"><fgColor rgb="FF' . $f['color'] . '"/><bgColor indexed="64"/></patternFill></fill>';
        }
        $fills = '<fills count="' . count($this->fills) . '">' . $fillsXml . '</fills>';

        // Borders
        $bordersXml = '<border><left/><right/><top/><bottom/><diagonal/></border>';
        if (count($this->borders) > 1) {
            $bordersXml .= '<border>'
                . '<left style="thin"><color rgb="FFD0D0D0"/></left>'
                . '<right style="thin"><color rgb="FFD0D0D0"/></right>'
                . '<top style="thin"><color rgb="FFD0D0D0"/></top>'
                . '<bottom style="thin"><color rgb="FFD0D0D0"/></bottom>'
                . '<diagonal/></border>';
        }
        $borders = '<borders count="' . count($this->borders) . '">' . $bordersXml . '</borders>';

        // CellXfs
        $cellXfs = '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'; // default
        foreach ($this->stylesUsed as $st) {
            $applyAlign = !empty($st['align']) ? ' applyAlignment="1"' : '';
            $alignTag = '';
            if (!empty($st['align'])) {
                $alignTag = '<alignment horizontal="' . $st['align'] . '" vertical="center" wrapText="1"/>';
            } else {
                $alignTag = '<alignment vertical="center" wrapText="1"/>';
            }
            $applyAlign = ' applyAlignment="1"';
            $applyFont   = ' applyFont="1"';
            $applyFill   = $st['fillIdx']   > 0 ? ' applyFill="1"'   : '';
            $applyBorder = $st['borderIdx'] > 0 ? ' applyBorder="1"' : '';
            $applyNumFmt = $st['numFmtId']  > 0 ? ' applyNumberFormat="1"' : '';
            $cellXfs .= '<xf numFmtId="' . $st['numFmtId'] . '"'
                . ' fontId="'   . $st['fontIdx']   . '"'
                . ' fillId="'   . $st['fillIdx']   . '"'
                . ' borderId="' . $st['borderIdx'] . '"'
                . ' xfId="0"'
                . $applyFont . $applyFill . $applyBorder . $applyNumFmt . $applyAlign . '>'
                . $alignTag
                . '</xf>';
        }
        $cellXfsCount = 1 + count($this->stylesUsed);
        $cellXfsXml = '<cellXfs count="' . $cellXfsCount . '">' . $cellXfs . '</cellXfs>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
. $numFmts . $fonts . $fills . $borders . '
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
. $cellXfsXml . '
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';
    }

    private function buildSharedStrings()
    {
        $count = count($this->sharedStrings);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $count . '" uniqueCount="' . $count . '">';
        foreach ($this->sharedStrings as $s) {
            $xml .= '<si><t xml:space="preserve">' . $this->esc($s) . '</t></si>';
        }
        $xml .= '</sst>';
        return $xml;
    }

    private function buildSheet($sheetIdx)
    {
        $sheet = $this->sheets[$sheetIdx];

        // Encontrar dimension max
        $maxRow = 1; $maxCol = 1;
        foreach ($sheet['rows'] as $row => $cols) {
            if ($row > $maxRow) $maxRow = $row;
            foreach ($cols as $col => $cell) if ($col > $maxCol) $maxCol = $col;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<dimension ref="A1:' . $this->colIndexToLetter($maxCol) . $maxRow . '"/>';

        // Vista de hoja: cuadrícula opcional + panes congelados
        $gridAttr = !empty($sheet['hideGrid']) ? ' showGridLines="0"' : '';
        $paneXml = '';
        if (!empty($sheet['freeze']) && (($sheet['freeze']['row'] ?? 0) > 0 || ($sheet['freeze']['col'] ?? 0) > 0)) {
            $fr = (int)$sheet['freeze']['row'];
            $fc = (int)$sheet['freeze']['col'];
            $topLeft = $this->colIndexToLetter($fc + 1) . ($fr + 1);
            $xSplit = $fc > 0 ? ' xSplit="' . $fc . '"' : '';
            $ySplit = $fr > 0 ? ' ySplit="' . $fr . '"' : '';
            $active = ($fc > 0 && $fr > 0) ? 'bottomRight' : ($fc > 0 ? 'topRight' : 'bottomLeft');
            $paneXml = '<pane' . $xSplit . $ySplit . ' topLeftCell="' . $topLeft . '" activePane="' . $active . '" state="frozen"/>'
                     . '<selection pane="' . $active . '" activeCell="' . $topLeft . '" sqref="' . $topLeft . '"/>';
        }
        $xml .= '<sheetViews><sheetView' . $gridAttr . ' workbookViewId="0">' . $paneXml . '</sheetView></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="15"/>';

        // Cols
        if (!empty($sheet['colWidths'])) {
            $xml .= '<cols>';
            foreach ($sheet['colWidths'] as $colIdx => $w) {
                $xml .= '<col min="' . $colIdx . '" max="' . $colIdx . '" width="' . (float)$w . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        ksort($sheet['rows']);
        foreach ($sheet['rows'] as $row => $cols) {
            ksort($cols);
            $rowAttrs = '';
            if (isset($sheet['rowHeights'][$row])) {
                $rowAttrs = ' ht="' . (float)$sheet['rowHeights'][$row] . '" customHeight="1"';
            }
            $xml .= '<row r="' . $row . '"' . $rowAttrs . '>';
            foreach ($cols as $colIdx => $cell) {
                $cellRef = $this->colIndexToLetter($colIdx) . $row;
                $sAttr = $cell['s'] > 0 ? ' s="' . $cell['s'] . '"' : '';
                if (!empty($cell['num'])) {
                    $xml .= '<c r="' . $cellRef . '"' . $sAttr . '><v>' . $cell['v'] . '</v></c>';
                } else {
                    $strIdx = $this->addSharedString((string)$cell['v']);
                    $xml .= '<c r="' . $cellRef . '"' . $sAttr . ' t="s"><v>' . $strIdx . '</v></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        // autoFilter va ANTES de mergeCells en el esquema OOXML
        if (!empty($sheet['autoFilter'])) {
            $xml .= '<autoFilter ref="' . $this->esc($sheet['autoFilter']) . '"/>';
        }

        if (!empty($sheet['merges'])) {
            $xml .= '<mergeCells count="' . count($sheet['merges']) . '">';
            foreach ($sheet['merges'] as $m) $xml .= '<mergeCell ref="' . $m . '"/>';
            $xml .= '</mergeCells>';
        }

        $xml .= '</worksheet>';
        return $xml;
    }

    private function addSharedString($value)
    {
        if (isset($this->sharedStringsIndex[$value])) return $this->sharedStringsIndex[$value];
        $idx = count($this->sharedStrings);
        $this->sharedStrings[] = $value;
        $this->sharedStringsIndex[$value] = $idx;
        return $idx;
    }

    private function parseRef($ref)
    {
        preg_match('/^([A-Z]+)(\d+)$/', strtoupper($ref), $m);
        return [$this->colLetterToIndex($m[1]), (int)$m[2]];
    }

    private function colLetterToIndex($letters)
    {
        $letters = strtoupper($letters);
        $n = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return $n;
    }

    private function colIndexToLetter($n)
    {
        $s = '';
        while ($n > 0) {
            $r = ($n - 1) % 26;
            $s = chr(65 + $r) . $s;
            $n = (int)(($n - 1) / 26);
        }
        return $s;
    }

    private function safeSheetName($name)
    {
        $name = preg_replace('/[\\/\\\\\\?\\*\\[\\]:]/u', '_', (string)$name);
        if (mb_strlen($name) > 31) $name = mb_substr($name, 0, 31);
        return $name === '' ? 'Hoja' : $name;
    }

    private function esc($s)
    {
        return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
