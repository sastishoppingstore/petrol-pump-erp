<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    use HasFactory;

    /*
     * Canonical schema: migration 2026_01_01_007600 (`bank_transactions`).
     * Ek hi `amount` column hai; paisa kis taraf gaya ye `type` batata hai,
     * aur har row ke saath `balance_before` / `balance_after` ki chain
     * hoti hai. (Purana debit_amount/credit_amount wala schema khatam.)
     */

    /** @var list<string> Types jo account me paisa JAMAA karte hain. */
    public const CREDIT_TYPES = ['DEPOSIT', 'TRANSFER_IN', 'MARKUP', 'CHEQUE_DEPOSIT'];

    /** @var list<string> Types jo account se paisa NIKAALTE hain. */
    public const DEBIT_TYPES = ['WITHDRAWAL', 'TRANSFER_OUT', 'CHARGES', 'CHEQUE_BOUNCE'];

    // `type` enum — migration 007600 ke har value ke liye ek constant.
    public const TYPE_DEPOSIT = 'DEPOSIT';
    public const TYPE_WITHDRAWAL = 'WITHDRAWAL';
    public const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    public const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';
    public const TYPE_CHARGES = 'CHARGES';
    public const TYPE_MARKUP = 'MARKUP';
    public const TYPE_CHEQUE_DEPOSIT = 'CHEQUE_DEPOSIT';
    public const TYPE_CHEQUE_BOUNCE = 'CHEQUE_BOUNCE';

    public const STATUS_COMPLETED = 'COMPLETED';

    /*
     * Deprecated legacy aliases — purane naamon se likha hua (dormant)
     * code fatal na ho aur ghalat enum value kabhi insert na ho, is liye
     * ye canonical values ki taraf point karte hain. Naye code me
     * canonical TYPE_* / STATUS_* hi istemal karen.
     */
    /** @deprecated Use TYPE_CHARGES ('BANK_CHARGES' enum me hai hi nahi). */
    public const TYPE_BANK_CHARGES = self::TYPE_CHARGES;

    /** @deprecated Use TYPE_CHEQUE_BOUNCE ('CHEQUE_BOUNCED' enum me hai hi nahi). */
    public const TYPE_CHEQUE_BOUNCED = self::TYPE_CHEQUE_BOUNCE;

    /** @deprecated Issued cheque clear hona paisa bahar jana hai; live code (ChequeService) is ke liye TYPE_WITHDRAWAL, received cheque ke liye TYPE_CHEQUE_DEPOSIT istemal karta hai. */
    public const TYPE_CHEQUE_CLEARED = self::TYPE_WITHDRAWAL;

    protected $fillable = [
        'branch_id',
        'bank_account_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reference_number',
        'transaction_date',
        'description',
        'performed_by',
        'slip_path',
        'shift_id',
        'related_account_id',
        'reconciled',
        'reconciled_at',
        'reconciliation_id',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'datetime',
        'reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function relatedAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'related_account_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /** Kya ye transaction account me paisa jamaa karti hai? */
    public function isCredit(): bool
    {
        return in_array($this->type, self::CREDIT_TYPES, true);
    }

    public function markReconciled(): void
    {
        $this->update([
            'reconciled' => true,
            'reconciled_at' => now(),
        ]);
    }
}
