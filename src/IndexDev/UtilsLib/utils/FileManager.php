<?php

namespace IndexDev\UtilsLib\utils;

use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

use IndexDev\UtilsLib\Main;

class FileManager {
    use SingletonTrait;

    /** @var array<string, Config> */
    private array $configs = [];

    public function init(): void {
        $plugin = Main::getInstance();
        $dataFolder = $plugin->getDataFolder();

        foreach (["settings", "mysql"] as $file) {
            $plugin->saveResource("{$file}.yml");
            $this->configs[$file] = new Config($dataFolder . "{$file}.yml", Config::YAML);
        }
    }

    public function get(string $name): ?Config {
        return $this->configs[$name] ?? null;
    }

    public function reload(string $name): void {
        $plugin = Main::getInstance();
        $file = "{$name}.yml";
        $path = $plugin->getDataFolder() . $file;

        if (file_exists($path)) {
            $this->configs[$name] = new Config($path, Config::YAML);
        }
    }

    public function reloadAll(): void {
        foreach (array_keys($this->configs) as $name) {
            $this->reload($name);
        }
    }
}
