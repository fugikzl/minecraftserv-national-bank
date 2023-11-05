<?php

namespace App\Controllers;

use App\Database\Database;

class BaseController
{
    protected int $code = 200;
    protected bool $status = true;
    protected array $data = [];
    protected ?string $message = null;

    public function setStatus(bool $status)
    {
        $this->status = $status;

        return $this;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    public function setCode(int $code)
    {
        $this->code = $code;

        return $this;
    }

    public function response()
    {
        header('Content-Type: application/json');

        echo json_encode([
            "success" => $this->status,
            "message" => $this->message,
            "data" => $this->data
        ]);

        exit($this->code);
    }
    
}