<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportQueue extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'support_queue_user');
    }

    public function facebookPages(): BelongsToMany
    {
        return $this->belongsToMany(FacebookPage::class, 'facebook_page_support_queue', 'support_queue_id', 'facebook_page_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'support_queue_id');
    }
}
