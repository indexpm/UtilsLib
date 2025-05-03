<?php

namespace IndexDev\UtilsLib\utils;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

use Vecnavium\FormsUI\SimpleForm;

class FormUtils {
    use SingletonTrait;

    public function sendSimpleForm(Player $player, string $title, string $content, array $buttons, callable $onSubmit): void {
        $form = new SimpleForm(function (Player $player, $data) use ($buttons, $onSubmit): void {
            if ($data === null) {
                return;
            }

            if (isset($buttons[$data])) {
                $onSubmit($player, $data, $buttons[$data]);
            }
        });

        $form->setTitle($title);
        $form->setContent($content);

        foreach ($buttons as $button) {
            $form->addButton($button);
        }

        $player->sendForm($form);
    }
}
