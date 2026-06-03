<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasRoles;
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'chat_id',
        'renewal_id',
        'alert_id',
        'weekly_id',
        'alert_mo_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function operators()
    {
        return $this->belongsToMany(Operator::class, 'user_has_operators', 'id_user', 'id_operator');
    }

    // Scopes untuk kemudahan
    #[Scope]
    public function HasOperator($query, $operatorId)
    {
        return $query->whereHas('operators', fn($q) => $q->where('operators.id', $operatorId));
    }

    #[Scope]
    public function OnlyOperator($query, $operatorId)
    {
        // pengguna yang punya operatorId dan total operator_count == 1
        return $query->whereHas('operators', fn($q) => $q->where('operators.id', $operatorId))
                     ->withCount('operators')
                     ->having('operators_count', 1);
    }
}
