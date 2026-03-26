# Plan de Implementación: Generación XML SIFEN v150

Este documento detalla la estrategia para implementar la generación de Facturas Electrónicas (FE) compatibles con el estándar SIFEN v150 de la SET (Paraguay).

## Objetivos
1. Generar el XML firmado digitalmente bajo la estructura `<rDE>`.
2. Implementar el cálculo automático del CDC (Código de Control) de 44 dígitos.
3. Generar la URL del código QR según los parámetros de seguridad requeridos.
4. Mapear todos los datos del sistema (Venta, Items, Cliente, Empresa) a los campos del XML.

## Componentes a Desarrollar

### 1. Configuración y Base de Datos
- **`config/sifen.php`**: Parámetros globales (versión, ambiente, CSC para QR). ✅
- **Migración `companies`**: Campos adicionales requeridos por SIFEN (RUC DV, Actividad Económica, etc.). ✅
- **Pendiente**: Migración `branches` para `establishment_code` y `dispatch_point` (dEst, dPunExp).

### 2. Servicios de Lógica (App\Services)
- **`SifenCdcService`**: Generación del CDC y dígito verificador. ✅
- **`SifenQrService`**: Construcción de la URL del QR con hash de seguridad. ✅
- **`SifenXmlService`**: Generación de la estructura DOM y firma digital. ✅

### 3. Firma Digital
- Se utilizará la librería `robrichards/xmlseclibs` para la firma RSA-SHA256 del bloque `<DE>`.
- El certificado (.p12) se almacenará en `storage/app/sifen/certificate.p12`.
- **Estado**: Pendiente de implementar (requiere certificado real de la SET).

## Estructura XML v150 — Mapeo de Campos

### Raíz `<rDE>`
| Campo XML | Fuente en el sistema |
|-----------|----------------------|
| `dVerFor` | `config('sifen.version')` → `150` |

### `<DE Id="...">` — Documento Electrónico
| Campo XML | Fuente en el sistema |
|-----------|----------------------|
| `@Id` (CDC 44 dígitos) | `SifenCdcService::generateFromSale()` |
| `dDVId` | Último dígito del CDC (módulo 11) |
| `dFecFirma` | `now()` — fecha/hora del servidor al generar |
| `dSisFact` | `config('sifen.issuer.system_facturation')` → `1` |

### `<gOpeDE>` — Operación del DE
| Campo XML | Fuente |
|-----------|--------|
| `iTipEmi` | `config('sifen.issuer.tipo_emision')` → `1` (Normal) |
| `dDesTipEmi` | `"Normal"` |
| `dCodSeg` | 9 dígitos aleatorios (generado en `SifenCdcService`) |
| `dInfoEmi` | `config('sifen.issuer.info_emi')` |
| `dInfoFisc` | `config('sifen.issuer.info_fisc')` |

### `<gTimb>` — Timbrado
| Campo XML | Fuente |
|-----------|--------|
| `iTiDE` | `Sale::document_type` → mapeado (`factura`=`01`, etc.) |
| `dDesTiDE` | Label del tipo de documento |
| `dNumTim` | `Sale::timbrado` |
| `dEst` | `Branch::establishment_code` (campo a agregar, default `001`) |
| `dPunExp` | `Branch::dispatch_point` (campo a agregar, default `001`) |
| `dNumDoc` | `Sale::invoice_number` (7 dígitos, padded) |
| `dSerieNum` | Opcional — no implementado |
| `dFeIniT` | Fecha inicio timbrado — pendiente de campo en `Branch` |

### `<gDatGralOpe>` — Datos Generales de la Operación
| Campo XML | Fuente |
|-----------|--------|
| `dFeEmiDE` | `Sale::sale_date` formato `Y-m-d\TH:i:s` |

#### `<gOpeCom>` — Operación Comercial
| Campo XML | Fuente |
|-----------|--------|
| `iTipTra` | `1` (Venta de mercadería) |
| `dDesTipTra` | `"Venta de mercadería"` |
| `iTImp` | `1` (IVA) |
| `dDesTImp` | `"IVA"` |
| `cMoneOpe` | `"PYG"` |
| `dDesMoneOpe` | `"Guarani"` |

#### `<gEmis>` — Datos del Emisor
| Campo XML | Fuente |
|-----------|--------|
| `dRucEm` | `Company::ruc` (solo número, sin DV) |
| `dDVEmi` | `Company::ruc_dv` |
| `iTipCont` | `Company::tipo_contribuyente` (1=Física, 2=Jurídica) |
| `cTipReg` | `Company::tipo_regimen` |
| `dNomEmi` | `Company::name` |
| `dDirEmi` | `Company::address` |
| `dNumCas` | `Company::num_casa` |
| `cDepEmi` | `Company::departamento_code` |
| `dDesDepEmi` | `Company::departamento_desc` |
| `cCiuEmi` | `Company::ciudad_code` |
| `dDesCiuEmi` | `Company::ciudad_desc` |
| `dTelEmi` | `Company::phone` |
| `dEmailE` | `Company::email` |
| `gActEco.cActEco` | `Company::actividad_eco_code` |
| `gActEco.dDesActEco` | `Company::actividad_eco_desc` |

#### `<gDatRec>` — Datos del Receptor
| Campo XML | Fuente |
|-----------|--------|
| `iNatRec` | `1` (Nacional) |
| `iTiOpe` | `1` (B2B con RUC) o `2` (B2C / Consumidor Final) |
| `cPaisRec` | `"PRY"` |
| `dDesPaisRe` | `"Paraguay"` |
| `iTiContRec` | `1` (Natural) o `2` (Jurídica) — según `Customer::document` |
| `dRucRec` | `Customer::document` (parte antes del `-`) |
| `dDVRec` | `Customer::document` (parte después del `-`) |
| `dNomRec` | `Customer::name` o `"SIN NOMBRE"` |
| `dDirRec` | `Customer::address` |
| `dNumCasRec` | `"0"` (default) |
| `dTelRec` | `Customer::phone` |
| `dCodCliente` | `Customer::id` |

### `<gDtipDE>` — Datos del Tipo de DE

#### `<gCamFE>` — Campos para Factura Electrónica
| Campo XML | Fuente |
|-----------|--------|
| `iIndPres` | `1` (Presencial) |
| `dDesIndPres` | `"Operación presencial"` |

#### `<gCamCond>` — Condición de la Operación
| Campo XML | Fuente |
|-----------|--------|
| `iCondOpe` | `1` (Contado) o `2` (Crédito) — de `Sale::payment_method` |
| `dDCondOpe` | `"Contado"` o `"Crédito"` |
| `gPagContado.iTiPago` | Método de pago (1=Efectivo, 3=Tarjeta crédito, etc.) |
| `gPagCred.iCondCred` | `1` (Plazo) — cuando es crédito |
| `gPagCred.dPlazoCre` | Días hasta `Sale::credit_due_date` |

#### `<gCamItem>` — Ítems (uno por `SaleItem`)
| Campo XML | Fuente |
|-----------|--------|
| `dCodInt` | `Product::sku` o `Product::barcode` |
| `dDesProSer` | `Product::name` (uppercase) |
| `cUniMed` | `77` (UNI) |
| `dDesUniMed` | `"UNI"` |
| `dCantProSer` | `SaleItem::quantity` |
| `gValorItem.dPUniProSer` | `SaleItem::price` (redondeado a entero PYG) |
| `gValorItem.dTotBruOpeItem` | `price * quantity` |
| `gValorRestaItem.dDescItem` | `SaleItem::discount` |
| `gValorRestaItem.dPorcDesIt` | `(discount / grossTotal) * 100` |
| `gValorRestaItem.dDescGloItem` | `0` |
| `gValorRestaItem.dTotOpeItem` | `subtotal` neto (bruto - descuento) |
| `gCamIVA.iAfecIVA` | `1` (Gravado) o `3` (Exento) |
| `gCamIVA.dPropIVA` | `100` |
| `gCamIVA.dTasaIVA` | `SaleItem::tax_percentage` (5 o 10) |
| `gCamIVA.dBasGravIVA` | `netTotal / (1 + rate/100)` |
| `gCamIVA.dLiqIVAItem` | `netTotal - basGrav` |

### `<gTotSub>` — Totales y Subtotales
| Campo XML | Fuente |
|-----------|--------|
| `dSubExe` | `Sale::subtotal_exenta` |
| `dSubExo` | `0` (no se manejan exonerados por ahora) |
| `dSub5` | `Sale::subtotal_5` |
| `dSub10` | `Sale::subtotal_10` |
| `dTotOpe` | `Sale::subtotal` (suma de netos de items) |
| `dTotDesc` | `Sale::discount` |
| `dTotGralOpe` | `Sale::total` |
| `dIVA5` | `Sale::tax_5` |
| `dIVA10` | `Sale::tax_10` |
| `dTotIVA` | `Sale::tax` |
| `dBaseGrav5` | `subtotal_5 - tax_5` |
| `dBaseGrav10` | `subtotal_10 - tax_10` |
| `dTBasGraIVA` | Suma de bases gravadas |

### `<gCamFuFD>` — Código QR
| Campo XML | Fuente |
|-----------|--------|
| `dCarQR` | `SifenQrService::generate()` |

## Algoritmo CDC (44 dígitos)

```
CDC = dTiDE(2) + dRucEm(8) + dDVEmi(1) + dEst(3) + dPunExp(3) + dNumDoc(7) + dSisFact(1) + dFeEmiDE(8) + iTipEmi(1) + dCodSeg(9) + dDVId(1)
```

**Módulo 11 (dDVId):**
1. Tomar los 43 chars base.
2. Asignar pesos cíclicos de derecha a izquierda: 2,3,4,5,6,7,8,9,2,3,...
3. Sumar `dígito * peso`.
4. `resto = suma % 11`
5. Si resto=0 → 0; si resto=1 → 1; sino → `11 - resto`.

## Tipos de Documentos (`iTiDE`)
| Código | Descripción |
|--------|-------------|
| `1` | Factura electrónica |
| `4` | Autofactura electrónica |
| `5` | Nota de crédito electrónica |
| `6` | Nota de débito electrónica |
| `7` | Nota de remisión electrónica |

## Hoja de Ruta
1. [x] Configuración (`config/sifen.php`) y Migración `companies`.
2. [x] Implementación de Lógica de CDC (`SifenCdcService`) y QR (`SifenQrService`).
3. [x] Construcción del servicio de generación XML (`SifenXmlService`).
4. [x] Migración `branches`: `establishment_code`, `dispatch_point`, `timbrado_number`, `timbrado_start_date`.
5. [x] Pruebas unitarias del CDC (`tests/Unit/SifenCdcServiceTest.php` — 10 tests).
6. [ ] Integración de la firma digital (`robrichards/xmlseclibs` con certificado .p12).
7. [ ] Tests del `SifenXmlService` validando la estructura XML completa.

---
*Nota: Este plan fue generado y aprobado el 25/03/2026. Actualizado con mapeo completo el 26/03/2026.*
