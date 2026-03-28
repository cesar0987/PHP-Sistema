# Manual: Sistema de Backups y Ajustes Generales

Este documento explica cómo gestionar las copias de seguridad del sistema y la configuración general a través de la interfaz de usuario.

## 1. Configuración General

El panel de **Ajustes Generales** (ubicado en el menú **Administración**) permite centralizar el comportamiento global del TPV.

### Parámetros Disponibles:
- **Nombre del Sistema:** Cambia el título que se muestra en la pestaña del navegador y en la marca del panel.
- **Frecuencia de Backup:** Define cada cuánto tiempo el sistema intentará realizar un respaldo automático (Diario, Cada 12h, Cada 6h, Horario).
- **Hora del Backup Diario:** Si la frecuencia es "Diario", aquí defines a qué hora se dispara el proceso (ej. `03:00` para la madrugada).

---

## 2. Gestión de Backups (Copias de Seguridad)

La sección **Backups** permite supervisar y ejecutar manualmente el respaldo de la base de datos y los archivos cargados.

### Funcionalidades:
- **Crear Backup:** Inicia un proceso inmediato de respaldo.
- **Historial:** Lista de todos los archivos `.zip` generados, indicando su tamaño y fecha.
- **Acciones:** Puedes **Descargar** el archivo a tu computadora o **Eliminar** copias antiguas para liberar espacio.

### Nota Técnica para PostgreSQL (Producción):
Para que los backups de base de datos funcionen en producción, el servidor debe tener instalado el binario `pg_dump`. 
Si el sistema no lo detecta automáticamente, asegúrate de configurar la ruta en tu archivo `.env`:
`DB_DUMP_BINARY_PATH="/usr/bin/"`

---

## 3. Automatización (Cron Job)

El sistema utiliza el scheduler de Laravel. Para que la frecuencia configurada en la interfaz funcione, el servidor debe tener esta tarea cron activa:

`* * * * * cd /ruta-a-tu-proyecto && php artisan schedule:run >> /dev/null 2>&1`

---
**Documento generado por Antigravity AI**
*Fecha: 2026-03-28*
