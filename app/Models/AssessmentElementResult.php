<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AssessmentElementResult extends Model { protected $fillable = ['assessment_id','element_number','element_name','yes_count','maturity_score','maturity_level']; }
