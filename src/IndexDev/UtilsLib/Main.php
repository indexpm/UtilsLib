<?php

namespace IndexDev\UtilsLib;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

use IndexDev\UtilsLib\utils\{Utils, FormUtils, Database, FileManager};
use IndexDev\UtilsLib\utils\FormUtils;
use IndexDev\UtilsLib\command\UtilsCommand;

class Main extends PluginBase {
    use SingletonTrait;

    $db = Databse::getInstance()->getMySQLConnection();

    /**
     * Ejemplo de uso:
     * 
     * $db = Database::getInstance()->getSQLiteConnection();
     * 
     * if ($db !== false) {
     *     $result = $db->query("SELECT * FROM jugadores");
     *     while ($row = $result->fetchArray(SQLITE3_ASSOC)){
     *         echo "Jugador: " . $row["name"] . "\n";
     *     }
     * }
     */

    protected function onEnable(): void {
        self::setInstance($this);

        $this->saveResource("settings.yml");
        $this->saveResource("mysql.yml");

        Utils::init($this);
        FormUtils::init($this);
        Database::init($this);

        FileManager::setInstance(new FileManager());
        FileManager::setInstance(new FormUtils());

        FileManager::getInstance()->init();
        FileManager::init($this);

        $prefix = FileManager::getInstance()->get("settings")->get("prefix");

        $this->getServer()->getCommandMap()->register("utilslib", new UtilsCommand());

        Utils::log("§aLibreria UtilsLib habilitada correctamente");
    }

    protected function onDisable(): void {
        Database::close();
    }
}
