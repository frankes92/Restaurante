<?php
/**
 * ExcelReader — lee archivos .xlsx (Office Open XML) y .csv sin dependencias.
 *
 * Un .xlsx es un ZIP con XML adentro; usamos ZipArchive + SimpleXML.
 * Devuelve un arreglo de filas, cada fila un arreglo de celdas (strings).
 *
 * Uso:
 *   $rows = ExcelReader::read('/ruta/archivo.xlsx');
 *   // $rows[0] = primera fila (encabezados)
 */
class ExcelReader
{
    /** Lee xlsx o csv segun extension/contenido. Devuelve array de filas. */
    public static function read($path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'csv' || $ext === 'txt') {
            return self::readCsv($path);
        }
        return self::readXlsx($path);
    }

    // ---------------- CSV ----------------
    private static function readCsv($path)
    {
        $rows = [];
        if (($h = fopen($path, 'r')) !== false) {
            // Detectar separador: coma o punto y coma
            $first = fgets($h);
            rewind($h);
            $sep = (substr_count($first, ';') > substr_count($first, ',')) ? ';' : ',';
            while (($data = fgetcsv($h, 0, $sep)) !== false) {
                // Normalizar codificacion a UTF-8
                $data = array_map(function ($c) {
                    if ($c === null) return '';
                    if (!mb_check_encoding($c, 'UTF-8')) $c = mb_convert_encoding($c, 'UTF-8', 'Windows-1252');
                    return trim($c);
                }, $data);
                $rows[] = $data;
            }
            fclose($h);
        }
        return $rows;
    }

    // ---------------- XLSX ----------------
    private static function readXlsx($path)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new Exception('No se pudo abrir el archivo Excel');
        }

        // 1) sharedStrings (textos)
        $shared = [];
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if ($ss !== false) {
            $xml = @simplexml_load_string($ss);
            if ($xml) {
                foreach ($xml->si as $si) {
                    $shared[] = self::extraerTextoSi($si);
                }
            }
        }

        // 2) Hallar la primera hoja real via workbook + rels
        $sheetPath = self::primeraHoja($zip);
        $sheetXmlStr = $zip->getFromName($sheetPath);
        $zip->close();
        if ($sheetXmlStr === false) throw new Exception('No se encontró la hoja de cálculo');

        $sheet = @simplexml_load_string($sheetXmlStr);
        if (!$sheet) throw new Exception('Hoja de cálculo ilegible');

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $fila = [];
            $colEsperada = 0;
            foreach ($row->c as $c) {
                // Posicion de columna (A=0, B=1...) por el atributo r (ej "C5")
                $ref = (string)$c['r'];
                $colIdx = self::colIndex($ref);
                // Rellenar celdas vacias intermedias
                while ($colEsperada < $colIdx) { $fila[] = ''; $colEsperada++; }

                $val = '';
                $t = (string)$c['t'];
                if ($t === 's') {
                    $idx = (int)$c->v;
                    $val = $shared[$idx] ?? '';
                } elseif ($t === 'inlineStr') {
                    $val = self::extraerTextoSi($c->is);
                } else {
                    $val = (string)$c->v;
                }
                $fila[] = trim($val);
                $colEsperada++;
            }
            $rows[] = $fila;
        }
        return $rows;
    }

    /** Texto de un nodo <si> o <is> (puede tener varios <r><t>). */
    private static function extraerTextoSi($si)
    {
        if ($si === null) return '';
        if (isset($si->t)) return (string)$si->t;
        $txt = '';
        if (isset($si->r)) {
            foreach ($si->r as $r) $txt .= (string)$r->t;
        }
        return $txt;
    }

    /** "C5" → 2 (A=0). */
    private static function colIndex($ref)
    {
        if (!preg_match('/^([A-Z]+)/', $ref, $m)) return 0;
        $letters = $m[1];
        $n = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return $n - 1;
    }

    /** Ruta XML de la primera hoja del libro. */
    private static function primeraHoja($zip)
    {
        // Por defecto sheet1
        $default = 'xl/worksheets/sheet1.xml';
        $wb = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wb === false || $rels === false) return $default;

        $wbXml = @simplexml_load_string($wb);
        if (!$wbXml) return $default;
        $wbXml->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheets = $wbXml->sheets->sheet ?? null;
        if (!$sheets) return $default;
        $first = $sheets[0];
        $rid = '';
        foreach ($first->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships') as $k => $v) {
            if ($k === 'id') { $rid = (string)$v; break; }
        }
        if ($rid === '') return $default;

        $relsXml = @simplexml_load_string($rels);
        if (!$relsXml) return $default;
        foreach ($relsXml->Relationship as $rel) {
            if ((string)$rel['Id'] === $rid) {
                $target = (string)$rel['Target'];
                $target = ltrim($target, '/');
                if (strpos($target, 'xl/') !== 0) $target = 'xl/' . $target;
                return $target;
            }
        }
        return $default;
    }
}
