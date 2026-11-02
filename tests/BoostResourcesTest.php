<?php

/**
 * Checks the Laravel Boost guideline and skill that ship in resources/boost, so they don't drift from the package.
 */

$root = dirname(__DIR__);
$guideline = "{$root}/resources/boost/guidelines/core.blade.php";
$skill = "{$root}/resources/boost/skills/mixable-development/SKILL.md";

/**
 * @return list<string>
 */
function boostPhpSnippets(string $path): array
{
    $content = file_get_contents($path);

    $pattern = str_ends_with($path, '.blade.php')
        ? '#<code-snippet[^>]*lang="php"[^>]*>(.*?)</code-snippet>#s'
        : '#```php\n(.*?)```#s';

    preg_match_all($pattern, $content, $matches);

    return $matches[1];
}

it('ships a short guideline that points to the skill', function () use ($guideline) {
    expect($guideline)->toBeFile();

    $content = file_get_contents($guideline);

    expect(substr_count($content, "\n"))->toBeLessThan(60);
    expect($content)->toContain('mixable-development');
});

it('ships a skill whose frontmatter names it', function () use ($skill) {
    expect($skill)->toBeFile();

    preg_match('/\A---\n(.*?)\n---\n/s', file_get_contents($skill), $frontmatter);

    expect($frontmatter[1] ?? '')
        ->toContain('name: mixable-development')
        ->toMatch('/^description: .{40,}$/m');
});

it('has PHP examples that parse', function (string $path) {
    $snippets = boostPhpSnippets($path);

    expect($snippets)->not->toBeEmpty();

    foreach ($snippets as $index => $snippet) {
        $file = tempnam(sys_get_temp_dir(), 'boost-snippet');
        file_put_contents($file, "<?php\n\n{$snippet}");

        exec(sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($file)), $output, $exitCode);
        unlink($file);

        expect($exitCode)->toBe(0, sprintf('Snippet #%d in %s does not parse: %s', $index + 1, basename($path), implode("\n", $output)));
    }
})->with([
    'guideline' => [$guideline],
    'skill' => [$skill],
]);

it('only mentions functions and classes the package has', function (string $path) {
    preg_match_all('/AdrianSpacely\\\\Mixable\\\\(\w+)/', file_get_contents($path), $matches);

    foreach (array_unique($matches[1]) as $name) {
        $exists = class_exists("AdrianSpacely\\Mixable\\{$name}")
            || trait_exists("AdrianSpacely\\Mixable\\{$name}")
            || function_exists("AdrianSpacely\\Mixable\\{$name}");

        expect($exists)->toBe(true, "AdrianSpacely\\Mixable\\{$name} doesn't exist.");
    }
})->with([
    'guideline' => [$guideline],
    'skill' => [$skill],
]);
