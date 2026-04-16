<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookPage extends Model
{
    protected $fillable = [
        'page_id',
        'page_name',
        'page_token',
        'page_category',
    ];

    /**
     * Get all support tickets for this Facebook page.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'facebook_page_id');
    }
}
