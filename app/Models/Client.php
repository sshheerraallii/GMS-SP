<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Auditable;
class Client extends Model
{
    use HasFactory;
   use Auditable;
    // Fields that are allowed to be mass assigned
    protected $fillable = [
        'name',
        'email_address',
        'contact_number',
        'payment_terms',
        'type',
    ];

    // Client has many Events
    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
