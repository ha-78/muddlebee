<?php

namespace App\Enums;

/**
 * Seasonal decorations, turned on for the whole site with APP_SEASON in .env (e.g. APP_SEASON=christmas).
 * They only add decorations; the colors and the light/dark theme stay as they are.
 */
enum Season: string
{
    case Christmas = 'christmas';

    public static function current(): ?self
    {
        return self::tryFrom(strtolower(trim((string) config('app.season'))));
    }

    public static function is(self $season): bool
    {
        return self::current() === $season;
    }
}
