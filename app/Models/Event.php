<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Supplier;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Invoice;

class Event extends Model
{
    use HasFactory;
    // inside class Event extends Model
    use Auditable;

    protected $fillable = [
        'event_name',
        'address',
        'client_id',
        'client_type', // ✅ moved from clients -> events
        'charge_rate',
        'invoice_date',
        'pay_rate',
        'instructions',
        'client_contact',

        // NEW FIELDS
        'start_date',
        'end_date',
        'supplier_id',
        'payment_terms',

        // Multi-step form fields
        'daily_guards',   // array: date => number of guards
        'shift_mode',     // string: 'same' or 'different'
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'daily_guards' => 'array', // important for Page 2
    ];

    /* ================= RELATIONSHIPS ================= */

    // Event belongs to a Client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): HasOne
{
    return $this->hasOne(Invoice::class);
}


public function guardInvoices()
{
    return $this->hasMany(\App\Models\GuardInvoice::class);
}
    // Event belongs to a Supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Event has many schedules (daily hours)
    public function schedules()
    {
        return $this->hasMany(EventSchedule::class);
    }

    public function shifts()
    {
        return $this->hasMany(\App\Models\EventShift::class);
    }

    // Event has many assigned Security Guards (many-to-many)
    public function guards()
    {
        return $this->belongsToMany(
            SecurityGuard::class,
            'event_guard',
            'event_id',
            'guard_id'
        );
    }
}
