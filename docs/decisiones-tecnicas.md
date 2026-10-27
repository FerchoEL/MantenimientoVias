# Decisiones, aclaraciones y preguntas abiertas

## Aprobadas como aclaraciones operativas posteriores (confirmadas en conversación del proyecto)
| Fecha | Tema | Decisión |
|---|---|---|
| 2026-10-26 | MV-006 Client y Plant | Ambos `short_name` opcionales (`nullable`), confirmados expresamente por el responsable |
| 2026-10-26 | MV-006 Plant | nombre único dentro del cliente, no global |
| 2026-10-26 | Desactivar cliente | conservar plantas e histórico; impedir nuevas operaciones; sin cascada |

Responsable: usuario responsable del proyecto. Referencia: autorización explícita de integración documental del 2026-10-26, puntos 4 y 5. Estatus: aprobado. Impacto futuro: ambos campos admiten NULL en migraciones, modelos, validación e interfaces; probar ambos casos. Esta integración solo registra el acuerdo, no cambia el esquema ni implementa funcionalidades.

Vínculos: [instrucciones](../AGENTS.md), [reglas de dominio](../.ai/rules/kpgroup-domain.md), [modelo](modelo-datos.md) y [plan](../PLANS.md).

## Establecidas en documentos fuente
- Core neutral y reutilizable para futura inspección, sin implementar inspección ahora.
- Track y TrackSwitch hermanos bajo Plant; TrackSection hijo de Track.
- Servicio-supervisor muchos a muchos temporal y simultáneo.
- Esteban aprueba reportes, autoriza materiales; permisos Spatie reforzados con políticas.
- Evidencias GCS en producción mediante Filesystem; DB solo metadatos.

## Pendientes fuente P-01 a P-06
| ID | Tema | Estado |
|---|---|---|
| P-01 | Subopciones de mantenimiento general | No definido |
| P-02 | Escala criticidad | No definido |
| P-03 | Límite exacto de fotos seleccionadas para PDF | No definido |
| P-04 | Formato definitivo folio | No definido; sugerencia `RMT-AAAA-######` NO aprobada |
| P-05 | Planta piloto/datos reales/croquis | No recibido |
| P-06 | Inventario inicial/materiales piloto | No recibido |

## Preguntas de implementación abiertas (NO decisiones aprobadas)
- Collation/caso/espacios y unicidad `plants.name` por cliente.
- Con `Client.active=false`, el bloqueo de nuevas operaciones y la conservación/lectura autorizada del histórico están aprobados. Falta clasificar por módulo las altas de plantas, servicios, periodos, actividades, hallazgos y movimientos; definir tratamiento de correcciones de registros existentes, cambios de cliente de una planta, reactivaciones y cierre/revisión/aprobación de reportes ya iniciados. No presumir excepciones ni ejecutar acciones afectadas sin resolver su clasificación. La opcionalidad de ambos `short_name` ya está resuelta.
- Límite físico de 6 evidencias por actividad mencionado en reuniones previas frente al documento que indica múltiples fotos: **verificar aprobación formal antes de imponer límite**.
- Diferencias `location_type=either/none` frente a RN-004.

Toda nueva decisión: fecha, responsable, referencia, efecto en base de datos/UI/pruebas y estatus de aprobación. No cambiar normas silenciosamente.
