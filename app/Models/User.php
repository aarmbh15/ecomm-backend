<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use App\Notifications\CustomResetPasswordNotification;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'gender',
        'type',
        'photo',
        'status',
        'activate_code',
        // 'reset_code',
        'created_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'activate_code',
        // 'reset_code',
        'remember_token', // kept in case you use remember me functionality later
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // 'email_verified_at' => 'datetime', // kept for potential future use (Laravel default)
            'password' => 'hashed',
            'status' => 'boolean',
            'type' => 'integer',           // tinyInteger is cast to integer
            // 'created_at' => 'date',        // date column
            'created_at' => 'datetime',    // from timestamps()
            'updated_at' => 'datetime',    // from timestamps()
        ];
    }

    /**
     * Override the default timestamp columns if needed.
     * Not strictly required here since you use standard timestamps(),
     * but kept for clarity.
     */
    // public function getCreatedAtAttribute($value)
    // {
    //     return $value; // standard created_at from timestamps()
    // }

    /**
     * Optional: Accessor for full name
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Optional: Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Optional: Scope for admins
     */
    public function scopeAdmin($query)
    {
        return $query->where('type', 1);
    }

    // Required by CanResetPassword trait
    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    public function sendPasswordResetNotification($token)
    {
        // $this->notify(new \App\Notifications\CustomResetPasswordNotification($token));
        $this->notify(new CustomResetPasswordNotification($token));
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}