<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Public properties for dynamic assignment from BiblioCommons API.
     * No database persistence - users are transient objects fetched fresh from API.
     */
    public $id;

    public $name;

    public $email;

    public $password;

    public $email_verified_at;

    /**
     * Mark as existing to prevent Laravel from attempting to save to database.
     * These are transient objects - session management is handled by BiblioCommons.
     */
    public $exists = true;

    /**
     * No fillable needed since we're not persisting to database.
     * Properties are set directly in BiblioUserProvider::createUserFromApiData()
     */
    protected $fillable = [];

    /**
     * Indicates if the model's ID is auto-incrementing.
     * Set to false since IDs come from BiblioCommons API.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     * BiblioCommons borrower IDs are strings.
     */
    protected $keyType = 'string';

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }
}
