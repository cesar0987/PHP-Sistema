---
description: Global AI behavior rules — always apply to every interaction
alwaysApply: true
scope: ai-agent
---

# AI Agent — Reglas Globales

## Idioma y Comunicación
- Responder **siempre en español** al usuario.
- El código fuente PHP, nombres de variables y comentarios técnicos van en **inglés**.
- Los mensajes de usuario (labels, notificaciones, strings de UI) van en **español**.

## Antes de Modificar Código
1. **Leer** el archivo antes de editarlo. Nunca editar a ciegas.
2. **Verificar** que la feature no esté ya implementada en otro servicio o modelo.
3. **Consultar** `Obsidian/` si el tema tiene un plan previo.
4. **Crear el plan** en `Obsidian/` si la tarea es significativa (ver `planning.md`).

## Restricciones Absolutas
- Nunca introducir dependencias de Composer sin justificación explícita.
- Nunca hacer `git push --force` ni `git reset --hard` sin confirmación.
- Nunca escribir lógica de negocio fuera de la capa de servicios (`app/Services/`).
- Nunca modificar stock directamente — siempre usar `InventoryService`.
- Nunca hacer hard delete de modelos que usan `SoftDeletes`.
- Nunca omitir `DB::transaction()` en operaciones multi-tabla.

## Calidad de Código
- Preferir editar un archivo existente antes de crear uno nuevo.
- No agregar docblocks, comentarios ni type hints en código que no se está modificando.
- No agregar manejo de errores para escenarios que no pueden ocurrir.
- No crear helpers o abstracciones para operaciones que se usan una sola vez.
- Siempre seguir las convenciones de commit (ver `git.md`).
