<?php

namespace App\Enums;

enum MealStatus: string
{
    case Wishlist = 'wishlist';
    case Shortlist = 'shortlist';
    case Planned = 'planned';
    case Discarded = 'discarded';

    public function label(): string
    {
        return __('meals.statuses.'.$this->value);
    }

    /**
     * The statuses that have a tab on the meals page, in order.
     *
     * @return list<self>
     */
    public static function tabs(): array
    {
        return [self::Wishlist, self::Shortlist, self::Planned, self::Discarded];
    }

    /**
     * Where a meal can be moved from this status by hand.
     * Planning and unplanning are separate and not listed here.
     *
     * @return list<self>
     */
    public function moveTargets(): array
    {
        return match ($this) {
            self::Wishlist => [self::Shortlist, self::Discarded],
            self::Shortlist => [self::Wishlist, self::Discarded],
            self::Discarded => [self::Wishlist],
            self::Planned => [],
        };
    }

    public function canMoveTo(self $target): bool
    {
        return in_array($target, $this->moveTargets(), true);
    }

    /**
     * The button text for moving a meal from this status to the target.
     */
    public function moveLabel(self $target): string
    {
        if ($this === self::Discarded && $target === self::Wishlist) {
            return __('meals.restore');
        }

        return __('meals.move_to.'.$target->value);
    }

    public static function fromTab(?string $value): self
    {
        $status = self::tryFrom((string) $value);

        return in_array($status, self::tabs(), true) ? $status : self::Wishlist;
    }

    public function tabLabel(): string
    {
        return __('meals.tabs.'.$this->value);
    }

    public function emptyMessage(): string
    {
        return __('meals.empty_by_status.'.$this->value);
    }
}
