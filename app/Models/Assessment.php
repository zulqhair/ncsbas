<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Assessment extends Model { protected $fillable = ['user_id','status','overall_score','overall_maturity_level']; public function user(): BelongsTo { return $this->belongsTo(User::class); } public function responses(): HasMany { return $this->hasMany(AssessmentResponse::class); } public function elementResults(): HasMany { return $this->hasMany(AssessmentElementResult::class); } }
