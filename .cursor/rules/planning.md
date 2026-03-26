---
description: Planning workflow — document in Obsidian before writing code
alwaysApply: true
scope: workflow
---

# Planning Rules

## Regla Fundamental
**Nunca empezar a modificar código sin antes crear o actualizar el documento de plan en `Obsidian/`.**

Esta regla aplica a:
- Nuevas funcionalidades (cualquier tamaño)
- Cambios estructurales en modelos o base de datos
- Integración de servicios externos (SIFEN, pagos, etc.)
- Refactors que afecten más de 2 archivos

No aplica a:
- Corrección de bugs simples (1 archivo, lógica obvia)
- Actualización de labels/textos de UI
- Agregar campos a `$fillable` de un modelo existente

## Estructura del Documento de Plan

```markdown
# Plan de Implementación: [Nombre del Feature]

## Objetivos
(Qué se quiere lograr y por qué)

## Componentes a Desarrollar
(Lista de archivos/clases a crear o modificar)

## Enfoque Técnico
(Decisiones de arquitectura, algoritmos clave, dependencias)

## Mapeo de Datos (si aplica)
(Tabla de campos: origen en el sistema → destino en DB/XML/API)

## Hoja de Ruta
- [ ] Tarea 1
- [ ] Tarea 2
- [x] Tarea completada

---
*Nota: Fecha de creación y última actualización*
```

## Planes Existentes

| Archivo | Feature | Estado |
|---------|---------|--------|
| `Plan_Sifen_XML.md` | Generación XML SIFEN v150 | En progreso |
| `Plan_Kendall_Kendall.md` | Compliance Kendall & Kendall | ✅ Completado |
| `Plan_IVA_SIFEN.md` | Cálculo IVA desagregado para SIFEN | Pendiente |
| `Plan_Flujo_Caja.md` | Caja obligatoria en ventas | Pendiente |
| `Plan_Plantillas_Impresion.md` | Templates de PDF para comprobantes | Pendiente |
| `Plan_Sifen_Fiscalizacion_Inventario.md` | Fiscalización de inventario SIFEN | Pendiente |
| `Plan_Navegacion_Atajos.md` | Atajos de teclado en POS | Pendiente |
| `Plan_Correccion_Venta_Stock.md` | Correcciones de stock en ventas | ✅ Completado |
| `Plan_Fix_Receipt_Purchase.md` | Correcciones de comprobantes compra | ✅ Completado |
| `Plan_SoftDeletes_Auditoria_Mejoras.md` | SoftDeletes y auditoría | ✅ Completado |
| `Plan_Mantenimiento_Capacitacion.md` | Plan de mantenimiento y capacitación | ✅ Completado |
| `Deploy_Produccion.md` | Guía de deploy a producción | ✅ Completado |

Consultar estos documentos antes de trabajar en las áreas relacionadas.

## Convención de Nombres

Los archivos en `Obsidian/` siguen el patrón:
- `Plan_[Feature].md` para planes de implementación
- `Manual_[Rol].md` para manuales de usuario
- `Arquitectura_[Componente].md` para documentación técnica
- `Errores_[Tema].md` para referencia de errores conocidos

## Relación con Commits
Los commits de nuevas features deben referenciar el plan:
```
feat(sifen): implement CDC generation service

Based on Plan_Sifen_XML.md — Phase 2.
Implements 44-digit CDC with modulo-11 check digit.
```
