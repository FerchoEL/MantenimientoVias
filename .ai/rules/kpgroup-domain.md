# KP Group — reglas de dominio

Complementa [AGENTS.md](../../AGENTS.md), que define flujo, autorizaciones y seguridad.
No autoriza implementar módulos. Consultar [PLANS.md](../../PLANS.md) para alcance,
[arquitectura](../../docs/arquitectura.md), [reglas RN/RC](../../docs/reglas-negocio.md),
[modelo de datos](../../docs/modelo-datos.md) y
[decisiones aprobadas](../../docs/decisiones-tecnicas.md) para detalle y fuentes.

## Arquitectura y alcance

- MVP de mantenimiento; no inspecciones, ERP ni funciones excluidas en arquitectura.
- Core neutral: Client, Plant, Track, TrackSwitch, TrackSection, User.
  No crear maintenance_plants ni maintenance_tracks.
- Client 1:N Plant; Plant 1:N Track y 1:N TrackSwitch; Track 1:N TrackSection.
  TrackSwitch no es hijo de Track ni contiene tramos. Los cambios de inicio/fin
  pueden ser referencias de TrackSection sin alterar la jerarquía.
- Servicio pertenece a planta. Asignaciones supervisor-servicio temporales,
  simultáneas y con vigencias superpuestas; no imponer exclusividad.
- Actividades, hallazgos y movimientos corresponden a periodo vigente;
  un reporte por periodo. No crear módulos futuros dentro de MV-006…MV-008.

## Clientes y plantas: decisiones confirmadas el 2026-10-26

- Client.short_name y Plant.short_name son opcionales (nullable).
- Nombre de planta único dentro de cada cliente; repetición entre clientes permitida.
  No inventar normalizaciones: concretar colación, caso, acentos y espacios.
- Desactivar Client no elimina ni desactiva automáticamente sus plantas y conserva
  el histórico. Mantener active; no añadir softDeletes sin decisión expresa.
- Bloquear nuevas operaciones de clientes inactivos; permitir lectura histórica
  autorizada. No inventar excepciones ni considerar un índice/FK suficiente para
  imponer la regla: validar en los puntos de escritura del dominio.
- La clasificación por módulo de nuevas operaciones y correcciones pendientes
  está en decisiones-tecnicas.md; resolverla antes de implementar la acción afectada.
- Conservar integridad referencial e histórico; no borrados en cascada del histórico.
- Probar ambos short_name nulos, relaciones, unicidad por cliente, claves foráneas,
  preservación de plantas y bloqueo de operaciones conforme a decisiones aprobadas.

## Autorización

- Spatie define permisos; Policies/Scopes aplican alcance por servicio/planta,
  incluyendo acceso directo por ID. Ocultar navegación no constituye autorización.
- Administrador: acceso técnico; Jefe de supervisores/Esteban: gestión y revisión
  según matriz; Supervisor: operación en servicios vigentes asignados.
- Supervisor no crea materiales ni aprueba reportes; no equivale a lectura global.
- maintenance.core.view no concede escrituras en catálogos; definirlas expresamente.
- Verificar FilamentUser, canAccessPanel y pruebas de acceso/denegación para MV-003.

## Inventario e histórico

- Saldo por Planta + Material + Condición; Nuevo y Recobro separados.
- Salida de nuevo y entrada opcional de recobro son independientes; solo recuperar
  material reutilizable. Instalación de faltante no genera recobro.
- Esteban/Admin crean y habilitan materiales; Supervisor mueve existentes autorizados.
- Transacciones y bloqueo de saldo ante concurrencia; no negativos por defecto.
  Movimientos trazables y ajustes auditados; no ocultar correcciones.
- Cierre de periodo pasa a apertura siguiente según las reglas del mismo servicio/planta.
  Mantener corte congelado y catálogos históricos desactivados, sin borrado físico.

## Evidencias, hallazgos y reportes

- Actividad completada requiere evidencia. Guardar archivos fuera de BD y rutas/
  metadatos en BD; Laravel Filesystem, GCS en producción y almacenamiento local en desarrollo.
- Validar imágenes, corregir orientación, lado mayor aproximado de 1920 px,
  WebP aproximadamente al 80 % y miniaturas. No inventar límites exactos pendientes.
- Preservar fecha original del hallazgo y registrar resolución aparte.
- Histórico operativo completo y PDF editorial son distintos. Esteban selecciona
  y aprueba; excluir del PDF no borra histórico.
- Folio único por corte, estable durante correcciones. PDF aprobado persistente,
  descargable e inmutable frente a movimientos posteriores.

## Pendientes

P-01…P-06 y la tensión location_type=either/none frente a RN-004 permanecen abiertos
en [decisiones técnicas](../../docs/decisiones-tecnicas.md). No inventar respuestas.
