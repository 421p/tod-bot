#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use NapevBot\Bot\DiscordBot;
use NapevBot\Config;
use NapevBot\Repository\JsonTodRepository;
use NapevBot\Repository\SqliteTodRepository;

$config = new Config();

if (!$config->getToken()) {
    fwrite(STDERR, "DISCORD_TOKEN is not set. Please export DISCORD_TOKEN env variable.\n");
    exit(1);
}

// Select storage backend based on env TOD_STORAGE (json|sqlite)
if ($config->getStorageDriver() === 'sqlite') {
    $repo = new SqliteTodRepository($config->getSqliteFile());
} else {
    $repo = new JsonTodRepository($config->getTodFile());
}

$bot = new DiscordBot($config, $repo);
$bot->run();
