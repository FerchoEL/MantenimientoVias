# Modelo de datos — guía conceptual, verificar migraciones antes de generar

Referencias: [instrucciones](../AGENTS.md), [dominio](../.ai/rules/kpgroup-domain.md), [decisiones](decisiones-tecnicas.md), [plan operativo](../PLANS.md) y [estado](estado-proyecto.md).
Fuente: `fuentes/Documentacion_Proyecto_Mantenimiento_Vias_KPGroup_v1.1.pdf`, sección 7 (págs. 11–16) y 8 (pág. 18). Los tipos son **conceptuales**.

| Tabla | Atributos clave | Relaciones / restricciones |
|---|---|---|
| `clients` | id, name varchar(150), short_name varchar(80) nullable, active, timestamps | `hasMany(Plant)`; nombre corto opcional según aclaración aprobada posterior |
| `plants` | id, client_id, name varchar(150), short_name varchar(80) nullable, active, observations nullable | `belongsTo(Client)`; nombre único **dentro del cliente** según aclaración posterior |
| `tracks` | id, plant_id, name varchar(120), code varchar(50)?, sketch_disk varchar(40)?, sketch_path varchar(255)?, active, timestamps | `Plant hasMany Track`; `Track belongsTo Plant`; unique `(plant_id, name)` y `(plant_id, code)` (code nullable); `hasMany(TrackSection)` |
| `track_switches` | id, plant_id, code varchar(50), name varchar(120), description?, active, timestamps | `Plant hasMany TrackSwitch`; `TrackSwitch belongsTo Plant`; unique `(plant_id, code)`; no tiene hijos ni relación con Track |
| `track_sections` | id, track_id, name varchar(120), code varchar(50)?, active, timestamps | unique `(track_id, name)` y `(track_id, code)` (code nullable); solo `belongsTo(Track)`; nombre operativo manual |
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
5. La lectura histórica autorizada se conserva. La creación de nuevas plantas para clientes inactivos queda bloqueada en esta fase por decisión expresa. Clasificar las demás operaciones por módulo y resolver correcciones, cambios de cliente, reactivaciones y cierres en [decisiones técnicas](decisiones-tecnicas.md) antes de implementar la acción afectada.

## Pruebas del bloque
- Cliente y planta con short_name nulo; planta vinculada a cliente.
- Unicidad planta dentro de cliente y duplicidad entre clientes permitida.
- Cliente inactivo no elimina ni desactiva plantas; impedir nuevas operaciones conforme regla aprobada.
- Integridad de foreign keys, manejo de cambios de cliente y autorización de recursos.

## MV-006 — correspondencia del diseño implementado

Fuente original contrastada: documentación v1.1, sección 7 (clientes/plantas,
página 11); secciones 3 y 18 para nombres neutrales, 6 para RF-001/RF-002,
8 para Client hasMany Plant / Plant belongsTo Client, 16 para AC-12 y 17 para
integridad referencial. La sección 9 define autorización para los módulos futuros;
no se implementan permisos ni interfaces en este bloque.

El original describe tipos conceptuales y no explicita nullable en ambos nombres
cortos ni unicidad de planta por cliente; esas decisiones proceden de las
aclaraciones posteriores y de la solicitud de implementación, no del PDF.

- clients: id BIGINT UNSIGNED PK; name VARCHAR(150) NOT NULL;
  short_name VARCHAR(80) NULL; active BOOLEAN NOT NULL DEFAULT true; timestamps.
- plants: id BIGINT UNSIGNED PK; client_id BIGINT UNSIGNED NOT NULL;
  name VARCHAR(150) NOT NULL; short_name VARCHAR(80) NULL;
  active BOOLEAN NOT NULL DEFAULT true; observations TEXT NULL; timestamps.
- FK plants.client_id → clients.id con ON DELETE RESTRICT;
  índice único plants(client_id, name). Sin índices redundantes ni columnas extra.
- Modelos App\Models\Client y App\Models\Plant: Fillable explícito siguiendo User,
  casts() boolean para active, HasFactory, HasMany/BelongsTo tipados.
- Defaults aplicados por la BD; recargar un modelo recién creado si se necesita
  observar un valor omitido que procede del default de la tabla.
- Validación de cliente inactivo centralizada en creating de Plant; justificación
  y límites documentados en decisiones-tecnicas.md. No se filtra globalmente el
  histórico ni se alteran plantas cuando cambia Client.active.

La estructura generada no acredita ejecución en MariaDB: ver el estado MV-007
parcial y las validaciones SQLite/MariaDB en [estado](estado-proyecto.md).

## MV-011/MV-012 — vías y cambios (29-10-2026)

Fuente contrastada: documentación funcional v1.1, secciones 3 (jerarquía), 4
(RN-005 y RN-026), 6 (RF-003 a RF-005), 7 (atributos), 8 (relaciones) y 9
(matriz de autorizaciones). Alcance implementado solo para Track y TrackSwitch;
TrackSection, interfaces y permisos específicos quedan fuera de este bloque.

- `tracks`: `id` BIGINT UNSIGNED PK; `plant_id` BIGINT UNSIGNED NOT NULL con FK
  a `plants.id` y `ON DELETE RESTRICT`; `name` VARCHAR(120) NOT NULL; `code`
  VARCHAR(50) NULL; `sketch_disk` VARCHAR(40) NULL; `sketch_path` VARCHAR(255)
  NULL; `active` BOOLEAN NOT NULL DEFAULT true; timestamps.
- `track_switches`: `id` BIGINT UNSIGNED PK; `plant_id` BIGINT UNSIGNED NOT NULL
  con FK a `plants.id` y `ON DELETE RESTRICT`; `code` VARCHAR(50) NOT NULL;
  `name` VARCHAR(120) NOT NULL; `description` TEXT NULL; `active` BOOLEAN NOT
  NULL DEFAULT true; timestamps.
- Las FKs a `plants.id` proporcionan sus índices; además, `tracks` tiene unique
  `(plant_id, name)` y `track_switches` unique `(plant_id, code)`, conforme a la
  aprobación funcional del 2026-10-29. No se modifica automáticamente ningún
  código. No existe `track_id` en `track_switches`.
- Modelos tipados `Track::plant()`, `TrackSwitch::plant()`, `Plant::tracks()` y
  `Plant::trackSwitches()`; factories asignan una planta por defecto. `active`
  se castea a boolean. No hay SoftDeletes ni carga de croquis.
- Ajuste aprobado el 2026-11-03: el código opcional de Track también es único
  por Planta cuando no es `NULL`, mediante una migración incremental que conserva
  el índice `(plant_id, name)`. Se permiten varios códigos `NULL` por Planta y
  códigos iguales en Plantas distintas; la validación de edición ignora la vía
  actual.

## MV-013/MV-014 — tramos (diseño inicial sustituido el 02-11-2026)

Fuente contrastada: documentación funcional v1.1, secciones 3, 4 (RN-005/RN-026),
6 (RF-003 a RF-005), 7 y 8; aclaraciones funcionales aprobadas en la solicitud
Semana 02.

- Diseño inicial del 29-10-2026: el esquema incluía referencias opcionales a
  cambios de vía. La aclaración funcional del responsable del 02-11-2026 sustituye
  esa parte: el tramo pertenece solo a Track; el nombre es un identificador manual
  que puede ser, por ejemplo, `C1-C2`, `C1-V2` o `C4-Patio`. No se interpreta ni
  genera relaciones con cambios, vías nombradas o lugares en sus extremos.
- Esquema vigente: `id` BIGINT UNSIGNED PK; `track_id` BIGINT UNSIGNED NOT NULL;
  `name` VARCHAR(120) NOT NULL; `code` VARCHAR(50) NULL; `active` BOOLEAN NOT NULL
  DEFAULT true; timestamps. Unique `(track_id, name)` y, mediante migración
  incremental, `(track_id, code)`; FK `track_id → tracks.id` con
  `ON DELETE RESTRICT`. Código opcional, único dentro de la Vía cuando se
  proporciona; se permiten múltiples NULL y reutilización en otras Vías.
- `Track::sections()` y `TrackSection::track()` son las relaciones Eloquent.
  `start_switch_id` y `end_switch_id` fueron retirados por una migración correctiva;
  no forman parte del esquema vigente.
- En creación se exige cliente, planta y vía activos. La baja de esos catálogos no
  desactiva ni elimina tramos ni bloquea sus ediciones generales.
- Administración disponible por dos interfaces con el mismo modelo y Policy:
  `SectionsRelationManager` dentro de Editar Vía y el catálogo independiente
  Tramos, donde se elige la Vía; en edición la propietaria permanece fija.
- La inactividad bloquea altas nuevas, pero no desactiva registros relacionados;
  las ediciones no reciben un bloqueo general. Sin SoftDeletes ni campos de
  kilometraje, longitud física, numeración o coordenadas.
