<?php

namespace NapevBot\Bot;

use Discord\Discord;
use Discord\WebSockets\Intents;
use NapevBot\Config;
use NapevBot\Repository\TodRepositoryInterface;
use NapevBot\Service\CommandHandler;
use NapevBot\Service\ReminderScheduler;

class DiscordBot
{
    private Discord $discord;
    private TodRepositoryInterface $repo;

    public function __construct(Config $config, TodRepositoryInterface $repo)
    {
        date_default_timezone_set('UTC');

        $this->repo = $repo;

        $this->discord = new Discord(array(
            'token' => $config->getToken(),
            'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT,
        ));

        $this->wireEvents();
    }

    private function wireEvents(): void
    {
        $discord = $this->discord;
        $repo = $this->repo;

        $discord->on('init', function (Discord $discord) use ($repo) {
            echo "Bot is ready." . PHP_EOL;

            // Commands
            $handler = new CommandHandler($discord, $repo);
            $discord->on('message', $handler);

            // Reminders
            (new ReminderScheduler($discord, $repo))->start();
        });
    }

    public function run()
    {
        $this->discord->run();
    }
}
