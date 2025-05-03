<?php

namespace IndexDev\UtilsLib\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

use IndexDev\UtilsLib\utils\Utils;
use IndexDev\UtilsLib\utils\Database;

class UtilsCommand extends Command {

    public function __construct() {
        parent::__construct("utils", "Comandos de utilidades");
    }

    public function execute(CommandSender $sender, string $label, array $args): void {
        if (!$sender->hasPermission("utilslib.command")) {
            $sender->sendMessage("§cNo tienes permiso.");
            return;
        }

        if (!isset($args[0])) {
            $sender->sendMessage("§eUso: /utils reload | dbtest");
            return;
        }

        switch (strtolower($args[0])) {
            case "reload":
                Utils::reload();
                $sender->sendMessage("§aConfiguración recargada.");
                break;

            case "dbtest":
                Database::insertLog("Test desde comando");
                $sender->sendMessage("§aRegistro insertado en la base de datos.");
                break;

            default:
                $sender->sendMessage("§cComando inválido.");
                break;
        }
    }
}
