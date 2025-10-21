<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradesFormulaWeight extends Model
{
    use HasFactory;

    protected $fillable = [
        'grades_formula_id',
        'activity_type',
        'weight',
    ];

    protected $casts = [
        'weight' => 'float',
    ];

    public function formula()
    {
        return $this->belongsTo(GradesFormula::class, 'grades_formula_id');
    }
}
