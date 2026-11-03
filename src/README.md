
# ChuspaSocial (local_chuspasocial)

ChuspaSocial es un plugin local para Moodle que incorpora un muro social académico y un marketplace de libros para los miembros de una institución educativa.

## Funcionalidades

- Muro social para crear y visualizar publicaciones académicas.
- Publicaciones con imágenes, comentarios y reacciones.
- Etiquetas académicas para organizar y filtrar publicaciones.
- Seguimiento de etiquetas y usuarios.
- Feed personalizado con publicaciones de temas y usuarios seguidos.
- Marketplace para publicar y consultar libros.
- Mensajería entre usuarios mediante Moodle.
- Notificaciones relacionadas con la actividad social.

Algunas funcionalidades pueden encontrarse en desarrollo. Consultar [Requerimientos](../docs/requerimientos.md) para conocer su estado.

## Componentes principales

- `view.php`: página principal del plugin.
- `classes/local/`: servicios y lógica de negocio.
- `classes/external/`: funciones externas y servicios AJAX.
- `classes/`: modelos y componentes PHP.
- `templates/`: plantillas Mustache de la interfaz.
- `amd/src/`: módulos JavaScript.
- `db/`: definición de base de datos, eventos y permisos.
- `lang/`: traducciones del plugin.

## Requisitos técnicos

| Componente | Versión |
|---|---|
| Moodle | 4.5 LTS |
| PHP | 8.1, 8.2 o 8.3 |
| MySQL | 8.0 o superior |
| MariaDB | 10.6.7 o superior |
| PostgreSQL | 13 o superior |

El issue de documentación menciona PHP 8.0+, pero Moodle 4.5 requiere PHP 8.1 como mínimo. Se recomienda utilizar una versión de PHP compatible con la instalación de Moodle.

También se requiere:

- Tener habilitada la funcionalidad de etiquetas de Moodle (`usetags`).
- Habilitar la mensajería de Moodle (`messaging`) para las funciones correspondientes.
- Configurar el cron de Moodle para ejecutar las tareas programadas.
- Mantener permisos de escritura adecuados en `moodledata`.

## Instalación mediante Git

Desde el directorio raíz de Moodle, ejecutar:

```bash
git clone --branch dev https://github.com/sis-inf/ChuspaSocial.git chuspasocial-repo
```

El código del plugin se encuentra dentro de `chuspasocial-repo/src/`. Copiar ese contenido a la ubicación de plugins locales de Moodle:

```text
moodle/local/chuspasocial/
```

Después, acceder como administrador a **Administración del sitio > Notificaciones** para completar la instalación.

Para configurar un entorno de desarrollo, consultar la [Guía de desarrollo](../docs/entorno-desarrollo.md).

## Instalación desde un archivo ZIP

1. Comprimir el contenido del plugin en un archivo ZIP cuya carpeta raíz sea `chuspasocial`.
2. Iniciar sesión en Moodle como administrador.
3. Ir a **Administración del sitio > Extensiones > Instalar plugins**.
4. Subir el archivo ZIP e iniciar la instalación.
5. Confirmar la actualización de la base de datos.
6. Purgar las cachés de Moodle.

## Documentación

- [Requerimientos funcionales](../docs/requerimientos.md)
- [Arquitectura del sistema](../docs/arquitectura.md)
- [Entorno de desarrollo](../docs/entorno-desarrollo.md)
- [Guía de despliegue](../docs/despliegue.md)
- [Modelo de datos](../docs/modelo-datos.md)
- [Permisos](../docs/permisos.md)
- [API del plugin](../docs/api.md)
- [Guía de eventos](../docs/guia-eventos.md)

## Licencia

GNU General Public License v3.0.
