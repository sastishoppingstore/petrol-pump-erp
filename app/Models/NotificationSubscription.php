<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSubscription extends Model
{
    protected $table = 'notification_subscriptions';
    
    protected $fillable = [
        'user_id',
        'branch_id',
        'notification_type',
        'email_enabled',
        'sms_enabled',
        'email',
        'phone',
        'threshold',
        'description',
        'active',
    ];
    
    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'active' => 'boolean',
        'threshold' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
