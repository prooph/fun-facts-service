<?php
declare(strict_types = 1);

namespace ProophFunFacts\Shared\Fn;

function createFunFactsFile(string $filename, array $facts): void {
    $facts = array_merge(['github_stars' => 0, 'downloads' => 0, 'gitter_people' => []], $facts);

    file_put_contents($filename, json_encode(['fun_facts' => $facts]));
}

function updateFunFactsFile(string $filename, array $facts): void {
    if(!file_exists($filename)) {
        throw new \RuntimeException('File could not be found: ' . $filename);
    }

    $fileNotUpdated = true;
    $lockFile = dirname($filename) . '/.lock';
    $retry = 0;

    while ($fileNotUpdated) {
        if($retry > 3) {
            throw new \RuntimeException("Tried to update fun facts file 3 times, but it is still locked. Check " . $lockFile);
        }

        if(!file_exists($lockFile)) {
            touch($lockFile);

            $oldFacts = json_decode(file_get_contents($filename), true);

            $facts = array_merge($oldFacts['fun_facts'], $facts);

            file_put_contents($filename, json_encode(['fun_facts' => $facts]));

            unlink($lockFile);
            $fileNotUpdated = false;
        } else {
            $retry++;
            sleep(1);
        }
    }
}

function upsertPackageStatsInStaticFile(int $githubStars, int $downloads, string $filename): void {
    $facts = ['github_stars' => $githubStars, 'downloads' => $downloads];

    if(!file_exists($filename)) {
        createFunFactsFile($filename, $facts);
    } else {
        updateFunFactsFile($filename, $facts);
    }
}

function upsertGitterPeopleInStaticFile(array $gitterPeople, string $filename): void {
    $facts = ['gitter_people' => $gitterPeople];

    if(!file_exists($filename)) {
        createFunFactsFile($filename, $facts);
    } else {
        updateFunFactsFile($filename, $facts);
    }
}