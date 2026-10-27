# Paquete de contexto — instrucciones de aplicación

Este ZIP **no instala software ni modifica el servidor**. Contiene las fuentes originales, índices Markdown, reglas para Codex y un prompt de implementación segura.

## Integración vigente

La integración documental del 2026-10-26 está autorizada y aplicada. Consultar [AGENTS.md](AGENTS.md), [reglas de dominio](.ai/rules/kpgroup-domain.md), [PLANS.md](PLANS.md) y [estado](docs/estado-proyecto.md). Boost no se instala con esta integración. Los pasos siguientes son una guía para futuras importaciones, no una orden de repetirlas.

## Pasos
1. Revisar `git status --short` y preservar el contenido antes de una integración autorizada. No crear commits sin autorización explícita.
2. **IMPORTANTE: si ya existen `AGENTS.md`, `PLANS.md`, `docs/` o `.ai/rules/`, NO extraer encima a ciegas.** Descomprime el paquete en un directorio temporal y revisa diff/conflictos antes de copiar.
3. Alternativa recomendada:
   ```bash
   mkdir -p /tmp/kpgroup-contexto
   unzip KPGroup_Codex_Contexto_Laravel13.zip -d /tmp/kpgroup-contexto
   cd /var/www/html/mantenimiento-vias
   git status --short
   diff -u AGENTS.md /tmp/kpgroup-contexto/AGENTS.md  # solo si AGENTS.md ya existe
   ```
4. Copiar/combinar manualmente el contenido necesario conservando modificaciones anteriores. La carpeta `docs/fuentes/` lleva PDFs y Gantt (no contiene credenciales del servidor).
5. Abre Codex con el repositorio como directorio de trabajo y pega `PROMPT_CODEX_IMPLEMENTACION.md` (ETAPA 1). Espera su auditoría y aprueba explícitamente la ETAPA 2 solo tras revisarla.
6. Al validar Boost y MCP, asigna después MV-006…008 con el prompt posterior, **separado**.

## Boost (ejemplo, NO ejecutar sin auditoría/aprobación)
```bash
composer require laravel/boost --dev
php artisan boost:install
# Para MCP, consultar los comandos vigentes de Codex y del paquete instalado.
```
No correr `migrate:fresh`, `db:wipe`, migraciones con datos, ni seeders por esta integración. Filament y Spatie existentes deben permanecer intactos.

## Fuentes y precedencia
- Documentación v1.1: diseño, roles, atributos, flujos y criterios.
- Reglas de negocio: RN-001…026 y RC-001…035.
- Planificación + Gantt: hitos y estimaciones, **no evidencia de ejecución**.
- Aclaraciones posteriores confirmadas: short_name de cliente y planta opcionales, unicidad de planta por cliente, inactivación cliente sin cascada.
