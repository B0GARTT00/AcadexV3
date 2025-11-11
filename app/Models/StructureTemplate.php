<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $template_key
 * @property string $label
 * @property string|null $description
 * @property array $structure_config
 * @property bool $is_system_default
 * @property bool $is_deleted
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class StructureTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_key',
        'label',
        'description',
        'structure_config',
        'is_system_default',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'structure_config' => 'array',
        'is_system_default' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}