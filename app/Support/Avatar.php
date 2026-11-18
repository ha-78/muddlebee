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

    /**
     * The emoji blocks of Unicode, spelled out as ranges. \p{Extended_Pictographic} would be shorter,
     * but older PCRE versions (like the one on the server) don't know that property and fail to compile.
     * Covers pictographs and symbols (U+1F000–U+1FAFF, including flag letters), the older symbol
     * and dingbat blocks (U+2600–U+27BF), the scattered emoji before them, and the keycap mark (U+20E3).
     */
    private const string EMOJI_PATTERN = '/[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{20E3}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}\x{21AA}'
        .'\x{231A}\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}\x{25AB}\x{25B6}\x{25C0}'
        .'\x{25FB}-\x{25FE}\x{2600}-\x{27BF}\x{2934}\x{2935}\x{2B05}-\x{2B07}\x{2B1B}\x{2B1C}\x{2B50}\x{2B55}'
        .'\x{3030}\x{303D}\x{3297}\x{3299}\x{1F000}-\x{1FAFF}\x{1FC00}-\x{1FFFD}]/u';

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
            && preg_match(self::EMOJI_PATTERN, $value) === 1;
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
