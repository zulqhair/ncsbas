<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AssessmentResponse extends Model { protected $fillable = ['assessment_id','ncsb_question_id','answer']; }
