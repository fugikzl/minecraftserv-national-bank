<?php

namespace App\Core\Contracts;

interface Singleton
{
    static function getInstance() : static;
}