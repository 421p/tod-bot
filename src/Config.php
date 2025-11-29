<?php

namespace NapevBot;

class Config
{
    private $token;
    private $todFile;
    private $storageDriver;
    private $sqliteFile;

    public function __construct($token = null, $todFile = null)
    {
        $envToken = getenv('DISCORD_TOKEN');
        $this->token = $token !== null ? $token : ($envToken ?: '');
        $defaultFile = dirname(__DIR__) . '/data/tods.json';
        $this->todFile = $todFile !== null ? $todFile : $defaultFile;

        $envStorage = getenv('TOD_STORAGE');
        $this->storageDriver = $envStorage ? strtolower($envStorage) : 'json';

        $defaultSqlite = dirname(__DIR__) . '/data/tods.sqlite';
        $envSqlite = getenv('TOD_SQLITE');
        $this->sqliteFile = $envSqlite ?: $defaultSqlite;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getTodFile()
    {
        return $this->todFile;
    }

    public function getStorageDriver(): string
    {
        return $this->storageDriver;
    }

    public function getSqliteFile(): false|array|string
    {
        return $this->sqliteFile;
    }
}
