<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaBlastSetting extends Model
{
    protected $fillable = [
        'cloud_enabled',
        'web_enabled',
        'default_backend',
        'web_session_name',
        'send_delay_seconds',
        'message_footer',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'cloud_enabled' => 'boolean',
            'web_enabled' => 'boolean',
            'send_delay_seconds' => 'integer',
        ];
    }

    public static function current(): self
    {
        $existing = self::query()->first();
        if ($existing) {
            return $existing;
        }

        return self::query()->create([
            'cloud_enabled' => false,
            'web_enabled' => false,
            'default_backend' => 'auto',
            'web_session_name' => 'main',
            'send_delay_seconds' => 2,
        ]);
    }
}
