# KP Group — Contexto técnico y funcional para Codex

**Proyecto:** Sistema de Mantenimiento de Vías (MVP)  
**Documento base:** `Documentacion_Proyecto_Mantenimiento_Vias_KPGroup_v1.1`, versión 1.1, 19/10/2026  
**Inicio planificado:** 22/10/2026 · **Entrega objetivo:** 22/01/2027  
**Propósito de este archivo:** conservar decisiones, restricciones y puntos de control para el agente de desarrollo. **No sustituye la documentación original ni los archivos existentes del repositorio.**

Referencias vigentes: [AGENTS.md](AGENTS.md), [reglas de dominio](.ai/rules/kpgroup-domain.md), [PLANS.md](PLANS.md), [decisiones técnicas](docs/decisiones-tecnicas.md) y [estado](docs/estado-proyecto.md). El responsable confirmó el 2026-10-26 que ambos `short_name` son opcionales; la discrepancia anterior queda resuelta. Los estados de implementación siguientes son históricos y no cambian por esta integración.

## 1. Jerarquía de autoridad y procedimiento

1. La documentación funcional/técnica v1.1 es la fuente principal de atributos, flujos y alcance; las reglas de negocio y la planificación complementan sus detalles.
2. Las **decisiones confirmadas del usuario** de la sección 5 de este archivo complementan el diseño de Clientes y Plantas.
3. Antes de trabajar, inspeccionar código, migraciones, configuración y pruebas reales. Este resumen contiene un **estado histórico reportado**, que puede haber cambiado desde la auditoría.
4. Si una decisión falta, existe discrepancia entre documentos o una operación tiene impacto destructivo, **informar y pedir decisión** antes de implementarla. No inventar requisitos.
5. Mantener los identificadores **MV-001 a MV-010 del cronograma**, sin renumerarlos con las categorías usadas por herramientas de auditoría.
6. Trabajar en una actividad o conjunto pequeño a la vez; resumir cambios, pruebas, bloqueos y relación con la planificación. No hacer commit sin autorización.

## 2. Objetivo y límites del MVP

El Supervisor captura una sola vez la operación de mantenimiento; Esteban (Jefe de supervisores) revisa, selecciona el contenido y aprueba un reporte PDF del periodo. El sistema mantiene el histórico completo, inventario por planta, hallazgos, evidencias y folios de cortes. Una **planta piloto** debe ejecutar todo el ciclo sin reconstruir manualmente un PowerPoint (documentación §1.2, §21).

**Fuera del MVP:** herramientas, facturación, cotizaciones, órdenes de compra, portal del cliente, firma electrónica, aplicación móvil nativa, GIS/GPS, mapas interactivos, planificación Gantt como función de la app, ERP de almacén y gestión integral de RR. HH. (§1.3). El Excel/Gantt administrativo de seguimiento del desarrollo es externo a este alcance.

## 3. Stack, repositorio y estado verificado previamente

- Código: `/var/www/html/mantenimiento-vias`; base de datos de desarrollo `kp_mantenimiento_vias`.
- Laravel **13.38.0**, PHP **8.3.30**, Filament **5.10.3**, Spatie Permission **8.3.2**, MariaDB **10.11.14**, Composer **2.10.3** (datos de auditoría compartidos; reconfirmar versiones instaladas al ejecutar).
- Filament panel `admin`, rutas `/admin`, `/admin/login`, `/admin/logout`.
- Roles: `Administrador`, `Jefe de supervisores`, `Supervisor`. Permisos iniciales: **25**, guard `web`; administrador y jefe 25 cada uno, supervisor 15 según el seeder auditado. La asignación de administrador a un usuario se comprobó; **no se ha probado aún login HTTP ni denegación por roles**.
- Modelo `User` contiene `HasRoles`; en la auditoría aún **faltaban** `FilamentUser` y `canAccessPanel()`.
- Migraciones iniciales y de Spatie ejecutadas; **no existían** aún `clients`, `plants`, modelos correspondientes ni Resources, según última auditoría.
- Git rama `main`, commits `2a01bc2` (instalación inicial) y `1add16f` (`RolePermissionSeeder`). Última auditoría reportó árbol limpio. No se confirmó remoto.
- **No usar este estado histórico como prueba de finalización actual**; auditar `git status`, rutas y archivos primero.

## 4. Modelo conceptual y reglas estructurales invariables

**Client → Plant → (Track y TrackSwitch como hermanos). Track → TrackSection.**

- `Client hasMany Plant`; `Plant belongsTo Client`.
- `Plant hasMany Track`; `Plant hasMany TrackSwitch` directamente; los cambios **NO** son hijos de la vía.
- `Track hasMany TrackSection`; un tramo puede referenciar `start_switch_id` y `end_switch_id` como delimitadores físicos, sin alterar la jerarquía.
- Entidades maestras de nombre neutral: `Client`, `Plant`, `Track`, `TrackSwitch`, `TrackSection`, `User` para futura integración con Inspección de Vías. **No crear `maintenance_plants` ni `maintenance_tracks`** (§3, §8, §18).
- `MaintenanceService` pertenece a una planta; las asignaciones User–Service tienen vigencias y **se pueden superponer** (RN-001). No imponer un supervisor exclusivo por planta ni por servicio.
- `MaintenancePeriod` representa un corte semanal/quincenal/mensual o sin reporte; actividades, hallazgos y movimientos pertenecen a periodo vigente (RN-002, RN-003).

## 5. MV-006 — Clientes y Plantas (decisiones confirmadas)

### Diseño de `clients` — documentación §7, p. 11

| Columna | Tipo objetivo | Nota |
| --- | --- | --- |
| `id` | bigint unsigned, PK | `id()` |
| `name` | varchar(150) | Obligatorio |
| `short_name` | varchar(80), nullable | **Opcional, confirmado por usuario** |
| `active` | boolean, default true | Activación/desactivación lógica |
| `created_at`, `updated_at` | timestamps | Auditoría básica |

### Diseño de `plants` — documentación §7, p. 11

| Columna | Tipo objetivo | Nota |
| --- | --- | --- |
| `id` | bigint unsigned, PK | `id()` |
| `client_id` | FK, no nulo | Referencia a `clients.id` |
| `name` | varchar(150) | Obligatorio |
| `short_name` | varchar(80), nullable | **Opcional, confirmado** |
| `active` | boolean, default true | Estado propio de planta |
| `observations` | text, nullable | Observaciones |
| `created_at`, `updated_at` | timestamps | Auditoría básica |

### Reglas acordadas adicionales (no atribuirlas literalmente al PDF)

- **Planta única por cliente:** no se permite duplicar `plants.name` dentro del mismo `client_id`. Sí se permite repetir el nombre con otro cliente. Se propone índice compuesto `unique(client_id, name)` y validación equivalente en futuras interfaces. Su interpretación de mayúsculas/acentos sigue la colación seleccionada; no aplicar un sistema de normalización adicional sin autorización.
- **Desactivación sin cascada:** cambiar `clients.active` a `false` **no altera** automáticamente `plants.active` ni registros históricos.
- **Cliente inactivo:** se permite consultar información histórica autorizada, pero no **crear nuevas operaciones** vinculadas a ese cliente. Alcance práctico de “nuevas operaciones” deberá documentarse por cada módulo posterior (por ejemplo, nuevos servicios o nuevas plantas). No impedir consultas existentes.
- `active` (bandera) es el mecanismo de desactivación aprobado para estas tablas; **no añadir `softDeletes`/`deleted_at`** sin decisión expresa.
- Proteger integridad de la FK. Se propone `restrictOnDelete()` para `plants.client_id`, alineado con conservación del histórico (RN-026, AC-12). Esto no sustituye la política de no eliminar físicamente catálogos con registros históricos.
- La restricción por cliente inactivo **no puede asegurarse únicamente mediante un índice/FK**: programar control en punto de escritura de dominio y reforzarlo posteriormente en Forms/Policies. Evitar afirmar que DB sola lo garantiza.

### Criterios de aceptación MV-006 a MV-008

1. Especificación de campos, relaciones y comportamiento aprobada y trazable.
2. En una futura tarea funcional autorizada, probar migraciones `clients` y `plants` **solo en BD de pruebas aislada**, comprobando FK y `UNIQUE(client_id,name)`. Una prohibición de migraciones incluye las ejecutadas por tests; esta integración documental no las autoriza.
3. Modelos `App\Models\Client` y `App\Models\Plant` con `fillable`/`casts` prudentes y relaciones Eloquent tipadas.
4. Tests: relación uno-a-muchos; nulos opcionales; default `active`; FK inválida denegada; nombre repetido dentro del cliente rechazado; nombre repetido entre clientes permitido; cliente inactivo no cambia estados de plantas; restricción de creación vía mecanismo de dominio probado; protección contra borrado de cliente vinculado.
5. Sin `ClientResource` ni `PlantResource` en MV-006 a MV-008: se desarrollan en MV-009/MV-010. No crear Track/Switch/Section todavía.
6. Una migración exitosa de `clients`/`plants` **no significa que los formularios ni la política de autorizaciones estén terminados**.

## 6. Usuarios y autorizaciones

- `Administrador`: acceso técnico total, configuración y contingencias.
- `Jefe de supervisores` (Esteban): opera catálogos según matriz, gestiona inventario, revisa, solicita correcciones, selecciona contenido y aprueba cortes.
- `Supervisor`: captura operación en servicios vigentes asignados; **no** crea materiales ni aprueba reportes.
- Autenticación, roles y permisos con Spatie. Policies y Scopes refuerzan acceso por servicio y planta, incluso mediante URL directa (§9.1, RF-034, RC-003). No basta con ocultar navegación.
- La matriz de 25 permisos de §9 se implementó en `RolePermissionSeeder`; `maintenance.core.view` es **solo lectura** y no autoriza indiscriminadamente mutaciones de Clientes o Plantas. Definir permisos de escritura de catálogos cuando toque el módulo, de forma explícita y compatible con la matriz de negocio.
- No reutilizar el rol `Supervisor` como equivalente a lectura global de todas las plantas.
- MV-003 es un entregable independiente: verificar `FilamentUser`, `canAccessPanel`, autenticación y denegaciones antes de concluirlo.

## 7. Reglas operativas que los módulos futuros no deben romper

### Actividades y ubicación (§4, §5, §7)

- RN-004/RN-005: una actividad se ubica en **tramo** o **cambio** según su tipo; tramo puede delimitarse por cambios.
- RN-006/RN-007: actividades programadas y no programadas; pueden durar varios días.
- RN-008: toda actividad completada lleva evidencia fotográfica.
- Catálogos de actividades: categoría, subopción, unidad, ubicación requerida e impacto de inventario.

### Inventario (§10)

- RN-009 a RN-017: existencias por **Planta + Material + Condición** (`Nuevo` / `Recobro`), separadas. Salida de nuevo disminuye nuevo; recobro solo si recuperable; instalación faltante no produce recobro.
- Esteban/Admin crean y habilitan materiales por planta. Supervisor mueve existencias ya autorizadas.
- Inventario final de periodo cerrado pasa a inicial del siguiente (RN-014); los movimientos son auditables, con timestamp y usuario (RN-015).
- Prohibido stock negativo por defecto (RN-025). Movimientos dentro de transacciones y bloqueo de fila durante actualización concurrente (§10.3).
- No editar/borrar movimientos confirmados ni ocultar correcciones: ajustes auditados.

### Evidencias, hallazgos y reportes (§11–§13)

- Imágenes: validar, orientar, lado mayor aproximado 1920 px, WebP ~80 %, miniatura; GCS en producción, disk local temporal en desarrollo; BD guarda rutas/metadatos.
- Hallazgo preserva fecha original, con estado pendiente/resuelto y fecha de resolución aparte (RN-018/RN-019).
- Reporte interno incluye toda operación; PDF final contiene selección de Esteban (RN-020 a RN-022). Excluidos del PDF siguen en histórico.
- Un folio único por corte, estable tras correcciones (RN-023). Aprobación de Esteban oficializa PDF (RN-024). PDF aprobado debe ser descargable e inmutable respecto de movimientos posteriores.
- **No crear tablas/funciones de estos módulos durante MV-006 a MV-008.** Estas reglas orientan las relaciones futuras.

## 8. Pendientes reconocidos en documentación (§19)

- P-01: nombres de subopciones de “Mantenimiento general de vía”.
- P-02: escala y significado de criticidad de hallazgos.
- P-03: máximo de fotos seleccionadas para PDF.
- P-04: formato definitivo de folio.
- P-05: datos reales de planta piloto y croquis.
- P-06: inventario inicial y materiales de planta piloto.

No sustituir estos pendientes con suposiciones. No bloquean `Client` / `Plant`.

## 9. Cronograma y avance (identificadores oficiales de Semana 01)

Semana 01, **22–28 oct 2026**:

| ID | Actividad | Estado de última auditoría y conversación |
| --- | --- | --- |
| MV-001 | Auditar Laravel, dependencias y MariaDB | Completada |
| MV-002 | Verificar Git, convenciones y estructura | Completada, según auditoría posterior |
| MV-003 | Autenticación y acceso al panel | Parcial; autorización no probada |
| MV-004 | Diseñar roles funcionales | Completada como matriz inicial |
| MV-005 | Implementar permisos base Spatie | Completada en BD; integración HTTP/Policies aún pendiente |
| MV-006 | Diseñar esquema Cliente y Planta | Diseño aprobado por usuario; pendiente registro/validación en código |
| MV-007 | Crear migraciones Cliente y Planta | Pendiente según auditoría |
| MV-008 | Crear modelos y relaciones | Pendiente según auditoría |
| MV-009 | Desarrollar formulario de Clientes | Pendiente |
| MV-010 | Desarrollar formulario de Plantas | Pendiente |

Este registro es histórico; **actualizar únicamente al verificar evidencia**. Cada miércoles se reportan plan vs real, horas reales, evidencias, bloqueos y siguiente semana. No inventar horas, porcentajes ni estados. Etapa 1 completa: **22 oct–4 nov** (proyecto, acceso, clientes/plantas/vías/cambios/tramos/croquis).

## 10. Instrucciones de ejecución segura para Codex

1. Leer `AGENTS.md` existente (si existe), este archivo y después las referencias originales disponibles; `AGENTS.md` conserva su función de instrucciones del agente.
2. Antes de modificar: `git status --short`, archivos/migraciones existentes, firmas de los modelos y pruebas. Evitar sobrescribir trabajo reciente.
3. Trabajar en rama actual salvo autorización distinta. No ejecutar comandos que borren o reconstruyan la BD de desarrollo (`migrate:fresh`, `db:wipe`, `migrate:refresh`, `schema:drop`, truncados).
4. **Comprobar conexión efectiva del proceso de pruebas** antes de usar `RefreshDatabase`: SQLite `:memory:` aislada. No depender solo de que `phpunit.xml` la declare; evitar que `.env.testing` o variables externas la sobrescriban.
5. Si SQLite no puede reproducir una regla crítica de MariaDB (colación/índice), registrarlo como limitación y proponer prueba específica aislada en MariaDB de pruebas; **nunca usar la base de desarrollo para pruebas destructivas**.
6. No modificar secretos, `.env` ni credenciales; no imprimir variables sensibles ni contraseñas.
7. Después de cada bloque, ejecutar pruebas relevantes, informar comandos y resultados, `git diff --stat` y archivos modificados; distinguir pruebas ejecutadas de pruebas propuestas.
8. No modificar módulos ajenos ni crear commits automáticamente. Proponer nombre de commit y esperar aprobación.

## 11. Referencias dentro del documento original

- §1–§3: objetivos, alcance, roles y jerarquía; págs. 2–5.
- §4 y §6: RN-001 a RN-026, RF-001 a RF-035; págs. 6 y 10.
- §7: atributos completos de entidades; págs. 11–16.
- §8–§9: relaciones Eloquent y autorización; págs. 18–19.
- §10–§12: inventario, fotografías, reportes; págs. 20–22.
- §16–§19: aceptación, auditoría, integración y pendientes; págs. 26–29.
- §20–§21: orden de trabajo y aceptación del MVP; págs. 30–31.

**Regla final:** el hecho de que una implementación parezca razonable técnicamente no la convierte automáticamente en requisito aprobado. Reportar claramente toda propuesta fuera de la documentación o de las decisiones confirmadas.
