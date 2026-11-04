# Entorno de desarrollo

## Windows con Laragon

Esta guía describe cómo preparar un entorno de desarrollo de ChuspaSocial en Windows con Laragon y Moodle 4.5 LTS. El plugin se mantiene en `src/` y se enlaza con Moodle para que los cambios se reflejen sin copiar los archivos manualmente.

**Estado de validación:** esta guía está pendiente de probarse en una instalación real de Laragon. Las rutas indicadas son las habituales y deben adaptarse si Laragon se instaló en otra ubicación.

### Requisitos

- Windows 10 u 11.
- [Laragon](https://laragon.org/download/), con una versión de PHP compatible con Moodle 4.5.
- MariaDB 10.6.7 o superior, o MySQL 8.0 o superior.
- [Git para Windows](https://git-scm.com/download/win).
- Este repositorio clonado en el equipo.

En los ejemplos se supone que Laragon está instalado en `C:\laragon` y que Moodle estará en `C:\laragon\www\moodle`. Si usas otra ubicación, adapta las rutas de los comandos.

### 1. Instalar y configurar Laragon

1. Descarga Laragon desde su sitio oficial e instálalo.
2. Abre Laragon y utiliza su menú para iniciar los servicios web y de base de datos.
3. Comprueba qué versión de PHP está seleccionada y verifica que sea compatible con Moodle 4.5.
4. Comprueba que las extensiones de PHP requeridas por Moodle estén habilitadas, incluidas `mysqli`, `curl`, `gd`, `intl`, `mbstring`, `soap`, `xml`, `zip` y `sodium`.
5. En el archivo `php.ini` de la versión activa, establece `max_input_vars = 5000`. Reinicia los servicios después de guardar los cambios.

Comprueba PHP desde PowerShell:

```powershell
C:\laragon\bin\php\php-8.3\php.exe --version
```

La ruta anterior es un ejemplo: sustituye `php-8.3` por la carpeta de la versión que tengas instalada. También puedes ejecutar `php --version` si PHP está disponible en el `PATH`.

### 2. Crear la base de datos

1. Inicia los servicios desde Laragon.
2. Abre la herramienta de administración de bases de datos que incluya tu instalación.
3. Crea una base de datos llamada `moodle` con codificación `utf8mb4` y una intercalación compatible, como `utf8mb4_unicode_ci`.
4. Anota el usuario, la contraseña y el puerto configurados.

### 3. Descargar Moodle 4.5

Abre PowerShell y ejecuta:

```powershell
Set-Location C:\laragon\www
git clone --branch MOODLE_405_STABLE --depth 1 https://github.com/moodle/moodle.git moodle
New-Item -ItemType Directory -Force C:\laragon\moodledata
```

La carpeta `moodledata` debe estar fuera del directorio público `www`.

### 4. Enlazar el plugin ChuspaSocial

Comprueba que el repositorio contiene el archivo `src\version.php`. En estos ejemplos, el repositorio está en `C:\Users\CAMILA\ChuspaSocial`.

```powershell
Test-Path C:\Users\CAMILA\ChuspaSocial\src\version.php
```

El resultado debe ser `True`. A continuación, crea el enlace:

```powershell
New-Item -ItemType Junction `
  -Path C:\laragon\www\moodle\local\chuspasocial `
  -Target C:\Users\CAMILA\ChuspaSocial\src
```

Comprueba el enlace:

```powershell
Test-Path C:\laragon\www\moodle\local\chuspasocial\version.php
```

Debe devolver `True`. Si la carpeta de destino ya existe, revisa su contenido antes de intentar crear el enlace.

### 5. Instalar Moodle

Identifica la ruta real del archivo `php.exe` de Laragon y sustituye `RUTA_PHP` por la ruta completa de la carpeta que lo contiene. Sustituye también las credenciales por las de tu base de datos.

```powershell
Set-Location C:\laragon\www\moodle

& "RUTA_PHP\php.exe" admin\cli\install.php `
  --wwwroot=http://localhost/moodle `
  --dataroot=C:\laragon\moodledata `
  --dbtype=mariadb `
  --dbhost=127.0.0.1 `
  --dbname=moodle `
  --dbuser=root `
  --dbpass="CONTRASEÑA_BASE_DATOS" `
  --fullname="ChuspaSocial Dev" `
  --shortname=chuspa `
  --adminuser=admin `
  --adminpass="CONTRASEÑA_ADMIN" `
  --adminemail=admin@example.com `
  --agree-license `
  --non-interactive
```

El marcador `RUTA_PHP` debe sustituirse por la ruta real; no ejecutes el comando literalmente con ese texto. La contraseña de administrador debe cumplir los requisitos de seguridad de Moodle.

### 6. Verificar la instalación

1. Abre `http://localhost/moodle` en el navegador.
2. Inicia sesión con la cuenta de administrador creada durante la instalación.
3. Entra en **Administración del sitio > Extensiones > Resumen de extensiones**.
4. Comprueba que `local_chuspasocial` aparece instalado.
5. Realiza un cambio de prueba en un archivo seguro del plugin y comprueba que se refleja en la carpeta enlazada. Después, revierte ese cambio.

### Problemas frecuentes

- **PHP no se reconoce:** utiliza la ruta completa de `php.exe` de Laragon.
- **Falta una extensión de PHP:** habilítala en el `php.ini` de la versión activa y reinicia los servicios.
- **Error de conexión con la base de datos:** comprueba el servicio, el puerto y las credenciales.
- **El plugin no aparece:** verifica que `local\chuspasocial\version.php` existe en Moodle y que el enlace apunta a `src/`.

**Validación pendiente:** realiza una instalación completa en Laragon antes de afirmar que la guía ha sido probada. Incluye una captura del resultado en el Pull Request.

## Windows con XAMPP

Esta guía deja ChuspaSocial funcionando en Windows con XAMPP y Moodle 4.5 LTS,
con los cambios de `src/` reflejados al instante en Moodle.
Fue probada con XAMPP (PHP 8.2.12), MariaDB 10.11.19 y Moodle 4.5.15.

### Requisitos

- Windows 10 u 11.
- [XAMPP](https://www.apachefriends.org/) con **PHP 8.1, 8.2 u 8.3**.
- **MariaDB 10.6.7 o superior** (o MySQL 8.0+), requisito de Moodle 4.5.
- [Git para Windows](https://git-scm.com/download/win).
- Tu fork de este repositorio clonado en tu equipo (en adelante, `<RUTA_REPO>`).

Comprueba las versiones que trae tu XAMPP:

```powershell
C:\xampp\php\php.exe --version
C:\xampp\mysql\bin\mysql.exe --version
```

Si MariaDB es anterior a 10.6.7 (por ejemplo, 10.4.x), sigue la
[actualización de MariaDB](#actualizar-mariadb-si-tu-xampp-trae-una-versión-anterior-a-1067)
antes de continuar.

### 1. Configurar PHP

1. Abre `C:\xampp\php\php.ini` y verifica que estas extensiones estén activas
   (sin `;` al inicio): `intl`, `zip`, `gd`, `soap`, `sodium` y `mysqli`.
2. Busca la línea `;max_input_vars = 1000` y déjala **sin `;`** y con este valor:

```ini
   max_input_vars = 5000
```

3. Guarda el archivo y reinicia **Apache** desde el XAMPP Control Panel.
4. Comprueba que PHP lee el valor nuevo:

```powershell
   C:\xampp\php\php.exe -r "echo ini_get('max_input_vars');"
```

   Debe responder `5000`.

### 2. Iniciar la base de datos y crearla

1. En el XAMPP Control Panel, inicia **Apache** y **MySQL**.
2. Comprueba que MariaDB responde en el puerto 3306:

```powershell
   C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT VERSION();"
```

3. Crea la base de datos de Moodle:

```powershell
   C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE moodle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Clonar Moodle 4.5

```powershell
cd C:\xampp\htdocs
git clone --branch MOODLE_405_STABLE --depth 1 https://github.com/moodle/moodle.git moodle
mkdir C:\xampp\moodledata
```

La carpeta `moodledata` debe quedar **fuera** de `htdocs`.

### 4. Enlazar el plugin en `local/chuspasocial`

Crea un enlace (junction) desde la carpeta `src/` de tu repositorio.
Este paso debe hacerse **antes** de instalar Moodle.

```powershell
New-Item -ItemType Junction `
  -Path C:\xampp\htdocs\moodle\local\chuspasocial `
  -Target <RUTA_REPO>\src
```

Comprueba el enlace:

```powershell
Test-Path C:\xampp\htdocs\moodle\local\chuspasocial\version.php
```

Debe responder `True`. Si `New-Item` da error de permisos, abre PowerShell como administrador.

### 5. Instalar Moodle

```powershell
cd C:\xampp\htdocs\moodle
C:\xampp\php\php.exe admin\cli\install.php `
  --wwwroot=http://localhost/moodle `
  --dataroot=C:\xampp\moodledata `
  --dbtype=mariadb --dbhost=127.0.0.1 --dbname=moodle `
  --dbuser=root --dbpass= `
  --fullname='ChuspaSocial Dev' --shortname=chuspa `
  --adminuser=admin --adminpass='<CONTRASEÑA>' `
  --adminemail=admin@example.com `
  --agree-license --non-interactive
```

- La contraseña debe cumplir la política de Moodle: mínimo 8 caracteres,
  con mayúscula, minúscula, número y un símbolo.
- Tarda varios minutos. No lo interrumpas hasta ver `Installation completed successfully.`
- Las advertencias de `openssl_pkey_export` del módulo LTI son normales en XAMPP
  y no impiden la instalación.

### 6. Verificar la instalación

1. Comprueba que se creó la tabla del plugin:

```powershell
   C:\xampp\mysql\bin\mysql.exe -u root -e "SHOW TABLES FROM moodle LIKE 'mdl_local_chuspasocial%';"
```

2. Abre `http://localhost/moodle` e inicia sesión con el usuario `admin`.
3. Ve a **Administración del sitio > Extensiones > Resumen de extensiones**
   y busca `local_chuspasocial`. Debe aparecer instalado.

### Actualizar MariaDB si tu XAMPP trae una versión anterior a 10.6.7

1. Descarga el **ZIP** (no el MSI) de MariaDB 10.11 LTS para Windows desde
   [mariadb.org/download](https://mariadb.org/download/).
   En **Package Type** elige `ZIP file`, no `ZIP file (debug symbols only)`.
2. En el XAMPP Control Panel, detén MySQL.
3. Aparta la carpeta vieja y coloca la nueva:

```powershell
   Rename-Item C:\xampp\mysql C:\xampp\mysql_old
   Expand-Archive "$HOME\Downloads\mariadb-10.11.*-winx64.zip" C:\xampp\
   Rename-Item (Get-Item C:\xampp\mariadb-10.11.*) mysql
   Copy-Item C:\xampp\mysql_old\bin\my.ini C:\xampp\mysql\bin\my.ini
```

4. Inicia MySQL desde el panel y comprueba la versión:

```powershell
   C:\xampp\mysql\bin\mysql.exe --version
```

La base de datos nueva empieza vacía y el usuario `root` queda sin contraseña.
Para volver atrás, detén MySQL, elimina `C:\xampp\mysql` y renombra `mysql_old` a `mysql`.

### Problemas frecuentes

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| El instalador pide una versión mayor de MariaDB | XAMPP trae una MariaDB antigua | Seguir la sección de actualización de MariaDB |
| `ERROR 2002 Can't connect to server` | MySQL está detenido en el panel | Iniciar MySQL desde el XAMPP Control Panel |
| `max_input_vars` sigue en 1000 | La línea sigue comentada con `;` | Quitar el `;` en `php.ini` y reiniciar Apache |
| Falta una extensión de PHP (`intl`, `zip`, etc.) | Está comentada en `php.ini` | Quitar el `;` y reiniciar Apache |
| `New-Item` da error de permisos | Faltan privilegios | Abrir PowerShell como administrador |
| El plugin no aparece en Extensiones | El enlace apunta a otra carpeta | Comprobar que `local\chuspasocial\version.php` existe |
| La instalación falla a medias | Error en el código del plugin | Corregirlo y reinstalar (ver abajo) |

### Reinstalar desde cero

Si la instalación falla, vacía la base de datos y los archivos antes de repetirla:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE moodle; CREATE DATABASE moodle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
Remove-Item C:\xampp\htdocs\moodle\config.php -ErrorAction SilentlyContinue
Remove-Item C:\xampp\moodledata\* -Recurse -Force
```

Después vuelve a ejecutar el paso 5.

### Instalación sin enlace (solo para probar)

Si solo quieres probar el plugin sin desarrollar, instálalo desde un zip.
Los pasos están en [`src/README.md`](../src/README.md).

## Problemas comunes

### Error `max_input_vars`

**Síntoma**

Durante la instalación o comprobación del entorno, Moodle muestra el mensaje:

`PHP setting max_input_vars must be at least 5000.`

**Causa**

La directiva `max_input_vars` de PHP está configurada con un valor menor que `5000`. Moodle necesita al menos ese límite para procesar correctamente formularios con una gran cantidad de campos.

**Solución**

1. Localiza el archivo `php.ini` utilizado por el servidor web.
2. Busca la directiva `max_input_vars` y establece:

   ```ini
   max_input_vars = 5000
   ```

3. Guarda el archivo y reinicia el servicio web o PHP para aplicar el cambio.
4. Verifica el valor activo con:

   ```bash
   php -i | grep max_input_vars
   ```

El valor efectivo debe ser `5000` o superior.

### Error de extensiones de PHP faltantes

#### Síntoma

Al ejecutar un comando de Composer (por ejemplo `composer install` o `composer diagnose`) aparece el mensaje:

```text
In Factory.php line 656:

  The openssl extension is required for SSL/TLS protection but is not available. If you can not enable the openssl extension, you can disable this error, at your own risk, by setting the 'disable-tls' option to true.
```

El mensaje cambia según la extensión que falte, pero siempre indica su nombre.

#### Causa

La extensión de PHP requerida no está instalada o está comentada (con `;` al inicio) en el archivo `php.ini`.

#### Cómo detectarlas

1. Lista las extensiones cargadas:

   ```bash
   php -m
   ```

2. Busca una en concreto:

   ```bash
   php -m | grep -i openssl
   ```

   En Windows (CMD) usa `php -m | findstr /i openssl`.

3. Comprueba qué extensiones exige el proyecto:

   ```bash
   composer check-platform-reqs
   ```

Si la extensión no aparece en la lista, está deshabilitada o no instalada.

#### Solución

1. Localiza el `php.ini` que usa PHP:

   ```bash
   php --ini
   ```

2. Ábrelo y quita el `;` del inicio de la línea de la extensión:

   ```ini
   extension=openssl
   ```

3. Guarda el archivo y abre una terminal nueva para aplicar el cambio.
4. Verifica que ya aparece con `php -m`.

En Linux, si la extensión no está instalada, instálala con el gestor de paquetes (por ejemplo `sudo apt install php-mbstring`).
### Error `dataroot not writable`

#### Síntoma

Durante la instalación o al abrir Moodle, aparece el mensaje:

`Fatal error: $CFG->dataroot is not writable, admin has to fix directory permissions! Exiting.`

#### Causa

Moodle no puede escribir en la carpeta `moodledata`. Puede ocurrir porque la carpeta no existe, porque la ruta indicada en `--dataroot` (o en `$CFG->dataroot` de `config.php`) es incorrecta, o porque el usuario con el que corre Apache no tiene permisos de escritura sobre ella.

#### Solución

#### En Windows

1. Comprueba que la carpeta existe y que la ruta coincide con la de `config.php`:

   ```powershell
   Test-Path C:\xampp\moodledata
   ```

   Si responde `False`, créala con `mkdir C:\xampp\moodledata`.

2. Haz clic derecho en `C:\xampp\moodledata` > **Propiedades** > **Seguridad** > **Editar**.
3. Selecciona el grupo `Users` (o el usuario con el que corre Apache) y marca **Modificar**.
4. Aplica los cambios y reinicia **Apache** desde el XAMPP Control Panel.
5. Verifica el permiso. Debe aparecer `Modify` o `FullControl`:

   ```powershell
   icacls C:\xampp\moodledata
   ```

#### En Linux

1. Crea la carpeta si no existe y asigna el usuario del servidor web (`www-data` en Debian/Ubuntu, `apache` en Fedora/RHEL):

   ```bash
   sudo mkdir -p /var/www/moodledata
   sudo chown -R www-data:www-data /var/www/moodledata
   sudo chmod -R 775 /var/www/moodledata
   ```

2. Reinicia el servidor web (por ejemplo, `sudo systemctl restart apache2`).

#### En macOS

1. Crea la carpeta y da permisos al usuario del servidor web (`_www` con el Apache del sistema; con XAMPP o MAMP suele ser `daemon`):

   ```bash
   sudo mkdir -p /usr/local/moodledata
   sudo chown -R _www:_www /usr/local/moodledata
   sudo chmod -R 775 /usr/local/moodledata
   ```

2. Reinicia Apache.

La carpeta `moodledata` debe estar **fuera** de `htdocs`.
