<?php

namespace IndexDev\UtilsLib\utils;

use pocketmine\utils\SingletonTrait;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

use mysqli;
use SQLite3;
use Exception;

use IndexDev\UtilsLib\Main;

class Database {
    use SingletonTrait;

    private ?mysqli $mysqlConnection = null;
    private ?SQLite3 $sqliteConnection = null;
    private array $cache = [];
    private const SLOW_QUERY_THRESHOLD = 1000; # milisegundos
    private const CACHE_EXPIRATION_TIME = 3600; # 1 hora en segundos

    # Database::setInstance(new Database());
    # Database::getInstance()->init();
    # Database::getInstance()->closeMySQL();
    # Database::getInstance()->closeSQLite();
    # Database::getInstance()->backupDatabase();
    # Database::getInstance()->startMySQLTransaction();
    # Database::getInstance()->commitMySQLTransaction();

    /**
     * $result = [Resultados de una consulta];
     * $page = 2;
     * $perPage = 10;
     * $pagedResults = Database::getInstance()->paginateResults($results, $page, $perPage);
     */

    # Error handling
    public static function throwDatabaseException(string $message): void {
        throw new Exception("Database Error: $message");
    }

    public function init(): void {
        $plugin = Main::getInstance();

        # Configuracion MySQL
        $mysqlConfig = FileManager::getInstance()->get("mysql");
        if ($mysqlConfig->get("enabled", false)) {
            $this->connectMySQL($mysqlConfig);
        }

        # Configuracion SQLite
        if (!$mysqlConfig->get("enabled", false)) {
            $this->connectSQLite($plugin);
        }
    }

    private function connectMySQL($config): void {
        $host = $config->get("host");
        $user = $config->get("user");
        $password = $config->get("password");
        $database = $config->get("database");

        # Conexion persistente a MySQL
        $this->mysqlConnection = new mysqli("p:" . $host, $user, $password, $database);

        if ($this->mysqlConnection->connect_error) {
            self::throwDatabaseException("Error al conectar a MySQL: " . $this->mysqlConnection->connect_error);
        } else {
            Main::getInstance()->getLogger()->info("Conectado a MySQL con éxito.");
            $this->runMigrationsMySQL();
        }
    }

    private function connectSQLite(Plugin $plugin): void {
        $sqliteFile = $plugin->getDataFolder() . "database.db";
        $this->sqliteConnection = new SQLite3($sqliteFile);

        if ($this->sqliteConnection) {
            Main::getInstance()->getLogger()->info("Conectado a SQLite con éxito.");
            $this->runMigrationsSQLite();
        } else {
            self::throwDatabaseException("Error al conectar a SQLite.");
        }
    }

    public function getMySQLConnection(): ?mysqli {
        return $this->mysqlConnection;
    }

    public function getSQLiteConnection(): ?SQLite3 {
        return $this->sqliteConnection;
    }

    # Consultas preparadas para MySQL
    public function prepareMySQLQuery(string $query, array $params): ?mysqli_stmt {
        if ($this->mysqlConnection) {
            $stmt = $this->mysqlConnection->prepare($query);
            if ($stmt === false) {
                self::throwDatabaseException("Error al preparar consulta MySQL: " . $this->mysqlConnection->error);
                return null;
            }

            $types = str_repeat('s', count($params)); # Suponiendo que todos son strings
            $stmt->bind_param($types, ...$params);

            return $stmt;
        }

        return null;
    }

    # Consultas preparadas para SQLite
    public function prepareSQLiteQuery(string $query, array $params): ?SQLite3Stmt {
        if ($this->sqliteConnection) {
            $stmt = $this->sqliteConnection->prepare($query);
            if (!$stmt) {
                self::throwDatabaseException("Error al preparar consulta SQLite.");
                return null;
            }

            $paramIndex = 1;
            foreach ($params as $param) {
                $stmt->bindValue($paramIndex++, $param);
            }

            return $stmt;
        }

        return null;
    }

    # Cerrar la conexion MySQL
    public function closeMySQL(): void {
        if ($this->mysqlConnection) {
            $this->mysqlConnection->close();
            Main::getInstance()->getLogger()->info("Conexión MySQL cerrada.");
        }
    }

    # Cerrar la conexion SQLite
    public function closeSQLite(): void {
        if ($this->sqliteConnection) {
            $this->sqliteConnection->close();
            Main::getInstance()->getLogger()->info("Conexión SQLite cerrada.");
        }
    }

    # Migraciones MySQL
    private function runMigrationsMySQL(): void {
        $sql = file_get_contents(Main::getInstance()->getDataFolder() . "migrations/mysql_migrations.sql");
        if ($sql !== false) {
            if ($this->mysqlConnection->multi_query($sql)) {
                Main::getInstance()->getLogger()->info("Migraciones MySQL ejecutadas con éxito.");
            } else {
                self::throwDatabaseException("Error al ejecutar migraciones MySQL: " . $this->mysqlConnection->error);
            }
        } else {
            self::throwDatabaseException("Error al leer el archivo de migraciones MySQL.");
        }
    }

    # Migraciones SQLite
    private function runMigrationsSQLite(): void {
        $sql = file_get_contents(Main::getInstance()->getDataFolder() . "migrations/sqlite_migrations.sql");
        if ($sql !== false) {
            if ($this->sqliteConnection->exec($sql)) {
                Main::getInstance()->getLogger()->info("Migraciones SQLite ejecutadas con éxito.");
            } else {
                self::throwDatabaseException("Error al ejecutar migraciones SQLite.");
            }
        } else {
            self::throwDatabaseException("Error al leer el archivo de migraciones SQLite.");
        }
    }

    # Paginacion de resultados
    public function paginateResults(array $results, int $page, int $perPage): array {
        $start = ($page - 1) * $perPage;
        return array_slice($results, $start, $perPage);
    }

    # Transacciones para MySQL
    public function safeExecuteTransaction(array $queriesWithParams): bool {
        if (!$this->mysqlConnection) {
            self::throwDatabaseException("No hay conexión MySQL disponible.");
            return false;
        }

        $this->mysqlConnection->begin_transaction();

        try {
            foreach ($queriesWithParams as $queryWithParams) {
                $stmt = $this->prepareMySQLQuery($queryWithParams['query'], $queryWithParams['params']);
                if ($stmt) {
                    $stmt->execute();
                }
            }

            $this->mysqlConnection->commit();
            return true;
        } catch (Exception $e) {
            $this->mysqlConnection->rollback();
            self::throwDatabaseException("Error en la transacción: " . $e->getMessage());
            return false;
        }
    }

    # Transacciones para SQLite
    public function safeExecuteSQLiteTransaction(array $queriesWithParams): bool {
        if (!$this->sqliteConnection) {
            self::throwDatabaseException("No hay conexión SQLite disponible.");
            return false;
        }

        $this->sqliteConnection->exec('BEGIN');

        try {
            foreach ($queriesWithParams as $queryWithParams) {
                $stmt = $this->prepareSQLiteQuery($queryWithParams['query'], $queryWithParams['params']);
                if ($stmt) {
                    $stmt->execute();
                }
            }

            $this->sqliteConnection->exec('COMMIT');
            return true;
        } catch (Exception $e) {
            $this->sqliteConnection->exec('ROLLBACK');
            self::throwDatabaseException("Error en la transacción: " . $e->getMessage());
            return false;
        }
    }

    # Cache de resultados
    public function cacheQuery(string $query, array $params, $results): void {
        $cacheKey = $this->generateCacheKey($query, $params);
        $this->cache[$cacheKey] = [
            'data' => $results,
            'timestamp' => time()
        ];
    }

    public function getCachedQueryResults(string $query, array $params) {
        $cacheKey = $this->generateCacheKey($query, $params);
        if (isset($this->cache[$cacheKey])) {
            $cacheData = $this->cache[$cacheKey];
            if ((time() - $cacheData['timestamp']) < self::CACHE_EXPIRATION_TIME) {
                return $cacheData['data'];
            } else {
                unset($this->cache[$cacheKey]); # Expira la cache
            }
        }
        return null;
    }

    private function generateCacheKey(string $query, array $params): string {
        return md5($query . serialize($params));
    }

    # Registro de consultas Lentas
    public function logSlowQuery(string $query, $executionTime): void {
        if ($executionTime > self::SLOW_QUERY_THRESHOLD) {
            Main::getInstance()->getLogger()->warning("Consulta lenta detectada: $query | Tiempo: $executionTime ms");
        }
    }

    # Backup de Base de Datos
    public function backupDatabase(): void {
        $backupDir = Main::getInstance()->getDataFolder() . "backups/";

        # Crear directorio si no existe
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        # Backup MySQL
        if ($this->mysqlConnection) {
            $backupFile = $backupDir . "mysql_backup_" . date("Y-m-d_H-i-s") . ".sql";
            $command = "mysqldump --host={$this->mysqlConnection->host_info} --user={$this->mysqlConnection->user} --password={$this->mysqlConnection->password} {$this->mysqlConnection->database} > $backupFile";
            exec($command);
            Main::getInstance()->getLogger()->info("Backup de MySQL realizado en: $backupFile");
        }

        # Backup SQLite
        if ($this->sqliteConnection) {
            $backupFile = $backupDir . "sqlite_backup_" . date("Y-m-d_H-i-s") . ".db";
            copy($this->sqliteConnection->filename, $backupFile);
            Main::getInstance()->getLogger()->info("Backup de SQLite realizado en: $backupFile");
        }
    }
}
