# PLANS.md — plan operativo para Codex

## Meta de la integración de contexto (no es tarea funcional MV)
1. Auditar repositorio y versiones actuales en solo lectura.
2. Integración documental autorizada el 2026-10-26: sustituir el bootstrap por las instrucciones unificadas y conservar reglas compatibles; no instalar Boost ni implementar funcionalidades.
3. Proponer instalación compatible de Laravel Boost con `composer require laravel/boost --dev` y configuración Codex/MCP **solo tras aprobación**.
4. Leer [AGENTS.md](AGENTS.md), [reglas de dominio](.ai/rules/kpgroup-domain.md) y [decisiones técnicas](docs/decisiones-tecnicas.md). Verificar herramientas disponibles sin asumir que Boost/MCP están configurados. Estado de integración: [docs/estado-proyecto.md](docs/estado-proyecto.md).
5. Continuar MV-006 a MV-008 exclusivamente después de autorización separada.

## Plan de desarrollo funcional
- MV-001…MV-005: estado reportado en Gantt, comprobar con Git/código; MV-003 aparece `En proceso`.
- **MV-006:** diseño del esquema Client/Plant; verificar aclaraciones y esquema.
- **MV-007:** migraciones Client/Plant, índices, relaciones, restricciones sin pérdida de datos.
- **MV-008:** modelos Eloquent y relaciones, pruebas.
- MV-009…MV-010: Filament Resources para clientes/plantas; **no incluidos en MV-006…008**.
- Continuar con resto de MV conforme al Gantt, sujeto a aceptación/revisión.

## Contrato de ejecución por tarea
Antes: objetivo, fuentes consultadas, archivos previstos, dependencias y pruebas. Después: archivos tocados, comandos, resultados, capturas relevantes (sin secretos), riesgos, estatus (`No iniciado / En proceso / Terminado verificado / Bloqueado`). Las fechas planeadas son referencias, no pruebas de avance.
