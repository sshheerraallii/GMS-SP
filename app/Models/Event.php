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

class Event extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'event_name',
        'address',
        'client_id',
        'client_type',
        'charge_rate',
        'invoice_date',
        'pay_rate',
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
