<?php

namespace App\Core\Contracts;

interface Database
{
    public function select(string $table, string $condition = null) : array|false;
    public function delete(string $table, string $condition);
    public function update(string $table, array $data, string $condition);
    public function insert(string $table, array $data) : string|false;
}