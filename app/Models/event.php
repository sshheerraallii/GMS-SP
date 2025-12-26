<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_name',
        'address',
        'client_id',
        'charge_rate',
        'invoice_date',
        'pay_rate',
        'instructions',
        'client_contact'
    ];

    // Event belongs to a Client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Event has many assigned SecurityGuards (many-to-many)
    public function guards()
    {
        return $this->belongsToMany(SecurityGuard::class, 'event_guard', 'event_id', 'guard_id');
    }
}
