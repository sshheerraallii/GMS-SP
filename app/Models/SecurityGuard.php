<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\Auditable;
class SecurityGuard extends Model
{
    use HasFactory, SoftDeletes;
 use Auditable;
    protected $fillable = [
        'fullname',
        'email_address',
        'phone_number',
        'license_number',
        'license_exp_date',
        'category',
        'adresse',
        'rtw_share_code',
        'visa_status',
        'ni_number',
        'driving_license',
        'car',
        'city',
        'profile_picture',
        'sia_license',
        'driving_license_doc',
        'passport',
        'evisa_ss',
        'rtw_ss',
        'proof_add1',
        'proof_add2',
        'ni_letter',
        'sort_code',
        'account_number',
    ];

    /**
     * Events this guard is assigned to
     */
    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_guard')
                    ->withTimestamps();
    }

public function guardInvoices()
{
    return $this->hasMany(\App\Models\GuardInvoice::class, 'guard_id');
}

}
