---
description: SIFEN v150 fiscal rules — Paraguay electronic invoicing conventions
alwaysApply: false
scope: sifen
---

# SIFEN v150 — Facturación Electrónica Paraguay

## Referencia
Manual Técnico SIFEN versión 150. Ambiente de pruebas: `ekuatia.set.gov.py/consultas-test`.

## Servicios SIFEN

| Servicio | Clase | Función |
|----------|-------|---------|
| CDC | `App\Services\SifenCdcService` | Genera el código de 44 dígitos |
| QR | `App\Services\SifenQrService` | URL del código QR con hash SHA256+CSC |
| XML | `App\Services\SifenXmlService` | Genera el `<rDE>` completo |

### SifenCdcService — Notas de Implementación

- `buildBase()` aplica padding interno a **todos** los campos: `establecimiento` (3), `puntoExp` (3), `numeroDoc` (7). Los llamadores **no necesitan** padear estos valores antes de invocar `buildBase()`.
- `generateFromSale()` padea los campos de forma defensiva antes de llamar a `buildBase()` — esto es codificación defensiva, no un requisito de la interfaz.

## CDC — Código de Control (44 dígitos)

Estructura: `dTiDE(2) + dRucEm(8) + dDVEmi(1) + dEst(3) + dPunExp(3) + dNumDoc(7) + dSisFact(1) + dFeEmiDE(8) + iTipEmi(1) + dCodSeg(9) + dDVId(1)`

### Algoritmo Módulo 11 (dDVId)
1. Recorrer los 43 chars de derecha a izquierda.
2. Pesos cíclicos 2→9 de derecha a izquierda (al llegar a 9, vuelve a 2).
3. `suma = Σ(dígito × peso)`
4. `resto = suma % 11`
5. Resultado: 0→0, 1→1, resto≥2 → `11 - resto`

## Tipos de Documentos (`iTiDE`)

| Valor | Descripción | Campo `document_type` |
|-------|-------------|----------------------|
| `1` | Factura electrónica | `factura` |
| `4` | Autofactura electrónica | `autofactura` |
| `5` | Nota de crédito electrónica | `nota_credito` |
| `6` | Nota de débito electrónica | `nota_debito` |
| `7` | Nota de remisión electrónica | `nota_remision` |

## Fuentes de Datos por Sección XML

### `<gEmis>` (Emisor)
Fuente: `Company` con campos SIFEN:
- `ruc` → `dRucEm` (solo número, sin `-DV`)
- `ruc_dv` → `dDVEmi`
- `tipo_contribuyente` → `iTipCont` (1=Física, 2=Jurídica)
- `tipo_regimen` → `cTipReg`
- `departamento_code/desc` → `cDepEmi/dDesDepEmi`
- `ciudad_code/desc` → `cCiuEmi/dDesCiuEmi`
- `actividad_eco_code/desc` → `gActEco`

### `<gTimb>` (Timbrado)
Fuente: `Sale` + `Branch`:
- `Sale::timbrado` → `dNumTim` (número de timbrado SET)
- `Sale::invoice_number` → `dNumDoc` (7 dígitos, padded)
- `Branch::establishment_code` → `dEst` (default `001`)
- `Branch::dispatch_point` → `dPunExp` (default `001`)
- `Branch::timbrado_start_date` → `dFeIniVig` (fecha inicio vigencia)

### `<gDatRec>` (Receptor)
Fuente: `Customer`:
- Sin documento: `iTiOpe=2` (consumidor final), `dNomRec='SIN NOMBRE'`
- Con documento RUC: separar por `-` → `dRucRec` y `dDVRec`

### `<gCamItem>` (Ítems)
IVA en Paraguay es **precio final incluye IVA**:
```
base_gravada = precio_total / (1 + tasa/100)
iva_item = precio_total - base_gravada
```
- `tax_percentage = 10` → `dTasaIVA=10`, `iAfecIVA=1` (Gravado)
- `tax_percentage = 5` → `dTasaIVA=5`, `iAfecIVA=1` (Gravado)
- `tax_percentage = 0` → `dTasaIVA=0`, `iAfecIVA=3` (Exento)

### `<gTotSub>` (Totales)
Fuente: Campos del modelo `Sale`:
- `subtotal_exenta`, `subtotal_5`, `subtotal_10`
- `tax_5`, `tax_10`
- `base_5 = subtotal_5 - tax_5`
- `base_10 = subtotal_10 - tax_10`

## SifenXmlService — Estructura del XML

El servicio genera el documento `<rDE>` completo usando el DOM de PHP. La jerarquía de nodos es la siguiente:

```
<rDE xmlns="http://ekuatia.set.gov.py/sifen/xsd" ...>
  <DE Id="{cdc}">
    dDVId          — dígito verificador del CDC
    dFecFirma      — fecha/hora de generación (ISO-8601)
    dSisFact       — sistema de facturación (config sifen.issuer.system_facturation)

    <gOpeDE>       — operación del documento electrónico
      iTipEmi      — tipo de emisión (1=Normal)
      dCodSeg      — código de seguridad (9 dígitos aleatorios)
      dInfoEmi     — descripción del tipo de emisión

    <gTimb>        — datos del timbrado
      iTiDE        — tipo de documento (1=Factura, etc.)
      dNumTim      — número de timbrado SET
      dEst         — establecimiento (3 dígitos, ej. '001')
      dPunExp      — punto de expedición (3 dígitos, ej. '001')
      dNumDoc      — número de documento (7 dígitos, padded)
      dFeIniVig    — fecha inicio vigencia timbrado

    <gDatGralOpe>  — datos generales de la operación
      dFeEmiDE     — fecha y hora de emisión
      <gOpeCom>    — operación comercial
        iTipTra    — tipo de transacción (1=Venta de mercadería)
        iTIVA      — indicador de afectación IVA (1=Operación IVA)
        iNatOpe    — naturaleza de la operación (1=Venta)
        iTiOpe     — tipo de operación (1=B2B, 2=B2C consumidor final)
        cPaisOpe   — código país operación (PRY)
        iTipPres   — tipo de presencia (1=Operación presencial)
      <gEmis>      — datos del emisor (Company)
        dRucEm, dDVEmi, iTipCont, dNomEmi, cTipReg
        dDirEmi, cDepEmi, dDesDepEmi, cCiuEmi, dDesCiuEmi
        dTelEmi, dEmailE
        <gActEco>  — actividad económica (código + descripción)
      <gDatRec>    — datos del receptor (Customer)
        iTiOpe     — 1=B2B (con RUC), 2=B2C (consumidor final)
        dRucRec/dDVRec  — si tiene RUC
        dNomRec    — nombre receptor ('SIN NOMBRE' si consumidor final)

  <gDtipDE>        — datos específicos del tipo de documento
    <gCamFE>       — campos factura electrónica
      iIndPres     — indicador presencia (1=Presencial)
    <gCamCond>     — condición de pago
      iCondOpe     — 1=Contado, 2=Crédito
      <gPaConEIni> — detalle de pago contado (tipo medio pago, monto)
      <gPagCre>    — detalle crédito (tipo, plazo, cuotas, etc.)
    <gCamItem>     — ítems de la venta (uno por SaleItem)
      dCodInt      — código interno del producto
      dDesProSer   — descripción
      cUniMed      — unidad de medida (77=unidad)
      dCantProSer  — cantidad
      dPUniProSer  — precio unitario
      dTotBruOpeItem — total bruto ítem
      <gValorRestaItem>
        dDescItem  — descuento ítem
        dTotOpeItem — total neto ítem
      <gCamIVA>    — IVA del ítem
        iAfecIVA   — 1=Gravado, 3=Exento
        dTasaIVA   — tasa: 10, 5 o 0
        dBasGravIVA — base gravada = total / (1 + tasa/100)
        dLiqIVAItem — IVA líquido = total - base_gravada

  <gTotSub>        — subtotales por tasa IVA
    dTotOpe        — total general de la operación
    dTotOpeMNT     — monto no gravado (exento)
    dTotOpeME      — monto exento
    dTotOpe5       — subtotal IVA 5%
    dTotOpe10      — subtotal IVA 10%
    dIVA5          — IVA total tasa 5%
    dIVA10         — IVA total tasa 10%
    dLiqTotIVA5    — base gravada 5%
    dLiqTotIVA10   — base gravada 10%

  <gCamFuFD>       — campos adicionales / complementarios

  <Signature>      — firma RSA-SHA256 (ver sección Firma Digital)
</DE>
</rDE>
```

---

## Campos Empresa Requeridos

Columnas que el modelo `Company` debe tener para generar XML SIFEN válido:

| Campo | Tipo | Descripción | XML |
|-------|------|-------------|-----|
| `ruc` | string | RUC sin guion ni DV | `dRucEm` |
| `ruc_dv` | string(1) | Dígito verificador del RUC | `dDVEmi` |
| `name` | string | Razón social o nombre | `dNomEmi` |
| `tipo_contribuyente` | integer | 1=Física, 2=Jurídica | `iTipCont` |
| `tipo_regimen` | integer | Código de régimen tributario | `cTipReg` |
| `departamento_code` | string | Código departamento SET | `cDepEmi` |
| `departamento_desc` | string | Descripción departamento | `dDesDepEmi` |
| `ciudad_code` | string | Código ciudad SET | `cCiuEmi` |
| `ciudad_desc` | string | Descripción ciudad | `dDesCiuEmi` |
| `actividad_eco_code` | string | Código actividad económica SET | `gActEco/cActEco` |
| `actividad_eco_desc` | string | Descripción actividad económica | `gActEco/dDesActEco` |
| `num_casa` | string | Número de casa/local | `dNumCas` |
| `telefono` | string | Teléfono de contacto | `dTelEmi` |
| `email` | string | Correo electrónico | `dEmailE` |

## Campos Sucursal Requeridos

Columnas que el modelo `Branch` debe tener para el timbrado SIFEN:

| Campo | Tipo | Default | Descripción | XML |
|-------|------|---------|-------------|-----|
| `establishment_code` | string(3) | `'001'` | Código de establecimiento | `dEst` |
| `dispatch_point` | string(3) | `'001'` | Punto de expedición | `dPunExp` |
| `timbrado_number` | string | — | Número de timbrado emitido por SET | `dNumTim` |
| `timbrado_start_date` | date | — | Fecha de inicio de vigencia del timbrado | `dFeIniVig` |

---

## URL QR — Estructura

```
{base_url}nVersion=150&Id={cdc}&dFeEmiDE={hex(fecha)}&dRucRec={ruc}&
dTotGralOpe={total}&dTotIVA={iva}&cItems={n}&DigestValue={hex(digest)}&
IdCSC={csc_id}&cHashQR={sha256(params + csc_val)}
```

- `dFeEmiDE`: bytes del string ISO-8601 convertidos a hex (`bin2hex()`)
- `DigestValue`: base64 del digest XML → decodificar → hex (`bin2hex(base64_decode(...))`)
- `cHashQR`: SHA256 del query string completo (sin `cHashQR`) + valor CSC

## Configuración (`config/sifen.php`)

```php
'version' => '150',
'environment' => env('SIFEN_ENV', 'test'),  // 'test' | 'production'
'issuer' => [
    'system_facturation' => 1,   // dSisFact
    'tipo_emision' => 1,         // iTipEmi — 1=Normal
],
'qr' => [
    'base_url' => '...',         // URL según ambiente
    'csc_id' => env('SIFEN_CSC_ID'),
    'csc_val' => env('SIFEN_CSC_VAL'),
],
'certificate' => [
    'path' => storage_path('app/sifen/certificate.p12'),
    'password' => env('SIFEN_CERT_PASSWORD'),
],
```

## Firma Digital (Pendiente)

La firma digital es **requerida** por la SET para el ambiente de producción. Sin ella el XML es estructuralmente válido y útil para desarrollo/preview, pero será **rechazado por SET en producción**.

- Librería: `robrichards/xmlseclibs`
- Algoritmo: RSA-SHA256 (enveloped signature, canonicalización C14N)
- El bloque `<Signature>` se inserta **dentro de `<rDE>`, inmediatamente después del cierre `</DE>`** — no dentro del `<DE>`.
- El certificado `.p12` (clave privada + certificado X.509) lo emite la SET Paraguay.
- La contraseña del `.p12` se configura en `SIFEN_CERT_PASSWORD`.

```
<rDE>
  <DE Id="{cdc}">
    ...
  </DE>
  <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">  ← aquí
    ...
  </Signature>
</rDE>
```

Flujo de implementación pendiente:
1. Cargar certificado `.p12` con `openssl_pkcs12_read()`.
2. Instanciar `XMLSecurityDSig` y firmar el nodo `<DE>` por `Id`.
3. Adjuntar el nodo `<Signature>` al `<rDE>` con `xmlseclibs`.

## Montos — Convención Paraguay
- Moneda: **Guaraní (PYG)** — sin decimales.
- Los campos de monto en el XML van como enteros: `(int) round($amount)`.
- Los campos `decimal:2` del modelo se redondean al generar el XML.

## Namespace XML
```xml
xmlns="http://ekuatia.set.gov.py/sifen/xsd"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:schemaLocation="https://ekuatia.set.gov.py/sifen/xsd siRecepDE_v150.xsd"
```
