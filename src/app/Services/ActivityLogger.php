<?php

namespace App\Services;

class ActivityLogger
{
    public static function log($description, $user = null, $properties = [])
    {
        activity()
            ->causedBy($user)
            ->withProperties(array_merge([
                'ip' => request()->ip(),
                'url' => request()->fullUrl(),
                'user_agent' => request()->userAgent(),
            ], $properties))
            ->log($description);
    }
}
