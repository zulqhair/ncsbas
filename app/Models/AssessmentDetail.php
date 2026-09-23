<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentDetail extends Model
{
    protected $fillable = ['assessment_id', 'label', 'value'];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }
}
