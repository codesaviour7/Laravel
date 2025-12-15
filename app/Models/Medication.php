<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medication extends Model
{
    protected $fillable = [
        'user_id',
        'rxcui',
        'name',
        'ingredient_base_names',
        'dose_form_names',
    ];

    protected $casts = [
        'ingredient_base_names' => 'array',
        'dose_form_names' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

