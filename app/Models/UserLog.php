<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $event_type
 * @property string|null $browser
 * @property string|null $device
 * @property string|null $platform
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserLog extends Model
{
    use HasFactory;

    protected $table = 'user_logs';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'event_type',
        'browser',
        'device',     
        'platform',   
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
