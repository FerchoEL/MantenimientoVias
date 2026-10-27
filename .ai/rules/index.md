# Índice de reglas KP Group

Este índice complementa [AGENTS.md](../../AGENTS.md) y permite localizar las reglas
que pide consultar el bloque gestionado de Laravel Boost.

| Rutas / globs | Regla aplicable |
|---|---|
| `**/*` (todo el proyecto, incluida documentación y configuración de IA) | [kpgroup-domain.md](kpgroup-domain.md) |

Las restricciones de ejecución y autorización están en [AGENTS.md](../../AGENTS.md).
Las decisiones aprobadas y pendientes están en [docs/decisiones-tecnicas.md](../../docs/decisiones-tecnicas.md).
No hay directrices de framework separadas en `.ai/rules/boost/`: están en el bloque
`<laravel-boost-guidelines>` de AGENTS.md. No asumir que esa carpeta existe.

Antes de modificar, leer las reglas coincidentes y buscar términos relevantes en
`.ai/rules/` con `rg -n -i`. La disponibilidad de Boost no amplía la autorización
ni permite ejecutar migraciones, seeders o escrituras de datos por sí sola.
