<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoReport extends Model
{
    protected $table = 'auto_reports';
    
    protected $fillable = [
        'branch_id',
        'period',
        'report_date',
        'summary_json',
        'data_json',
        'status',
        'pdf_size_bytes',
        'excel_size_bytes',
        'sent_at',
        'sent_by',
        'recipient_email',
        'recipient_phone',
    ];
    
    protected $casts = [
        'summary_json' => 'array',
        'data_json' => 'array',
        'recipient_email' => 'array',
        'recipient_phone' => 'array',
        'report_date' => 'date',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    
    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
