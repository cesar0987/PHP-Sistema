---
title: "Plan Refactor - Desacoplamiento de ReceiptService"
status: "Completado"
date: "2026-03-26"
---

# Refactor: Purificación del `ReceiptService`

Este documento describe la solución aplicada a las **Violaciones C2 y C3 de Clean Architecture** en el generador de comprobantes y PDFs, que consistían en la emisión directa de respuestas HTTP y el uso no explícito de localizadores de dependencias.

## 1. El Problema Original

El `ReceiptService` sufría de dos patologías graves de diseño:

1. **Acoplamiento al Protocolo de Transporte (C2):** Sus métodos `downloadPdf()` y `streamPdf()` retornaban objetos de la clase `Illuminate\Http\Response`. Por diseño, un Servicio o Caso de Uso no debe saber que existe algo llamado HTTP, Response, Status 200, Web, etc. Su única labor es procesar y retornar datos puros (un objeto, un booleano, un binario o ruta del archivo).
2. **Uso de Service Locators (C3):** En lugar de declarar explícitamente qué componentes externos necesitaba, invocaba mágicamente la función `app(SifenCdcService::class)`. Esto oculta las dependencias reales del servicio e imposibilita la inyección por constructor, dificultando las pruebas y el mantenimiento a largo plazo.

## 2. Los Cambios Implementados

### A. Eliminación del Anti-patrón "Service Locator" (C3)
En lugar de depender de `app()`, el constructor del `ReceiptService` se modificó para recibir explícitamente sus dependencias:

```php
public function __construct(
    private SifenCdcService $sifenCdcService,
    private SifenQrService $sifenQrService,
) {}
```
Esta sencilla inversión de control permite que un framework en el Adaptador inyecte instancias falsas o mocks si fuere necesario, y hace trivial leer el código para saber en qué otros servicios confía `ReceiptService`.

### B. Extracción de Lógica de Respuesta HTTP (C2)
- Se eliminaron los métodos `downloadPdf` y `streamPdf` de la clase `ReceiptService`.
- Se transfirió la responsabilidad a la capa Adaptador (en este caso, los Actions de Filament y los Controladores dedicados), quienes ahora simplemente piden el contenido binario o streamable al servicio base mediante `generatePdf()`, y luego construyen localmente el `response()->download(...)`.
- El servicio en sí mismo ya no importa o retorna objetos de `Illuminate\Http\Response`.

## 3. Resultado y Beneficios
El `ReceiptService` ahora es 100% agnóstico del medio a través del cual el usuario recibe el archivo. Se puede usar un Job programado para generar masivamente PDFs en la madrugada y enviarlos por correo, sin lidiar con los errores de que se retorne una respuesta web HTTP en un entorno de CLI o consola.
