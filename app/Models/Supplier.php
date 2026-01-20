<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /* ================= RELATIONSHIPS ================= */

    // Supplier has many Events
    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
