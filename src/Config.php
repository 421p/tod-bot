<?php

namespace NapevBot;

class Config
{
    private $token;
    private $todFile;

    public function __construct($token = null, $todFile = null)
    {
        $envToken = getenv('DISCORD_TOKEN');
        $this->token = $token !== null ? $token : ($envToken ? $envToken : '');
        $defaultFile = dirname(__DIR__) . '/tods.json';
        $this->todFile = $todFile !== null ? $todFile : $defaultFile;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getTodFile()
    {
        return $this->todFile;
    }
}
