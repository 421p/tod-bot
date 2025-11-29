<?php

namespace NapevBot\Repository;

interface TodRepositoryInterface
{
    /**
     * @return array<string,array{tod:int,channel:string,start_reminded:bool,end_reminded:bool}>
     */
    public function all();

    /**
     * @param string $boss
     * @return array|null
     */
    public function get($boss);

    /**
     * @param string $boss
     * @param array $data
     * @return void
     */
    public function set($boss, $data);

    /**
     * @param string $boss
     * @return void
     */
    public function delete($boss);

    /**
     * Persist current state to storage.
     * @return void
     */
    public function save();
}
