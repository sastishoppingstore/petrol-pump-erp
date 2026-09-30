<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';
    
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'updated_by',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Relationship: who updated this setting
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

