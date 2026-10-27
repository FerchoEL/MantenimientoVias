# Modelo de datos — guía conceptual, verificar migraciones antes de generar

Referencias: [instrucciones](../AGENTS.md), [dominio](../.ai/rules/kpgroup-domain.md), [decisiones](decisiones-tecnicas.md), [plan operativo](../PLANS.md) y [estado](estado-proyecto.md).
Fuente: `fuentes/Documentacion_Proyecto_Mantenimiento_Vias_KPGroup_v1.1.pdf`, sección 7 (págs. 11–16) y 8 (pág. 18). Los tipos son **conceptuales**.

| Tabla | Atributos clave | Relaciones / restricciones |
|---|---|---|
| `clients` | id, name varchar(150), short_name varchar(80) nullable, active, timestamps | `hasMany(Plant)`; nombre corto opcional según aclaración aprobada posterior |
| `plants` | id, client_id, name varchar(150), short_name varchar(80) nullable, active, observations nullable | `belongsTo(Client)`; nombre único **dentro del cliente** según aclaración posterior |
| `tracks` | id, plant_id, name, code?, sketch_disk?, sketch_path?, active | `belongsTo(Plant)`; `hasMany(TrackSection)` |
| `track_switches` | id, plant_id, code, name, description?, active | `belongsTo(Plant)`; NO hijos |
| `track_sections` | id, track_id, name, code?, start_switch_id?, end_switch_id?, active | `belongsTo(Track)`; switches solo referencias |
| `maintenance_services` | id, plant_id, name, order_number?, start_date, end_date?, report_frequency, status, notes? | planta 1:N servicios |
| `maintenance_service_supervisors` | service_id, user_id, start_date, end_date?, active | asignaciones temporales N:M; no exclusividad |
| `maintenance_periods` | service_id, fechas, status, creador, envío, aprobación, cierre | servicio 1:N periodos |
| `maintenance_activity_categories/types` | categoría, concepto, subopción, unidad, location_type, inventory_behavior | conceptos jerarquizados |
| `maintenance_programs/items` | period_id, concepto, ubicación, fecha, asignado?, estado | programa por periodo |
| `maintenance_activities/photos` | period_id, concepto, ubicación, supervisor, fecha, estado, evidencia | fotos fuera de BD |
| `materials`, `plant_materials`, `inventory_movements` | producto; saldo por planta/material/condición; movimientos auditados | unique planta/material/condición; transacciones |
| `maintenance_period_inventories` | opening, entries, exits, adjustments, closing | resumen congelado |
| `maintenance_findings/photos` | fecha detección, criticidad, ubicación, resolución, evidencias | histórico inmutable |
| `maintenance_reports` + pivotes/revisiones | periodo único, folio único, selección, auditoría, pdf | PDF oficial después aprobación |

## MV-006 → MV-008: acuerdos posteriores confirmados
1. `clients.short_name` y `plants.short_name` son opcionales (nullable), confirmados por el responsable el 2026-10-26; ver [decisiones técnicas](decisiones-tecnicas.md).
2. Los nombres de planta son únicos dentro de un mismo cliente; distintas empresas pueden tener plantas con el mismo nombre. Definir explícitamente si comparación ignora mayúsculas/espacios de acuerdo con collation MariaDB real y documentar la decisión.
3. Desactivar un cliente conserva plantas y todos los registros históricos; bloquear nuevas operaciones bajo cliente inactivo sin desactivar plantas en cascada.
4. Mantener desactivación lógica (`active`) y relaciones históricas; no `ON DELETE CASCADE` para borrar histórico.
5. La lectura histórica autorizada se conserva. Clasificar las operaciones por módulo y resolver correcciones, cambios de cliente, reactivaciones y cierres en [decisiones técnicas](decisiones-tecnicas.md) antes de implementar la acción afectada; no inventar excepciones al bloqueo.

## Pruebas del bloque
- Cliente y planta con short_name nulo; planta vinculada a cliente.
- Unicidad planta dentro de cliente y duplicidad entre clientes permitida.
- Cliente inactivo no elimina ni desactiva plantas; impedir nuevas operaciones conforme regla aprobada.
- Integridad de foreign keys, manejo de cambios de cliente y autorización de recursos.
