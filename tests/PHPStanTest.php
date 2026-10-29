<?php

/**
 * Runs PHPStan with the package's extension over tests/PHPStan/data, where each
 * line ending in `// type: ...` or `// error: ...` states what PHPStan should report.
 */
it('reports mixin problems to PHPStan', function () {
    $root = dirname(__DIR__);

    exec(sprintf(
        '%s %s analyse -c %s --error-format=json --no-progress --memory-limit=1G 2>/dev/null',
        escapeshellarg(PHP_BINARY),
        escapeshellarg("{$root}/vendor/bin/phpstan"),
        escapeshellarg("{$root}/tests/PHPStan/extension-test.neon"),
    ), $output);

    $report = json_decode(implode("\n", $output), true);
    expect($report)->toBeArray();
    expect($report['errors'])->toBe([]);

    foreach (glob("{$root}/tests/PHPStan/data/*.php") as $file) {
        $expected = [];

        foreach (file($file) as $index => $line) {
            if (preg_match('#// (type|error): (.+)$#', rtrim($line), $match)) {
                $expected[] = sprintf('%d: %s', $index + 1, $match[1] === 'type' ? "Dumped type: {$match[2]}" : $match[2]);
            }
        }

        $actual = array_map(
            fn (array $message) => sprintf('%d: %s', $message['line'], $message['message']),
            $report['files'][realpath($file)]['messages'] ?? [],
        );

        expect($actual)->toBe($expected, basename($file));
    }
});
