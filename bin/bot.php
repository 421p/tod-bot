#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use NapevBot\Bot\DiscordBot;
use NapevBot\Config;
use NapevBot\Repository\JsonTodRepository;

$config = new Config();

if (!$config->getToken()) {
    fwrite(STDERR, "DISCORD_TOKEN is not set. Please export DISCORD_TOKEN env variable.\n");
    exit(1);
}

$repo = new JsonTodRepository($config->getTodFile());

$bot = new DiscordBot($config, $repo);
$bot->run();
