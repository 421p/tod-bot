<?php

namespace NapevBot\Repository;

class JsonTodRepository implements TodRepositoryInterface
{
    private $file;
    private $data = [];

    public function __construct($file)
    {
        $this->file = $file;
        $this->load();
    }

    private function load()
    {
        if (!file_exists($this->file)) {
            $this->data = [];
            return;
        }
        $json = @file_get_contents($this->file);
        $arr = $json ? json_decode($json, true) : null;
        $this->data = is_array($arr) ? $arr : [];
    }

    public function all()
    {
        return $this->data;
    }

    public function get($boss)
    {
        return isset($this->data[$boss]) ? $this->data[$boss] : null;
    }

    public function set($boss, $data)
    {
        $this->data[$boss] = $data;
    }

    public function delete($boss)
    {
        if (isset($this->data[$boss])) {
            unset($this->data[$boss]);
        }
    }

    public function save()
    {
        @file_put_contents($this->file, json_encode($this->data, JSON_PRETTY_PRINT));
    }
}
