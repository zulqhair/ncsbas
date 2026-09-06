<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NcsbQuestion extends Model
{
    protected $fillable = ['number', 'domain', 'category', 'element_number', 'element_name', 'question'];
}
