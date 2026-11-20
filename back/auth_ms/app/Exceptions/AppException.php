<?php

namespace App\Exceptions;

use RuntimeException;

class AppException extends RuntimeException
{
    public function __construct(string $mensaje, private int $codigo_estado = 400, private ?array $errores = null)
    {
        parent::__construct($mensaje);
    }

    public function get_codigo_estado(): int
    {
        return $this->codigo_estado;
    }

    public function get_errores(): ?array
    {
        return $this->errores;
    }
}
