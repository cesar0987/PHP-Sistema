# Plan de Implementación: Navegación y Atajos de Teclado (Shortcuts)

## Objetivo
Agilizar el uso del Sistema POS (especialmente para los cajeros) permitiendo la navegación fluida entre los módulos principales sin necesidad de usar el mouse. Esto reduce los tiempos de transacción y mejora la ergonomía del usuario.

## Atajos Globales Propuestos

Estos atajos funcionarán desde **cualquier pantalla** del sistema (Panel Administrativo de Filament). Se basan en combinaciones con la tecla `Alt` para evitar conflictos con atajos nativos del navegador web (como `Ctrl+P` para imprimir navegador, o `Ctrl+T` para nueva pestaña).

| Acción / Módulo Destino | Atajo de Teclado | Notas / Justificación |
| :--- | :--- | :--- |
| **Buscador Global** | `Ctrl + K` *(Nativo)* | Ya existe en Filament. Abre la barra de búsqueda rápida. |
| **Ir a Nueva Venta (POS)** | `Alt + V` | **V**enta. Salto directo al formulario de cobro. |
| **Ir a Caja Registradora** | `Alt + C` | **C**aja. Para ver el estado del turno, cierres o imprimir reportes. |
| **Ir a Inventario (Productos)** | `Alt + I` | **I**nventario / Productos. Para consultas rápidas de precios. |
| **Ir a Tomas Físicas** | `Alt + F` | **F**iscalización. |

## Atajos Locales (Dentro de "Nueva Venta")
*Nota: Estos requieren inyectar Alpine.js directo en el formulario de la Venta (SaleResource).*
* `Enter` al estar sobre la búsqueda de un producto: Agrega el primer resultado automáticamente al carrito.
* `F12`: Simula el click en "Guardar/Completar" para finalizar la transacción rápidamente.

## Estrategia Técnica de Implementación en Filament v3

Para lograr estos atajos globales sin modificar el núcleo de Filament, aplicaremos el siguiente enfoque:

1. **Crear un archivo JavaScript personalizado (Theme/Script):**
   * Crearemos un archivo en `public/js/shortcuts.js`.
   * Este archivo contendrá un Event Listener puro en JavaScript: `document.addEventListener('keydown', function(event) { ... })`.
   * El script evaluará la combinación de teclas presionada (`event.altKey && event.key === 'v'`).

2. **Inyectar el Script Globalmente:**
   * Registraremos el script en la configuración del proveedor del panel (`app/Providers/Filament/AdminPanelProvider.php`).
   * Usaremos el método `->scripts([ asset('js/shortcuts.js') ])` o inyectaremos mediante un *Render Hook* (`PanelsRenderHook::BODY_END`).

3. **Manejo de Rutas Base:**
   * Dado que el dominio o la carpeta base puede cambiar en un servidor de producción vs local, los atajos no tendrán código "quemado" (hardcoded). El JS leerá el prefijo de la URL o simplemente navegará a `/admin/sales/create`, extrayendo la URL base del window location de forma segura.

## Manual de Uso para el Usuario Final (Documentación)
Una vez implementado, se agregará un anexo visual en la vista de *Dashboard* o se integrará en el archivo `Manual_Operaciones_Inventario.md` especificando la tabla de comandos y recomendaciones para el operador de caja.

---
**¿Estás de acuerdo con estas teclas asignadas o prefieres cambiarlas (por ejemplo, usar F1, F2... en vez de Alt)?**
*(Recuerda que algunas teclas F, como F5, recargan la página nativamente en los navegadores).*
