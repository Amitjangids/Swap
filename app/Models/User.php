<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;

    public $sortable = [
        'id',
        'business_name',
        'name',
        'email',
        'phone',
        'user_type',
        'isProfileCompleted',
        'created_at'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'phone',
        'parent_id',
        'email',
        'user_type',
        'merchantKey',
        'country',
        'state',
        'city',
        'addr_line1',
        'addr_line2',
        'zip',
        'password',
        'slug',
        'securityPin',
        'prevPins',
        'verify_code',
        'kyc_status',
        'national_identity_type',
        'national_identity_number',
        'dob',
        'id_expiry_date',
        'identity_back_image',
        'identity_front_image',
        'is_verify',
        'is_email_verified',
        'isBulkUser',
        'referralBy',
        'referralCode',
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'profile_image',
        'password',
        'remember_token',
        'is_kyc_done',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function Merchant($id)
    {
        return DB::table('users')
            ->where(
                'id',
                '=',
                $id
            )
            ->select('name');
    }


    public static function getCityName($id)
    {
        return DB::table('cities')
            ->where('id', '=', $id)
            ->select('name_en', 'name_ar');
    }

    public static function getAreaName($id)
    {
        return DB::table('areas')
            ->where('id', '=', $id)
            ->select('name');
    }
}
