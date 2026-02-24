#!/usr/bin/env php
<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$bump = $argv[1] ?? null;

if (!in_array($bump, ['major', 'minor', 'patch'], true)) {
    fwrite(STDERR, "Usage: php build/release-tag.php [major|minor|patch]\n");
    exit(1);
}

run('git rev-parse --git-dir');

$dirtyCheck = run('git status --porcelain');

if (trim($dirtyCheck['output']) !== '') {
    fwrite(STDERR, "Working tree is not clean. Commit or stash changes before releasing.\n");
    exit(1);
}

run('git fetch --tags --quiet');

$tagList = run('git tag --list')['output'];
$bestVersion = null;
$bestPrefix = 'v';

foreach (preg_split('/\R/', trim($tagList)) as $tag) {
    if ($tag === '') {
        continue;
    }

    if (!preg_match('/^(v)?(\d+)\.(\d+)\.(\d+)$/', $tag, $matches)) {
        continue;
    }

    $currentVersion = sprintf('%d.%d.%d', (int) $matches[2], (int) $matches[3], (int) $matches[4]);
    if ($bestVersion === null || version_compare($currentVersion, $bestVersion, '>')) {
        $bestVersion = $currentVersion;
        $bestPrefix = $matches[1] ?? '';
    }
}

$baseVersion = $bestVersion ?? '0.0.0';
[$major, $minor, $patch] = array_map('intval', explode('.', $baseVersion));

switch ($bump) {
    case 'major':
        $major++;
        $minor = 0;
        $patch = 0;
        break;
    case 'minor':
        $minor++;
        $patch = 0;
        break;
    case 'patch':
        $patch++;
        break;
}

$newVersion = sprintf('%d.%d.%d', $major, $minor, $patch);
$newTag = $bestPrefix . $newVersion;

$tagExists = trim(run('git tag --list ' . escapeshellarg($newTag))['output']) !== '';

if ($tagExists) {
    fwrite(STDERR, sprintf("Tag %s already exists.\n", $newTag));
    exit(1);
}

$headCommit = trim(run('git rev-parse HEAD')['output']);
run('git tag ' . escapeshellarg($newTag) . ' ' . escapeshellarg($headCommit));

$branch = trim(run('git rev-parse --abbrev-ref HEAD')['output']);
$remote = trim(run('git config --get ' . escapeshellarg("branch.$branch.remote"))['output']);

if ($remote === '') {
    $remote = 'origin';
}

run('git push ' . escapeshellarg($remote) . ' ' . escapeshellarg($newTag));

fwrite(STDOUT, sprintf("Created and pushed tag %s (from %s, bump: %s).\n", $newTag, $baseVersion, $bump));

/**
 * @return array{output: string, exitCode: int}
 */
function run(string $command): array
{
    $output = [];
    $exitCode = 0;

    exec($command . ' 2>&1', $output, $exitCode);

    $combinedOutput = implode(PHP_EOL, $output);

    if ($exitCode !== 0) {
        fwrite(STDERR, sprintf("Command failed: %s\n%s\n", $command, $combinedOutput));
        exit($exitCode);
    }

    return [
        'output' => $combinedOutput,
        'exitCode' => $exitCode,
    ];
}
