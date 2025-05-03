# UtilsLib para PocketMine-MP 5

**UtilsLib** es una biblioteca para desarrolladores de plugins de **PocketMine-MP 5**. Proporciona una variedad de herramientas utilitarias, como manejo de bases de datos (MySQL y SQLite), manejo de transacciones, caché de consultas, migraciones de bases de datos, y más. Esta biblioteca está diseñada para facilitar el desarrollo de plugins, promoviendo la reutilización de código y la modularidad.

## Características

- **Conexión a MySQL y SQLite**: Soporte para ambas bases de datos, con conexiones de alto rendimiento y fáciles de usar.
- **Consultas Preparadas**: Ejecuta consultas SQL seguras utilizando parámetros vinculados, evitando posibles vulnerabilidades de inyección SQL.
- **Migraciones de Base de Datos**: Gestiona las migraciones de la base de datos para MySQL y SQLite automáticamente.
- **Caché de Consultas**: Almacena los resultados de las consultas en caché para mejorar el rendimiento de las consultas repetidas.
- **Registro de Consultas Lentas**: Registra consultas que exceden un umbral de tiempo definido, permitiendo la optimización del rendimiento.
- **Transacciones**: Soporte para transacciones tanto en MySQL como en SQLite, asegurando la integridad de los datos.

## Requisitos

- **PHP 7.4 o superior**
- **PocketMine-MP 5**
- **MySQL o SQLite**: Dependiendo de la configuración seleccionada.
- **Make sure you have Vecnavium FormsUI installed: https://github.com/Vecnavium/FormsUI**

## Instalación

### 1. Clonar el repositorio

Para comenzar, clona este repositorio en tu servidor de desarrollo de PocketMine-MP 5.

```bash
git clone https://github.com/tu-usuario/UtilsLib.git
```

## Uso e Inicializacion de la base de datos 
**Puedes inicializar la base de datos y comenzar a usar las conexiones a MySQL o SQLite de la siguiente manera:**
```bash
use IndexDev\UtilsLib\utils\Database;

# Inicializa la base de datos
Database::getInstance()->init();
```

## Uso de transacciones
**Puedes usar transacciones tanto en MySQL como en SQLite para garantizar la integridad de los datos.**
## MySQL:

```bash
Database::getInstance()->startMySQLTransaction();

# Realiza varias consultas...

Database::getInstance()->commitMySQLTransaction();
```
## SQLite:
```bash
Database::getInstance()->startSQLiteTransaction();

# Realiza varias consultas...

Database::getInstance()->commitSQLiteTransaction();
```

# Backup de la Base de Datos
**Puedes realizar copias de seguridad de la base de datos MySQL o SQLite con el siguiente método:**

```bash
Database::getInstance()->backupDatabase();
```

## Migraciones
**El sistema de migraciones permite gestionar cambios en la estructura de la base de datos.**
## Crear Migraciones
**1. Crea un archivo .sql con las consultas necesarias para modificar la estructura de la base de datos.**
**2. Colócalo en la carpeta migrations dentro del directorio de tu plugin.**

## Ejecutar Migraciones
**Las migraciones se ejecutarán automáticamente cuando se establezca la conexión a la base de datos.**
# Registro de Consultas Lentas
**El sistema de registro de consultas lentas te permitirá registrar aquellas que tarden más de un umbral definido en el archivo de configuración.**
```bash
Database::getInstance()->logSlowQuery($query, $executionTime);
```

## Gestion de cache
**El sistema de caché te permite almacenar los resultados de las consultas para evitar consultas repetitivas.**
## Ejemplo de cache de consulta
```bash
$query = "SELECT * FROM tabla WHERE columna = ?";
$params = ['valor'];
$cachedResults = Database::getInstance()->getCachedQueryResults($query, $params);

if ($cachedResults === null) {
    # Ejecutar la consulta y almacenar los resultados en caché
    $stmt = Database::getInstance()->prepareMySQLQuery($query, $params);
    if ($stmt) {
        $stmt->execute();
        $results = $stmt->get_result();
        Database::getInstance()->cacheQuery($query, $params, $results);
    }
} else {
    // Usar los resultados almacenados en caché
}
```

## Ejecutar Consultas
**Para ejecutar consultas SQL, puedes utilizar los métodos de consultas preparadas tanto para MySQL como para SQLite.**
## Consultas en MySQL:

```bash
$params = ['valor1', 'valor2'];
$stmt = Database::getInstance()->prepareMySQLQuery("SELECT * FROM tabla WHERE columna = ?", $params);

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Procesar resultados
    }
}
```

## Archivo config.yml

```bash
mysql:
  enabled: true
  host: "localhost"
  user: "root"
  password: "password"
  database: "nombre_de_base_de_datos"

sqlite:
  enabled: false
  database: "database.db"
```
