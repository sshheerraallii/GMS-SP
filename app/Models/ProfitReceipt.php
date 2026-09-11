<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * V3-P7: a profit amount received from a client on a given date.
 * Entered by hand on the executive page (Super Admin only).
 */
class ProfitReceipt extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'client_id',
        'amount',
        'received_on',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'received_on' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
