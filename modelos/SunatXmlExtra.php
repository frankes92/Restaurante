<?php
/**
 * Generadores XML adicionales para SUNAT:
 *   - Nota de Credito (UBL CreditNote 2.1)
 *   - Nota de Debito  (UBL DebitNote 2.1)
 *   - Resumen Diario de Boletas (UBL SummaryDocuments)
 *   - Comunicacion de Baja      (UBL VoidedDocuments)
 *
 * Catalogos SUNAT:
 *   - Cat 9 (motivos NC): 01=Anulacion operacion, 02=Anulacion error RUC, 03=Correccion descripcion,
 *                         06=Devolucion total, 07=Devolucion parcial, 13=Ajuste/descuento
 *   - Cat 10 (motivos ND): 01=Intereses por mora, 02=Aumento valor, 03=Penalidades
 *   - Cat 19 (estado item resumen baja): 1=adicionar, 2=modificar, 3=anular
 */
class SunatXmlExtra
{
    // =================================================================
    // NOTA DE CREDITO (tipo 07) - estructura UBL CreditNote
    // =================================================================
    public function buildNotaCredito(array $comp)
    {
        $rucEmisor       = $comp['empresa_ruc'];
        $razonEmisor     = $this->esc($comp['empresa_razon']);
        $nombreComEmisor = $this->esc($comp['empresa_nombre_comercial'] ?? $comp['empresa_razon']);
        $serie  = $comp['serie'];
        $numero = ltrim($comp['numero'], '0') ?: '0';
        $idDoc  = $serie . '-' . $numero;

        $fecha = date('Y-m-d', strtotime($comp['fecha_emision']));
        $hora  = date('H:i:s', strtotime($comp['fecha_emision']));

        $cliTipoDoc = $comp['cliente_tipo_doc'];
        $cliNumDoc  = $comp['cliente_num_doc'];
        $cliRazon   = $this->esc($comp['cliente_razon']);
        $cliDir     = $this->esc($comp['cliente_direccion'] ?? '');

        $moneda = $comp['tipo_moneda'];
        $tasaIgv = (float)($comp['tasa_igv'] ?? 0.18);

        // Referencia al comprobante anulado
        $refTipoDoc = $comp['ref_tipo_documento']; // 01 o 03
        $refNumeroCompleto = $comp['ref_serie'] . '-' . ltrim($comp['ref_numero'], '0');
        $motivoCod = $comp['motivo_codigo'] ?? '01'; // 01 = Anulacion de la operacion
        $motivoDes = $this->esc($comp['motivo_descripcion'] ?? 'Anulacion de la operacion');

        // Direccion emisor
        $empUbigeo       = $this->esc($comp['empresa_ubigeo']       ?? '150101');
        $empDepartamento = $this->esc($comp['empresa_departamento'] ?? 'LIMA');
        $empProvincia    = $this->esc($comp['empresa_provincia']    ?? 'LIMA');
        $empDistrito     = $this->esc($comp['empresa_distrito']     ?? 'LIMA');
        $empPais         = $this->esc($comp['empresa_pais']         ?? 'PE');
        $empDireccion    = $this->esc($comp['empresa_direccion']    ?? '');

        $igvTotal     = (float)$comp['igv'];
        $totalVenta   = (float)$comp['total'];
        $totalLetras  = $this->esc($comp['total_letras'] ?? '');

        // Sumar por afectacion a partir de las lineas (gravado/exonerado/inafecto)
        $totGravado = 0; $totExonerado = 0; $totInafecto = 0;
        foreach ($comp['items'] as $i) {
            $a = (int)($i['codigo_afectacion'] ?? 10);
            if ($a === 20)      $totExonerado += (float)$i['valor_venta'];
            elseif ($a === 30)  $totInafecto  += (float)$i['valor_venta'];
            else                $totGravado   += (float)$i['valor_venta'];
        }
        $lineExtTotal  = $totGravado + $totExonerado + $totInafecto;

        $totalGravadoF = number_format($totGravado, 2, '.', '');
        $totExoneradoF = number_format($totExonerado, 2, '.', '');
        $totInafectoF  = number_format($totInafecto, 2, '.', '');
        $igvTotalF     = number_format($igvTotal, 2, '.', '');
        $totalVentaF   = number_format($totalVenta, 2, '.', '');
        $lineExtTotalF = number_format($lineExtTotal, 2, '.', '');

        // Lineas (CreditNoteLine en lugar de InvoiceLine)
        $lineas = '';
        $idx = 1;
        foreach ($comp['items'] as $i) {
            $cnt = number_format((float)$i['cantidad'], 2, '.', '');
            $um  = $i['unidad_medida'];
            $pu  = number_format((float)$i['precio_unitario'], 2, '.', '');
            $pci = number_format((float)$i['precio_con_igv'], 2, '.', '');
            $vv  = number_format((float)$i['valor_venta'], 2, '.', '');
            $iv  = number_format((float)$i['igv_item'], 2, '.', '');
            $des = $this->esc($i['descripcion']);
            $cod = $this->esc($i['codigo'] ?? '');
            $afect = (int)($i['codigo_afectacion'] ?? 10);
            $afectStr = str_pad($afect, 2, '0', STR_PAD_LEFT);

            if ($afect === 20) { $tsId='9997'; $tsName='EXO'; $ttCode='VAT'; $tasaPct='0.00'; }
            elseif ($afect === 30) { $tsId='9998'; $tsName='INA'; $ttCode='FRE'; $tasaPct='0.00'; }
            else { $tsId='1000'; $tsName='IGV'; $ttCode='VAT'; $tasaPct=number_format($tasaIgv*100,2,'.',''); }

            $lineas .= <<<XML
    <cac:CreditNoteLine>
        <cbc:ID>$idx</cbc:ID>
        <cbc:CreditedQuantity unitCode="$um">$cnt</cbc:CreditedQuantity>
        <cbc:LineExtensionAmount currencyID="$moneda">$vv</cbc:LineExtensionAmount>
        <cac:PricingReference>
            <cac:AlternativeConditionPrice>
                <cbc:PriceAmount currencyID="$moneda">$pci</cbc:PriceAmount>
                <cbc:PriceTypeCode>01</cbc:PriceTypeCode>
            </cac:AlternativeConditionPrice>
        </cac:PricingReference>
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID="$moneda">$iv</cbc:TaxAmount>
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID="$moneda">$vv</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID="$moneda">$iv</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cbc:Percent>$tasaPct</cbc:Percent>
                    <cbc:TaxExemptionReasonCode>$afectStr</cbc:TaxExemptionReasonCode>
                    <cac:TaxScheme>
                        <cbc:ID>$tsId</cbc:ID>
                        <cbc:Name>$tsName</cbc:Name>
                        <cbc:TaxTypeCode>$ttCode</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        </cac:TaxTotal>
        <cac:Item>
            <cbc:Description><![CDATA[$des]]></cbc:Description>
            <cac:SellersItemIdentification>
                <cbc:ID>$cod</cbc:ID>
            </cac:SellersItemIdentification>
        </cac:Item>
        <cac:Price>
            <cbc:PriceAmount currencyID="$moneda">$pu</cbc:PriceAmount>
        </cac:Price>
    </cac:CreditNoteLine>

XML;
            $idx++;
        }

        // TaxSubtotals dinamicos: solo se incluye el de cada afectacion con monto
        $ncTaxSubtotals = '';
        if ($totGravado > 0) {
            $ncTaxSubtotals .= "        <cac:TaxSubtotal>\n"
                . "            <cbc:TaxableAmount currencyID=\"$moneda\">$totalGravadoF</cbc:TaxableAmount>\n"
                . "            <cbc:TaxAmount currencyID=\"$moneda\">$igvTotalF</cbc:TaxAmount>\n"
                . "            <cac:TaxCategory><cac:TaxScheme><cbc:ID>1000</cbc:ID><cbc:Name>IGV</cbc:Name><cbc:TaxTypeCode>VAT</cbc:TaxTypeCode></cac:TaxScheme></cac:TaxCategory>\n"
                . "        </cac:TaxSubtotal>\n";
        }
        if ($totExonerado > 0) {
            $ncTaxSubtotals .= "        <cac:TaxSubtotal>\n"
                . "            <cbc:TaxableAmount currencyID=\"$moneda\">$totExoneradoF</cbc:TaxableAmount>\n"
                . "            <cbc:TaxAmount currencyID=\"$moneda\">0.00</cbc:TaxAmount>\n"
                . "            <cac:TaxCategory><cac:TaxScheme><cbc:ID>9997</cbc:ID><cbc:Name>EXO</cbc:Name><cbc:TaxTypeCode>VAT</cbc:TaxTypeCode></cac:TaxScheme></cac:TaxCategory>\n"
                . "        </cac:TaxSubtotal>\n";
        }
        if ($totInafecto > 0) {
            $ncTaxSubtotals .= "        <cac:TaxSubtotal>\n"
                . "            <cbc:TaxableAmount currencyID=\"$moneda\">$totInafectoF</cbc:TaxableAmount>\n"
                . "            <cbc:TaxAmount currencyID=\"$moneda\">0.00</cbc:TaxAmount>\n"
                . "            <cac:TaxCategory><cac:TaxScheme><cbc:ID>9998</cbc:ID><cbc:Name>INA</cbc:Name><cbc:TaxTypeCode>FRE</cbc:TaxTypeCode></cac:TaxScheme></cac:TaxCategory>\n"
                . "        </cac:TaxSubtotal>\n";
        }
        // Si no hubiera ningun subtotal (caso borde), declarar gravado en 0 para validez
        if ($ncTaxSubtotals === '') {
            $ncTaxSubtotals = "        <cac:TaxSubtotal>\n"
                . "            <cbc:TaxableAmount currencyID=\"$moneda\">0.00</cbc:TaxableAmount>\n"
                . "            <cbc:TaxAmount currencyID=\"$moneda\">0.00</cbc:TaxAmount>\n"
                . "            <cac:TaxCategory><cac:TaxScheme><cbc:ID>1000</cbc:ID><cbc:Name>IGV</cbc:Name><cbc:TaxTypeCode>VAT</cbc:TaxTypeCode></cac:TaxScheme></cac:TaxCategory>\n"
                . "        </cac:TaxSubtotal>\n";
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<CreditNote xmlns="urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2"
            xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
            xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
            xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
            xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionContent />
        </ext:UBLExtension>
    </ext:UBLExtensions>
    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>2.0</cbc:CustomizationID>
    <cbc:ID>$idDoc</cbc:ID>
    <cbc:IssueDate>$fecha</cbc:IssueDate>
    <cbc:IssueTime>$hora</cbc:IssueTime>
    <cbc:Note languageLocaleID="1000"><![CDATA[$totalLetras]]></cbc:Note>
    <cbc:DocumentCurrencyCode>$moneda</cbc:DocumentCurrencyCode>

    <cac:DiscrepancyResponse>
        <cbc:ReferenceID>$refNumeroCompleto</cbc:ReferenceID>
        <cbc:ResponseCode>$motivoCod</cbc:ResponseCode>
        <cbc:Description><![CDATA[$motivoDes]]></cbc:Description>
    </cac:DiscrepancyResponse>

    <cac:BillingReference>
        <cac:InvoiceDocumentReference>
            <cbc:ID>$refNumeroCompleto</cbc:ID>
            <cbc:DocumentTypeCode>$refTipoDoc</cbc:DocumentTypeCode>
        </cac:InvoiceDocumentReference>
    </cac:BillingReference>

    <cac:Signature>
        <cbc:ID>$rucEmisor</cbc:ID>
        <cbc:Note><![CDATA[$nombreComEmisor]]></cbc:Note>
        <cac:SignatoryParty>
            <cac:PartyIdentification>
                <cbc:ID>$rucEmisor</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name><![CDATA[$razonEmisor]]></cbc:Name>
            </cac:PartyName>
        </cac:SignatoryParty>
        <cac:DigitalSignatureAttachment>
            <cac:ExternalReference>
                <cbc:URI>#SignatureSP</cbc:URI>
            </cac:ExternalReference>
        </cac:DigitalSignatureAttachment>
    </cac:Signature>

    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="6">$rucEmisor</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name><![CDATA[$nombreComEmisor]]></cbc:Name>
            </cac:PartyName>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[$razonEmisor]]></cbc:RegistrationName>
                <cac:RegistrationAddress>
                    <cbc:ID>$empUbigeo</cbc:ID>
                    <cbc:AddressTypeCode>0000</cbc:AddressTypeCode>
                    <cbc:CitySubdivisionName>NONE</cbc:CitySubdivisionName>
                    <cbc:CityName>$empProvincia</cbc:CityName>
                    <cbc:CountrySubentity>$empDepartamento</cbc:CountrySubentity>
                    <cbc:District>$empDistrito</cbc:District>
                    <cac:AddressLine>
                        <cbc:Line><![CDATA[$empDireccion]]></cbc:Line>
                    </cac:AddressLine>
                    <cac:Country>
                        <cbc:IdentificationCode>$empPais</cbc:IdentificationCode>
                    </cac:Country>
                </cac:RegistrationAddress>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>

    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="$cliTipoDoc">$cliNumDoc</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[$cliRazon]]></cbc:RegistrationName>
                <cac:RegistrationAddress>
                    <cac:AddressLine>
                        <cbc:Line><![CDATA[$cliDir]]></cbc:Line>
                    </cac:AddressLine>
                </cac:RegistrationAddress>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingCustomerParty>

    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="$moneda">$igvTotalF</cbc:TaxAmount>
$ncTaxSubtotals
    </cac:TaxTotal>

    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="$moneda">$lineExtTotalF</cbc:LineExtensionAmount>
        <cbc:PayableAmount currencyID="$moneda">$totalVentaF</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>

$lineas
</CreditNote>
XML;
        return $xml;
    }

    // =================================================================
    // NOTA DE DEBITO (tipo 08) - estructura UBL DebitNote
    // =================================================================
    public function buildNotaDebito(array $comp)
    {
        // Estructura casi identica a NC pero usa <DebitNote> y <DebitNoteLine>
        $rucEmisor       = $comp['empresa_ruc'];
        $razonEmisor     = $this->esc($comp['empresa_razon']);
        $nombreComEmisor = $this->esc($comp['empresa_nombre_comercial'] ?? $comp['empresa_razon']);
        $serie  = $comp['serie'];
        $numero = ltrim($comp['numero'], '0') ?: '0';
        $idDoc  = $serie . '-' . $numero;
        $fecha = date('Y-m-d', strtotime($comp['fecha_emision']));
        $hora  = date('H:i:s', strtotime($comp['fecha_emision']));
        $cliTipoDoc = $comp['cliente_tipo_doc'];
        $cliNumDoc  = $comp['cliente_num_doc'];
        $cliRazon   = $this->esc($comp['cliente_razon']);
        $cliDir     = $this->esc($comp['cliente_direccion'] ?? '');
        $moneda = $comp['tipo_moneda'];
        $tasaIgv = (float)($comp['tasa_igv'] ?? 0.18);
        $refTipoDoc = $comp['ref_tipo_documento'];
        $refNumeroCompleto = $comp['ref_serie'] . '-' . ltrim($comp['ref_numero'], '0');
        $motivoCod = $comp['motivo_codigo'] ?? '01';
        $motivoDes = $this->esc($comp['motivo_descripcion'] ?? 'Aumento de valor');
        $empUbigeo       = $this->esc($comp['empresa_ubigeo']       ?? '150101');
        $empDepartamento = $this->esc($comp['empresa_departamento'] ?? 'LIMA');
        $empProvincia    = $this->esc($comp['empresa_provincia']    ?? 'LIMA');
        $empDistrito     = $this->esc($comp['empresa_distrito']     ?? 'LIMA');
        $empPais         = $this->esc($comp['empresa_pais']         ?? 'PE');
        $empDireccion    = $this->esc($comp['empresa_direccion']    ?? '');
        $igvTotal     = (float)$comp['igv'];
        $totalVenta   = (float)$comp['total'];
        $totalLetras  = $this->esc($comp['total_letras'] ?? '');

        // Sumar por afectacion (gravado/exonerado/inafecto)
        $totGravado = 0; $totExonerado = 0; $totInafecto = 0;
        foreach ($comp['items'] as $i) {
            $a = (int)($i['codigo_afectacion'] ?? 10);
            if ($a === 20)      $totExonerado += (float)$i['valor_venta'];
            elseif ($a === 30)  $totInafecto  += (float)$i['valor_venta'];
            else                $totGravado   += (float)$i['valor_venta'];
        }
        $lineExtTotal  = $totGravado + $totExonerado + $totInafecto;
        $totalGravadoF = number_format($totGravado, 2, '.', '');
        $totExoneradoF = number_format($totExonerado, 2, '.', '');
        $totInafectoF  = number_format($totInafecto, 2, '.', '');
        $igvTotalF     = number_format($igvTotal, 2, '.', '');
        $totalVentaF   = number_format($totalVenta, 2, '.', '');
        $lineExtTotalF = number_format($lineExtTotal, 2, '.', '');

        $lineas = '';
        $idx = 1;
        foreach ($comp['items'] as $i) {
            $cnt = number_format((float)$i['cantidad'], 2, '.', '');
            $um  = $i['unidad_medida'];
            $pu  = number_format((float)$i['precio_unitario'], 2, '.', '');
            $pci = number_format((float)$i['precio_con_igv'], 2, '.', '');
            $vv  = number_format((float)$i['valor_venta'], 2, '.', '');
            $iv  = number_format((float)$i['igv_item'], 2, '.', '');
            $des = $this->esc($i['descripcion']);
            $cod = $this->esc($i['codigo'] ?? '');
            $afect = (int)($i['codigo_afectacion'] ?? 10);
            $afectStr = str_pad($afect, 2, '0', STR_PAD_LEFT);
            if ($afect === 20) { $tsId='9997'; $tsName='EXO'; $ttCode='VAT'; $tasaPct='0.00'; }
            elseif ($afect === 30) { $tsId='9998'; $tsName='INA'; $ttCode='FRE'; $tasaPct='0.00'; }
            else { $tsId='1000'; $tsName='IGV'; $ttCode='VAT'; $tasaPct=number_format($tasaIgv*100,2,'.',''); }

            $lineas .= <<<XML
    <cac:DebitNoteLine>
        <cbc:ID>$idx</cbc:ID>
        <cbc:DebitedQuantity unitCode="$um">$cnt</cbc:DebitedQuantity>
        <cbc:LineExtensionAmount currencyID="$moneda">$vv</cbc:LineExtensionAmount>
        <cac:PricingReference>
            <cac:AlternativeConditionPrice>
                <cbc:PriceAmount currencyID="$moneda">$pci</cbc:PriceAmount>
                <cbc:PriceTypeCode>01</cbc:PriceTypeCode>
            </cac:AlternativeConditionPrice>
        </cac:PricingReference>
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID="$moneda">$iv</cbc:TaxAmount>
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID="$moneda">$vv</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID="$moneda">$iv</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cbc:Percent>$tasaPct</cbc:Percent>
                    <cbc:TaxExemptionReasonCode>$afectStr</cbc:TaxExemptionReasonCode>
                    <cac:TaxScheme>
                        <cbc:ID>$tsId</cbc:ID>
                        <cbc:Name>$tsName</cbc:Name>
                        <cbc:TaxTypeCode>$ttCode</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        </cac:TaxTotal>
        <cac:Item>
            <cbc:Description><![CDATA[$des]]></cbc:Description>
            <cac:SellersItemIdentification>
                <cbc:ID>$cod</cbc:ID>
            </cac:SellersItemIdentification>
        </cac:Item>
        <cac:Price>
            <cbc:PriceAmount currencyID="$moneda">$pu</cbc:PriceAmount>
        </cac:Price>
    </cac:DebitNoteLine>

XML;
            $idx++;
        }

        // TaxSubtotals dinamicos por afectacion (igual que NC)
        $ndTaxSubtotals = '';
        if ($totGravado > 0) {
            $ndTaxSubtotals .= "        <cac:TaxSubtotal>\n"
                . "            <cbc:TaxableAmount currencyID=\"$moneda\">$totalGravadoF</cbc:TaxableAmount>\n"
                . "            <cbc:TaxAmount currencyID=\"$moneda\">$igvTotalF</cbc:TaxAmount>\n"
                . "            <cac:TaxCategory><cac:TaxScheme><cbc:ID>1000</cbc:ID><cbc:Name>IGV</cbc:Name><cbc:TaxTypeCode>VAT</cbc:TaxTypeCode></cac:TaxScheme></cac:TaxCategory>\n"
                . "        </cac:TaxSubtotal>\n";
        }
        if ($totExonerado > 0) {
            $ndTaxSubtotals .= "        <cac:TaxSubtotal>\n"
                . "            <cbc:TaxableAmount currencyID=\"$moneda\">$totExoneradoF</cbc:TaxableAmount>\n"
                . "            <cbc:TaxAmount currencyID=\"$moneda\">0.00</cbc:TaxAmount>\n"
                . "            <cac:TaxCategory><cac:TaxScheme><cbc:ID>9997</cbc:ID><cbc:Name>EXO</cbc:Name><cbc:TaxTypeCode>VAT</cbc:TaxTypeCode></cac:TaxScheme></cac:TaxCategory>\n"
                . "        </cac:TaxSubtotal>\n";
        }
        if ($totInafecto > 0) {
            $ndTaxSubtotals .= "        <cac:TaxSubtotal>\n"
                . "            <cbc:TaxableAmount currencyID=\"$moneda\">$totInafectoF</cbc:TaxableAmount>\n"
                . "            <cbc:TaxAmount currencyID=\"$moneda\">0.00</cbc:TaxAmount>\n"
                . "            <cac:TaxCategory><cac:TaxScheme><cbc:ID>9998</cbc:ID><cbc:Name>INA</cbc:Name><cbc:TaxTypeCode>FRE</cbc:TaxTypeCode></cac:TaxScheme></cac:TaxCategory>\n"
                . "        </cac:TaxSubtotal>\n";
        }
        if ($ndTaxSubtotals === '') {
            $ndTaxSubtotals = "        <cac:TaxSubtotal>\n"
                . "            <cbc:TaxableAmount currencyID=\"$moneda\">0.00</cbc:TaxableAmount>\n"
                . "            <cbc:TaxAmount currencyID=\"$moneda\">0.00</cbc:TaxAmount>\n"
                . "            <cac:TaxCategory><cac:TaxScheme><cbc:ID>1000</cbc:ID><cbc:Name>IGV</cbc:Name><cbc:TaxTypeCode>VAT</cbc:TaxTypeCode></cac:TaxScheme></cac:TaxCategory>\n"
                . "        </cac:TaxSubtotal>\n";
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<DebitNote xmlns="urn:oasis:names:specification:ubl:schema:xsd:DebitNote-2"
           xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
           xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
           xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
           xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionContent />
        </ext:UBLExtension>
    </ext:UBLExtensions>
    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>2.0</cbc:CustomizationID>
    <cbc:ID>$idDoc</cbc:ID>
    <cbc:IssueDate>$fecha</cbc:IssueDate>
    <cbc:IssueTime>$hora</cbc:IssueTime>
    <cbc:Note languageLocaleID="1000"><![CDATA[$totalLetras]]></cbc:Note>
    <cbc:DocumentCurrencyCode>$moneda</cbc:DocumentCurrencyCode>

    <cac:DiscrepancyResponse>
        <cbc:ReferenceID>$refNumeroCompleto</cbc:ReferenceID>
        <cbc:ResponseCode>$motivoCod</cbc:ResponseCode>
        <cbc:Description><![CDATA[$motivoDes]]></cbc:Description>
    </cac:DiscrepancyResponse>

    <cac:BillingReference>
        <cac:InvoiceDocumentReference>
            <cbc:ID>$refNumeroCompleto</cbc:ID>
            <cbc:DocumentTypeCode>$refTipoDoc</cbc:DocumentTypeCode>
        </cac:InvoiceDocumentReference>
    </cac:BillingReference>

    <cac:Signature>
        <cbc:ID>$rucEmisor</cbc:ID>
        <cbc:Note><![CDATA[$nombreComEmisor]]></cbc:Note>
        <cac:SignatoryParty>
            <cac:PartyIdentification>
                <cbc:ID>$rucEmisor</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name><![CDATA[$razonEmisor]]></cbc:Name>
            </cac:PartyName>
        </cac:SignatoryParty>
        <cac:DigitalSignatureAttachment>
            <cac:ExternalReference>
                <cbc:URI>#SignatureSP</cbc:URI>
            </cac:ExternalReference>
        </cac:DigitalSignatureAttachment>
    </cac:Signature>

    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="6">$rucEmisor</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[$razonEmisor]]></cbc:RegistrationName>
                <cac:RegistrationAddress>
                    <cbc:ID>$empUbigeo</cbc:ID>
                    <cbc:AddressTypeCode>0000</cbc:AddressTypeCode>
                    <cbc:CitySubdivisionName>NONE</cbc:CitySubdivisionName>
                    <cbc:CityName>$empProvincia</cbc:CityName>
                    <cbc:CountrySubentity>$empDepartamento</cbc:CountrySubentity>
                    <cbc:District>$empDistrito</cbc:District>
                    <cac:AddressLine>
                        <cbc:Line><![CDATA[$empDireccion]]></cbc:Line>
                    </cac:AddressLine>
                    <cac:Country>
                        <cbc:IdentificationCode>$empPais</cbc:IdentificationCode>
                    </cac:Country>
                </cac:RegistrationAddress>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>

    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="$cliTipoDoc">$cliNumDoc</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[$cliRazon]]></cbc:RegistrationName>
                <cac:RegistrationAddress>
                    <cac:AddressLine>
                        <cbc:Line><![CDATA[$cliDir]]></cbc:Line>
                    </cac:AddressLine>
                </cac:RegistrationAddress>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingCustomerParty>

    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="$moneda">$igvTotalF</cbc:TaxAmount>
$ndTaxSubtotals
    </cac:TaxTotal>

    <cac:RequestedMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="$moneda">$lineExtTotalF</cbc:LineExtensionAmount>
        <cbc:PayableAmount currencyID="$moneda">$totalVentaF</cbc:PayableAmount>
    </cac:RequestedMonetaryTotal>

$lineas
</DebitNote>
XML;
        return $xml;
    }

    // =================================================================
    // RESUMEN DIARIO DE BOLETAS (RC) - SummaryDocuments
    // =================================================================
    public function buildResumenBoletas(array $resumen)
    {
        $rucEmisor       = $resumen['empresa_ruc'];
        $razonEmisor     = $this->esc($resumen['empresa_razon']);
        $nombreComEmisor = $this->esc($resumen['empresa_nombre_comercial'] ?? $resumen['empresa_razon']);
        $idDoc        = $resumen['serie_doc']; // RC-YYYYMMDD-NNN
        $fechaGen     = date('Y-m-d', strtotime($resumen['fecha_generacion']));
        $fechaRef     = date('Y-m-d', strtotime($resumen['fecha_referencia']));
        $moneda       = 'PEN';

        $lineas = '';
        $idx = 1;
        foreach ($resumen['items'] as $i) {
            $tipoDoc = $i['tipo_documento'];
            $serie   = $i['serie'];
            $numero  = ltrim($i['numero'], '0') ?: '0';
            $numComp = $serie . '-' . $numero;
            $cliTipoDoc = $i['cliente_tipo_doc'] ?? '1';
            $cliNumDoc  = $i['cliente_num_doc']  ?? '00000000';
            $totalGravado   = number_format((float)($i['total_gravado']   ?? 0), 2, '.', '');
            $totalExonerado = number_format((float)($i['total_exonerado'] ?? 0), 2, '.', '');
            $totalInafecto  = number_format((float)($i['total_inafecto']  ?? 0), 2, '.', '');
            $igv            = number_format((float)($i['igv']             ?? 0), 2, '.', '');
            $total          = number_format((float)($i['total']           ?? 0), 2, '.', '');

            $lineas .= <<<XML
    <sac:SummaryDocumentsLine>
        <cbc:LineID>$idx</cbc:LineID>
        <cbc:DocumentTypeCode>$tipoDoc</cbc:DocumentTypeCode>
        <cbc:ID>$numComp</cbc:ID>
        <cac:AccountingCustomerParty>
            <cbc:CustomerAssignedAccountID>$cliNumDoc</cbc:CustomerAssignedAccountID>
            <cbc:AdditionalAccountID>$cliTipoDoc</cbc:AdditionalAccountID>
        </cac:AccountingCustomerParty>
        <cac:Status>
            <cbc:ConditionCode>1</cbc:ConditionCode>
        </cac:Status>
        <sac:TotalAmount currencyID="$moneda">$total</sac:TotalAmount>
        <sac:BillingPayment>
            <cbc:PaidAmount currencyID="$moneda">$totalGravado</cbc:PaidAmount>
            <cbc:InstructionID>01</cbc:InstructionID>
        </sac:BillingPayment>
        <sac:BillingPayment>
            <cbc:PaidAmount currencyID="$moneda">$totalExonerado</cbc:PaidAmount>
            <cbc:InstructionID>02</cbc:InstructionID>
        </sac:BillingPayment>
        <sac:BillingPayment>
            <cbc:PaidAmount currencyID="$moneda">$totalInafecto</cbc:PaidAmount>
            <cbc:InstructionID>03</cbc:InstructionID>
        </sac:BillingPayment>
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID="$moneda">$igv</cbc:TaxAmount>
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID="$moneda">$totalGravado</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID="$moneda">$igv</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cbc:Percent>18</cbc:Percent>
                    <cac:TaxScheme>
                        <cbc:ID>1000</cbc:ID>
                        <cbc:Name>IGV</cbc:Name>
                        <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        </cac:TaxTotal>
    </sac:SummaryDocumentsLine>

XML;
            $idx++;
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SummaryDocuments xmlns="urn:sunat:names:specification:ubl:peru:schema:xsd:SummaryDocuments-1"
                  xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
                  xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
                  xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                  xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
                  xmlns:sac="urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1">
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionContent />
        </ext:UBLExtension>
    </ext:UBLExtensions>
    <cbc:UBLVersionID>2.0</cbc:UBLVersionID>
    <cbc:CustomizationID>1.1</cbc:CustomizationID>
    <cbc:ID>$idDoc</cbc:ID>
    <cbc:ReferenceDate>$fechaRef</cbc:ReferenceDate>
    <cbc:IssueDate>$fechaGen</cbc:IssueDate>
    <cac:Signature>
        <cbc:ID>$rucEmisor</cbc:ID>
        <cac:SignatoryParty>
            <cac:PartyIdentification>
                <cbc:ID>$rucEmisor</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name><![CDATA[$razonEmisor]]></cbc:Name>
            </cac:PartyName>
        </cac:SignatoryParty>
        <cac:DigitalSignatureAttachment>
            <cac:ExternalReference>
                <cbc:URI>#SignatureSP</cbc:URI>
            </cac:ExternalReference>
        </cac:DigitalSignatureAttachment>
    </cac:Signature>
    <cac:AccountingSupplierParty>
        <cbc:CustomerAssignedAccountID>$rucEmisor</cbc:CustomerAssignedAccountID>
        <cbc:AdditionalAccountID>6</cbc:AdditionalAccountID>
        <cac:Party>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[$razonEmisor]]></cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>

$lineas
</SummaryDocuments>
XML;
        return $xml;
    }

    // =================================================================
    // COMUNICACION DE BAJA (RA) - VoidedDocuments
    // =================================================================
    public function buildComunicacionBaja(array $resumen)
    {
        $rucEmisor    = $resumen['empresa_ruc'];
        $razonEmisor  = $this->esc($resumen['empresa_razon']);
        $idDoc        = $resumen['serie_doc']; // RA-YYYYMMDD-NNN
        $fechaGen     = date('Y-m-d', strtotime($resumen['fecha_generacion']));
        $fechaRef     = date('Y-m-d', strtotime($resumen['fecha_referencia']));

        $lineas = '';
        $idx = 1;
        foreach ($resumen['items'] as $i) {
            $tipoDoc  = $i['tipo_documento'];
            $serie    = $i['serie'];
            $numero   = ltrim($i['numero'], '0') ?: '0';
            $motivo   = $this->esc($i['motivo_baja'] ?? 'ERROR DE EMISION');

            $lineas .= <<<XML
    <sac:VoidedDocumentsLine>
        <cbc:LineID>$idx</cbc:LineID>
        <cbc:DocumentTypeCode>$tipoDoc</cbc:DocumentTypeCode>
        <sac:DocumentSerialID>$serie</sac:DocumentSerialID>
        <sac:DocumentNumberID>$numero</sac:DocumentNumberID>
        <sac:VoidReasonDescription><![CDATA[$motivo]]></sac:VoidReasonDescription>
    </sac:VoidedDocumentsLine>

XML;
            $idx++;
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<VoidedDocuments xmlns="urn:sunat:names:specification:ubl:peru:schema:xsd:VoidedDocuments-1"
                 xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
                 xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
                 xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                 xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
                 xmlns:sac="urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1">
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionContent />
        </ext:UBLExtension>
    </ext:UBLExtensions>
    <cbc:UBLVersionID>2.0</cbc:UBLVersionID>
    <cbc:CustomizationID>1.0</cbc:CustomizationID>
    <cbc:ID>$idDoc</cbc:ID>
    <cbc:ReferenceDate>$fechaRef</cbc:ReferenceDate>
    <cbc:IssueDate>$fechaGen</cbc:IssueDate>
    <cac:Signature>
        <cbc:ID>$rucEmisor</cbc:ID>
        <cac:SignatoryParty>
            <cac:PartyIdentification>
                <cbc:ID>$rucEmisor</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name><![CDATA[$razonEmisor]]></cbc:Name>
            </cac:PartyName>
        </cac:SignatoryParty>
        <cac:DigitalSignatureAttachment>
            <cac:ExternalReference>
                <cbc:URI>#SignatureSP</cbc:URI>
            </cac:ExternalReference>
        </cac:DigitalSignatureAttachment>
    </cac:Signature>
    <cac:AccountingSupplierParty>
        <cbc:CustomerAssignedAccountID>$rucEmisor</cbc:CustomerAssignedAccountID>
        <cbc:AdditionalAccountID>6</cbc:AdditionalAccountID>
        <cac:Party>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[$razonEmisor]]></cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>

$lineas
</VoidedDocuments>
XML;
        return $xml;
    }

    private function esc($s)
    {
        return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
