<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'setting_group', 'label', 'value', 'is_public', 'updated_by_user_id'];

    protected $casts = ['is_public' => 'boolean'];
}