<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\ModelFilters\CustomerFilter;
use Database\Factories\UserFactory;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['first_name', 'last_name', 'email', 'password', 'role', 'contact_number', 'messenger_name'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Filterable, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected static function booted()
    {
        static::creating(function (User $user) {
            $user->uuid = Str::uuid()->toString();
        });
    }

    public function modelFilter()
    {
        return $this->provideFilter(CustomerFilter::class);
    }

    public function orders()
    {
        return $this->hasMany(PasabuyOrder::class);
    }

    public function fullName()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getIsApprovedAttribute()
    {
        return $this->role === 'admin' || $this->approved_at !== null;
    }

    protected function casts()
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
