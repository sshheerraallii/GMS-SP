<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Supplier;
use App\Models\Concerns\Auditable;
use App\Models\Invoice;
use App\Models\SecurityGuard;

class Event extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'event_name',
        'address',
        'client_id',
        'client_type',
        'charge_rate',
        'charge_rate_sia',
        'charge_rate_steward',
        'invoice_date',
        'pay_rate',
        'pay_rate_sia',
        'pay_rate_steward',
        'instructions',
        'client_contact',
        'start_date',
        'end_date',
        'supplier_id',
        'payment_terms',
        'daily_guards',
        'shift_mode',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'daily_guards' => 'array',
    ];

    /**
     * V3-P3: single source of truth for rate resolution.
     *
     *   category rate if set  ->  else base rate if set  ->  else 0
     *
     * $kind is 'pay' or 'charge'. $category is a value from
     * security_guards.category ('SIA' | 'Steward'); NULL, empty or any
     * unrecognised value falls through to the base rate, which is what
     * keeps every pre-V3 event billing exactly as it does today.
     *
     * Every consumer (invoice generators, reports, executive, exports)
     * must call this rather than reading the columns directly, so the
     * fallback logic exists in one place only.
     */
    public function rateFor(?string $category, string $kind): float
    {
        $kind = strtolower(trim($kind));

        if (!in_array($kind, ['pay', 'charge'], true)) {
            throw new \InvalidArgumentException("Event::rateFor() \$kind must be 'pay' or 'charge', got '{$kind}'.");
        }

        $suffix = match (strtolower(trim((string) $category))) {
            'sia'     => 'sia',
            'steward' => 'steward',
            default   => null,
        };

        if ($suffix !== null) {
            $categoryRate = $this->{$kind . '_rate_' . $suffix};

            if ($categoryRate !== null && $categoryRate !== '') {
                return (float) $categoryRate;
            }
        }

        $baseRate = $this->{$kind . '_rate'};

        return ($baseRate !== null && $baseRate !== '') ? (float) $baseRate : 0.0;
    }

    /**
     * Convenience wrapper for callers that hold a guard rather than a
     * category string. A missing guard resolves to the base rate.
     */
    public function rateForGuard(?SecurityGuard $guard, string $kind): float
    {
        return $this->rateFor($guard?->category, $kind);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignmentSlots(): HasMany
    {
        return $this->hasMany(\App\Models\EventAssignmentSlot::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Invoice::class, 'invoice_event')
            ->withTimestamps();
    }

    public function guardInvoices()
    {
        return $this->hasMany(\App\Models\GuardInvoice::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function schedules()
    {
        return $this->hasMany(EventSchedule::class);
    }

    public function shifts()
    {
        return $this->hasMany(\App\Models\EventShift::class);
    }

    public function guardPayments(): HasMany
    {
        return $this->hasMany(EventGuardPayment::class);
    }

    public function expenses()
    {
        return $this->hasMany(\App\Models\EventExpense::class);
    }

    public function guards()
    {
        return $this->belongsToMany(
            SecurityGuard::class,
            'event_guard',
            'event_id',
            'guard_id'
        )->withTimestamps();
    }
}
