# Prompt para Codex — integración del contexto y Laravel Boost

Estado de referencia: la integración documental está autorizada; instalar Boost e implementar MV-006…MV-008 requieren autorización separada. Este prompt almacenado no concede permisos. Consultar [AGENTS.md](AGENTS.md), [dominio](.ai/rules/kpgroup-domain.md), [PLANS.md](PLANS.md) y [estado](docs/estado-proyecto.md).

Copia y pega el siguiente texto **en la sesión Codex abierta en** `/var/www/html/mantenimiento-vias`, después de copiar/descomprimir los archivos del paquete y revisar posibles conflictos.

---

Actúa como desarrollador senior Laravel 13 y auditor del proyecto KP Group Mantenimiento de Vías.

**ETAPA 1 — AUDITORÍA DE SOLO LECTURA. NO MODIFIQUES NADA todavía.**
1. Revisa `AGENTS.md`, `PLANS.md`, los Markdown bajo `docs/`, `.ai/rules/kpgroup-domain.md` y sus fuentes PDF/XLSX si puedes leerlas. Indica documentos que no hayas podido leer.
2. Inspecciona el proyecto actual, Git (estado, rama, HEAD), PHP, Composer, Laravel, Filament, Spatie, migrations, modelos, seeders, policies, pruebas, Boost y cualquier configuración Codex/MCP preexistente. No leas valores secretos de `.env`.
3. Comprueba que las sustituciones estén autorizadas: el bootstrap de `AGENTS.md` fue sustituido mediante la integración documental aprobada del 2026-10-26. Si detectas reemplazos adicionales no autorizados, propone conciliación. Identifica cambios no confirmados en Git.
4. Contrasta MV-001…MV-008 del Gantt con evidencia real. Presta atención a `Client`, `Plant`, campos y unicidad, cliente desactivado e integridad histórica.
5. Valida compatibilidad/documentación oficial disponible de `laravel/boost` con la versión instalada; confirma comandos exactos y si la versión de Codex disponible admite MCP. Si no hay acceso a documentación oficial, deja la validación como pendiente.
6. Devuelve un informe con hallazgos, riesgos, archivos a modificar, comandos propuestos y verificaciones. **Detente y solicita mi aprobación textual antes de instalar paquetes, cambiar archivos o escribir en BD.**

**ETAPA 2 — SOLO CUANDO APRUEBE EXPRESAMENTE:**
- Incorpora reglas/contexto en los archivos existentes, sin sobrescritura destructiva ni duplicar instrucciones contradictorias.
- Instala `laravel/boost` como dependencia dev si la compatibilidad está confirmada y autoricé instalación. Configura integración Codex/MCP con los métodos soportados por esa versión; no inventes comandos ni habilites operaciones de escritura en BD.
- Si el instalador modifica `AGENTS.md`, haz diff y conserva nuestros mandatos. No revelar ni versionar secretos.
- Confirma que las herramientas de consulta funcionen y que la documentación v1.1 y RN/RC sea accesible. Ejecuta pruebas no destructivas y presenta resultados.
- Actualiza solo `docs/estado-proyecto.md` con evidencia verificable, sin tocar los originales en `docs/fuentes/` ni el Gantt. Propón commits, no hagas commit/push hasta autorización.
- **NO implementar MV-006…MV-008 en esta etapa** salvo que te lo ordene explícitamente en otro mensaje.

Formato del resultado: `Auditoría`, `Conflictos`, `Plan de cambios`, `Pruebas/resultado`, `Archivos tocados`, `Pendientes y autorizaciones`.
---

## Prompt posterior (independiente) para MV-006…MV-008
Una vez integrada y aprobada la infraestructura de contexto:

> Implementa únicamente MV-006, MV-007 y MV-008 conforme a `AGENTS.md`, `docs/modelo-datos.md`, `docs/decisiones-tecnicas.md` y los documentos fuente. Primero comprueba el estado del repositorio y migraciones existentes. Diseña `Client` y `Plant`, con `Client.short_name` y `Plant.short_name` opcionales, nombre de planta único por cliente y desactivación de cliente sin cascada, preservando plantas e histórico e impidiendo nuevas operaciones. No inventes definiciones de bloqueo sobre registros existentes: presenta alternativas si falta precisión. Propón migraciones, modelos, relaciones y pruebas. No implementes recursos Filament MV-009/MV-010. Antes de migrar una BD con datos, solicita mi autorización. Reporta cambios y evidencia y actualiza `docs/estado-proyecto.md`.
