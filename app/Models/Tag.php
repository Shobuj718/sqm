<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    public $timestamps = false;
    protected $table = 'labels';

    protected $fillable = [
        'name',
        'category',
    ];

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'label_ticket', 'label_id', 'ticket_id');
    }
}
