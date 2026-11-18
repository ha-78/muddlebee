<?php

namespace App\Support;

/**
 * The avatar a user picked, stored as JSON on the user.
 * Only the "emoji" type exists so far, and any single emoji may be picked: {"type":"emoji","emoji":"🐝"}.
 * It sits on the same section colour as the letter avatars.
 * Anything unknown or broken falls back to the first letter of the name.
 */
final readonly class Avatar
{
    public const string TYPE_EMOJI = 'emoji';

    /** Room for the longest emoji sequences (families, flags with tags), and not much more. */
    public const int MAX_BYTES = 64;

    private function __construct(
        public string $emoji,
    ) {}

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function from(?array $data): ?self
    {
        if (($data['type'] ?? null) !== self::TYPE_EMOJI) {
            return null;
        }

        $emoji = $data['emoji'] ?? null;

        return self::isEmoji($emoji) ? new self($emoji) : null;
    }

    /**
     * Exactly one emoji as people see it: one grapheme (so 👨‍👩‍👧, 👍🏽 and 🇸🇪 count as one)
     * that holds a pictographic character, a flag letter or a keycap. Letters and digits alone don't count.
     */
    public static function isEmoji(mixed $value): bool
    {
        if (! is_string($value) || $value === '' || strlen($value) > self::MAX_BYTES) {
            return false;
        }

        return self::graphemeCount($value) === 1
            && preg_match('/[\p{Extended_Pictographic}\x{1F1E6}-\x{1F1FF}\x{20E3}]/u', $value) === 1;
    }

    /**
     * Characters as people see them. intl (ICU) counts like browsers do; PCRE's \X is only a fallback,
     * since some PCRE versions join emoji that stand side by side into one.
     */
    private static function graphemeCount(string $value): int
    {
        if (function_exists('grapheme_strlen')) {
            $count = grapheme_strlen($value);

            return is_int($count) ? $count : 0;
        }

        return (int) preg_match_all('/\X/u', $value);
    }

    /**
     * @return array{type: string, emoji: string}
     */
    public static function emoji(string $emoji): array
    {
        return ['type' => self::TYPE_EMOJI, 'emoji' => $emoji];
    }
}
