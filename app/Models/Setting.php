<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const TYPE_TEXT = 'text';
    public const TYPE_NUMBER = 'number';

    protected $fillable = [
        'group', 'key', 'value', 'value_numeric', 'value_type', 'description', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'decimal:3',
            'is_public' => 'boolean',
        ];
    }
}
