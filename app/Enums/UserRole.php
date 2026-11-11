<?php

namespace App;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Manager => 'Manager',
            self::Member => 'Project member',
        };
    }   
}
