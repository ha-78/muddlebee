<?php

use Illuminate\Support\Facades\File;

/*
 * Colors live in one place: the palette and the role tokens in resources/css/app.css.
 * Views and scripts use the role utilities (bg-surface, text-ink, …) or data-color,
 * never a palette variable, a hex color or rgb()/rgba().
 */

// Datasets are built before the app boots, so the paths come from this file's place, not resource_path().
dataset('design files', function (): array {
    $resources = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'resources';
    $files = [];

    foreach (['views', 'js'] as $directory) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resources.DIRECTORY_SEPARATOR.$directory, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (in_array($file->getExtension(), ['php', 'js'], true)) {
                $files[str_replace('\\', '/', substr($file->getPathname(), strlen($resources) + 1))] = [$file->getPathname()];
            }
        }
    }

    ksort($files);

    return $files;
});

it('keeps colors out of views and scripts', function (string $path) {
    $source = file_get_contents($path);

    expect($source)
        ->not->toContain('--color-')
        ->not->toMatch('/\brgba?\(/i')
        ->not->toMatch('/(?<![&\w])(?<!href=")#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})\b/i');
})->with('design files');

it('keeps the old tokens and per-section classes out of the stylesheet', function () {
    $css = File::get(resource_path('css/app.css'));

    foreach (['--badge', '--notice', '--panel', '--form:', '--button', '--chip', '--pill', '--status', '--danger', '--reply', '--aside', '--row', '--message-', '--calendar', '--header', '--section-mark'] as $token) {
        expect($css)->not->toContain($token);
    }

    expect($css)->not->toMatch('/\.(bookmarks|lists|meals|threads|account|login|home|welcome)-[a-z-]+|\.data-table/');
});
