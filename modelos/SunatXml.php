<?php
/**
 * Generador XML UBL 2.1 para Boleta y Factura electronica SUNAT.
 * Soporta tipos de afectacion del IGV segun catalogo 7 de SUNAT:
 *   - Gravado (10) → IGV normal 18% — TaxScheme 1000 / VAT
 *   - Exonerado (20) → exento de IGV — TaxScheme 9997 / VAT
 *   - Inafecto (30) → fuera del ambito del IGV — TaxScheme 9998 / FRE
 *   - Gratuita gravada (11..17) → bonificacion / muestra → TaxScheme 9996 / FRE
 */
class SunatXml
{
    public function buildBoletaFactura(array $comp)
    {
        $tipoDoc = $comp['tipo_documento']; // 01 factura, 03 boleta
        $tipoOp  = $comp['tipo_operacion'] ?? '0101';  // 0101 = Venta interna
        $rucEmisor   = $comp['empresa_ruc'];
        $razonEmisor = $this->esc($comp['empresa_razon']);
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

        // Datos de direccion del emisor (con valores por defecto seguros)
        $empUbigeo       = $this->esc($comp['empresa_ubigeo']       ?? '150101');
        $empDepartamento = $this->esc($comp['empresa_departamento'] ?? 'LIMA');
        $empProvincia    = $this->esc($comp['empresa_provincia']    ?? 'LIMA');
        $empDistrito     = $this->esc($comp['empresa_distrito']     ?? 'LIMA');
        $empPais         = $this->esc($comp['empresa_pais']         ?? 'PE');
        $empDireccion    = $this->esc($comp['empresa_direccion']    ?? '');

        // Sumar montos por tipo de afectacion (para construir TaxSubtotal correctos)
        $totGravado    = 0;
        $totExonerado  = 0;
        $totInafecto   = 0;
        $totGratuito   = 0;
        $igvTotal      = 0;

        foreach ($comp['items'] as $i) {
            $afect = (int)($i['codigo_afectacion'] ?? 10); // catalogo 7
            if ($afect === 10) {
                $totGravado   += (float)$i['valor_venta'];
                $igvTotal     += (float)$i['igv_item'];
            } elseif ($afect === 20) {
                $totExonerado += (float)$i['valor_venta'];
            } elseif ($afect === 30) {
                $totInafecto  += (float)$i['valor_venta'];
            } elseif ($afect >= 11 && $afect <= 17) {
                $totGratuito  += (float)$i['valor_venta'];
            } else {
                // Por defecto: gravado
                $totGravado   += (float)$i['valor_venta'];
                $igvTotal     += (float)$i['igv_item'];
            }
        }

        // Si todo viene calculado a nivel cabecera (caso comun PUERTO HABANA POS), usar los del comprobante
        if ($totGravado == 0 && $totExonerado == 0 && $totInafecto == 0 && $totGratuito == 0) {
            $totGravado = (float)$comp['subtotal'];
            $igvTotal   = (float)$comp['igv'];
        }

        $totalVenta  = (float)$comp['total'];
        $totalLetras = $this->esc($comp['total_letras'] ?? '');

        // Suma de valores de venta de TODAS las afectaciones = LineExtensionAmount
        // de cabecera (debe coincidir con la suma de InvoiceLine, sea gravado,
        // exonerado o inafecto). Era el bug del rechazo 3278.
        $lineExtensionTotal = $totGravado + $totExonerado + $totInafecto + $totGratuito;

        $totGravadoF   = number_format($totGravado, 2, '.', '');
        $totExoneradoF = number_format($totExonerado, 2, '.', '');
        $totInafectoF  = number_format($totInafecto, 2, '.', '');
        $totGratuitoF  = number_format($totGratuito, 2, '.', '');
        $igvTotalF     = number_format($igvTotal, 2, '.', '');
        $totalVentaF   = number_format($totalVenta, 2, '.', '');
        $lineExtTotalF = number_format($lineExtensionTotal, 2, '.', '');

        // ---------- Construir TaxSubtotals segun lo que tenga el comprobante ----------
        $taxSubtotals = '';

        // IGV gravado: solo si hay monto gravado (no incluir un subtotal en 0
        // cuando la venta es 100% exonerada/inafecta → SUNAT lo rechaza).
        if ($totGravado > 0) {
            $taxSubtotals .= <<<XML
        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID="$moneda">$totGravadoF</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID="$moneda">$igvTotalF</cbc:TaxAmount>
            <cac:TaxCategory>
                <cac:TaxScheme>
                    <cbc:ID>1000</cbc:ID>
                    <cbc:Name>IGV</cbc:Name>
                    <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>

XML;
        }

        if ($totExonerado > 0) {
            $taxSubtotals .= <<<XML
        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID="$moneda">$totExoneradoF</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID="$moneda">0.00</cbc:TaxAmount>
            <cac:TaxCategory>
                <cac:TaxScheme>
                    <cbc:ID>9997</cbc:ID>
                    <cbc:Name>EXO</cbc:Name>
                    <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>

XML;
        }

        if ($totInafecto > 0) {
            $taxSubtotals .= <<<XML
        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID="$moneda">$totInafectoF</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID="$moneda">0.00</cbc:TaxAmount>
            <cac:TaxCategory>
                <cac:TaxScheme>
                    <cbc:ID>9998</cbc:ID>
                    <cbc:Name>INA</cbc:Name>
                    <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>

XML;
        }

        // ---------- Construir lineas (InvoiceLine) ----------
        $lineas = '';
        $idx = 1;
        foreach ($comp['items'] as $i) {
            $cnt = number_format((float)$i['cantidad'], 2, '.', '');
            $um  = $i['unidad_medida'];
            $pu  = number_format((float)$i['precio_unitario'], 2, '.', '');
            $pci = number_format((float)$i['precio_con_igv'], 2, '.', '');
            $vv  = number_format((float)$i['valor_venta'], 2, '.', '');
            $iv  = number_format((float)$i['igv_item'], 2, '.', '');
            $ti  = number_format((float)$i['total_item'], 2, '.', '');
            $des = $this->esc($i['descripcion']);
            $cod = $this->esc($i['codigo'] ?? '');

            // Mapear codigo_afectacion → catalogo 7 + tax scheme
            $afect = (int)($i['codigo_afectacion'] ?? 10);
            $afectStr = str_pad($afect, 2, '0', STR_PAD_LEFT);
            // Determinar TaxScheme y porcentaje segun afectacion
            if ($afect === 10) {
                // Gravado
                $taxSchemeId   = '1000';
                $taxSchemeName = 'IGV';
                $taxTypeCode   = 'VAT';
                $tasaPct       = number_format(((float)$comp['tasa_igv']) * 100, 2, '.', '');
                $priceTypeCode = '01';
            } elseif ($afect === 20) {
                $taxSchemeId   = '9997';
                $taxSchemeName = 'EXO';
                $taxTypeCode   = 'VAT';
                $tasaPct       = '0.00';
                $priceTypeCode = '01';
            } elseif ($afect === 30) {
                $taxSchemeId   = '9998';
                $taxSchemeName = 'INA';
                $taxTypeCode   = 'FRE';
                $tasaPct       = '0.00';
                $priceTypeCode = '01';
            } else {
                $taxSchemeId   = '1000';
                $taxSchemeName = 'IGV';
                $taxTypeCode   = 'VAT';
                $tasaPct       = number_format(((float)$comp['tasa_igv']) * 100, 2, '.', '');
                $priceTypeCode = '01';
            }

            $lineas .= <<<XML
    <cac:InvoiceLine>
        <cbc:ID>$idx</cbc:ID>
        <cbc:InvoicedQuantity unitCode="$um">$cnt</cbc:InvoicedQuantity>
        <cbc:LineExtensionAmount currencyID="$moneda">$vv</cbc:LineExtensionAmount>
        <cac:PricingReference>
            <cac:AlternativeConditionPrice>
                <cbc:PriceAmount currencyID="$moneda">$pci</cbc:PriceAmount>
                <cbc:PriceTypeCode>$priceTypeCode</cbc:PriceTypeCode>
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
                        <cbc:ID>$taxSchemeId</cbc:ID>
                        <cbc:Name>$taxSchemeName</cbc:Name>
                        <cbc:TaxTypeCode>$taxTypeCode</cbc:TaxTypeCode>
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
    </cac:InvoiceLine>

XML;
            $idx++;
        }

        // ---------- Documento UBL ----------
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
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
    <cbc:DueDate>$fecha</cbc:DueDate>
    <cbc:InvoiceTypeCode listID="$tipoOp" listAgencyName="PE:SUNAT" listName="Tipo de Documento" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01" name="Tipo de Operacion" listSchemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo51">$tipoDoc</cbc:InvoiceTypeCode>
    <cbc:Note languageLocaleID="1000"><![CDATA[$totalLetras]]></cbc:Note>
    <cbc:DocumentCurrencyCode>$moneda</cbc:DocumentCurrencyCode>

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
                <cbc:ID schemeID="6" schemeName="Documento de Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">$rucEmisor</cbc:ID>
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
                <cbc:ID schemeID="$cliTipoDoc" schemeName="Documento de Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">$cliNumDoc</cbc:ID>
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

    <cac:PaymentTerms>
        <cbc:ID>FormaPago</cbc:ID>
        <cbc:PaymentMeansID>Contado</cbc:PaymentMeansID>
    </cac:PaymentTerms>

    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="$moneda">$igvTotalF</cbc:TaxAmount>
$taxSubtotals
    </cac:TaxTotal>

    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="$moneda">$lineExtTotalF</cbc:LineExtensionAmount>
        <cbc:TaxInclusiveAmount currencyID="$moneda">$totalVentaF</cbc:TaxInclusiveAmount>
        <cbc:PayableAmount currencyID="$moneda">$totalVentaF</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>

$lineas
</Invoice>
XML;
        return $xml;
    }

    private function esc($s)
    {
        return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
