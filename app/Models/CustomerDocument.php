<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A document attached to a customer khata: CNIC copy, NTN / tax
 * document, or any other paperwork. Stored on the `public` disk,
 * following the same pattern as the station Document Vault
 * (App\Models\StationDocument).
 */
class CustomerDocument extends Model
{
    public const CATEGORY_CNIC = 'cnic';
    public const CATEGORY_NTN = 'ntn';
    public const CATEGORY_OTHER = 'other';

    protected $fillable = [
        'branch_id', 'customer_id', 'title', 'category',
        'file_path', 'note', 'uploaded_by',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return array<string, string> category => label
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_CNIC => __('sales.collection.cat_cnic'),
            self::CATEGORY_NTN => __('sales.collection.cat_ntn'),
            self::CATEGORY_OTHER => __('sales.collection.cat_other'),
        ];
    }

    public function categoryLabel(): string
    {
        return self::categories()[$this->category] ?? ucfirst((string) $this->category);
    }

    /** Public URL of the stored file (public disk). */
    public function fileUrl(): string
    {
        return asset('storage/' . ltrim($this->file_path, '/'));
    }
}
