<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $fillable = ['name', 'email', 'company', 'phone'];

    /**
     * Get the projects that belong to this client.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
