<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Pesos = 'pesos';
    case Dolares = 'dolares';
    case Transferencia = 'transferencia';

    public function label(): string
    {
        return match ($this) {
            self::Pesos => 'Pesos',
            self::Dolares => 'Dólares',
            self::Transferencia => 'Transferencia',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pesos => 'success',
            self::Dolares => 'info',
            self::Transferencia => 'warning',
        };
    }
}
