# Reglas de negocio — índice operativo (no sustituye el PDF)

Referencias: [instrucciones](../AGENTS.md), [dominio](../.ai/rules/kpgroup-domain.md), [decisiones](decisiones-tecnicas.md), [plan operativo](../PLANS.md) y [estado](estado-proyecto.md).
Fuentes: `fuentes/Reglas_Negocio_Mantenimiento_Vias_KPGroup.pdf` (RN-001 a RN-026, RC-001 a RC-035) y documentación funcional v1.1. Para implementación leer el texto original de cada regla.

## RN-001…RN-008 — servicios/actividades
- RN-001 asignaciones de supervisores simultáneas y con vigencias superpuestas.
- RN-002 frecuencia semanal/quincenal/mensual/sin reporte.
- RN-003 toda actividad, hallazgo y movimiento asociado a periodo vigente.
- RN-004 ubicaciones tramo o cambio conforme al concepto.
- RN-005 vía con tramos; cambios de inicio/fin pueden ser referencias.
- RN-006 programada o desviación por cliente/supervisor.
- RN-007 ejecución multidiaria.
- RN-008 evidencia de toda actividad completada; solo fotos seleccionadas en PDF.

## RN-009…RN-017 — inventario
- RN-009 salida nuevo reduce saldo nuevo; RN-010 recobro solo si reutilizable.
- RN-011 sustitución: salida nuevo + entrada recobro opcional independiente.
- RN-012 instalación de faltante: salida sin recobro.
- RN-013 saldo por planta/material/condición; RN-014 cierre final a apertura siguiente periodo del mismo servicio/planta.
- RN-015 trazabilidad de actor/fecha/material/condición/cantidad/actividad opcional.
- RN-016 alta y habilitación solo Esteban/Admin; RN-017 supervisor mueve existentes autorizados.

## RN-018…RN-026 — hallazgos/reportes/histórico
- RN-018 fecha de detección original; RN-019 fecha resolución separada.
- RN-020 reporte interno contiene toda operación; RN-021 selección editorial de Esteban; RN-022 exclusiones no borran histórico.
- RN-023 folio único por corte estable durante correcciones; RN-024 aprobación de Esteban oficializa PDF.
- RN-025 sin stock negativo por defecto y ajustes autorizados; RN-026 catálogos históricos se desactivan sin borrar.

## RC — puntos de control a revisar en PDF
- RC-001…RC-007: estructura, vigencias, permisos por servicio, conceptos/programa y ubicación.
- RC-008…RC-015: productos, autorización por planta, saldos, transacciones, correcciones y corte congelado.
- RC-016…RC-021: fotografías, GCS, selección, hallazgos y criticidad pendiente.
- RC-022…RC-028: un reporte/periodo, reenvíos, croquis, PDF, aprobación, folio pendiente.
- RC-029…RC-035: estados, auditoría, restricciones, integridad y autorización.

## Criterios AC
AC-01 acceso por asignación; AC-02 evidencia actividad completada; AC-03 ubicaciones; AC-04 no negativos; AC-05 permisos alta materiales; AC-06 recobro independiente; AC-07 fecha original hallazgo; AC-08 traslado inventario; AC-09 selección no borra histórico; AC-10 folio fijo; AC-11 PDF persistente; AC-12 no borrado físico catálogos relacionados.

**No inventar pendientes:** P-01 subopciones, P-02 criticidad, P-03 límite fotos PDF, P-04 folio definitivo, P-05 planta piloto, P-06 inventario piloto. RC-007 indica tensión entre `either`/`none` del catálogo y RN-004: escalar antes de implementar.
