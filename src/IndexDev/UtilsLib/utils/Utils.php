<?php

namespace IndexDev\UtilsLib\utils;

use pocketmine\command\CommandSender;
use pocketmine\utils\SingletonTrait;

use IndexDev\UtilsLib\Main;

class Utils {
    use SingletonTrait;

    public function getPrefix(): string {
        return FileManager::get("settings")->get("prefix", "§8[§bUtilsLib§8]§r");
    }

    public function log(string $message): void {
        if (FileManager::get("settings")->get("debug", false)) {
            Main::getInstance()->getLogger()->info($this->getPrefix() . " " . $message);
        }
    }

    public function sendMessage(CommandSender $sender, string $message): void {
        $sender->sendMessage($this->getPrefix() . " " . $message);
    }

    public function reload(): void {
        FileManager::reload("settings");
        FileManager::reload("mysql");
        $this->log("§aConfiguraciones recargadas.");
    }
}
