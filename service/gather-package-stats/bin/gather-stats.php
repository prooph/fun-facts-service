<?php
declare(strict_types = 1);

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

$client = new \GuzzleHttp\Client();

$fetchPackageData = function (string $package) use ($client): \GuzzleHttp\Promise\PromiseInterface {
    $promise = $client->requestAsync('GET', 'https://packagist.org/packages/'.$package.'.json');

    return $promise->then(function (\GuzzleHttp\Psr7\Response $response) use ($package) {
        if($response->getStatusCode() !== 200) {
            throw new \RuntimeException("Failed to fetch data for package $package: [{$response->getStatusCode()}] " . $response->getBody());
        }

        $data = json_decode((string)$response->getBody(), true);

        return [
            'github_stars' => $data['package']['github_stars'] ?? 0,
            'downloads' => $data['package']['downloads']['total'] ?? 0
        ];
    });
};

$res = $client->request('GET', 'https://packagist.org/packages/list.json?vendor=prooph');

if($res->getStatusCode() !== 200) {
    echo "Failed to fetch prooph packages: [{$res->getStatusCode()}] {$res->getBody()}\n";
    exit(1);
}

$data = json_decode((string)$res->getBody(), true);

$promiseCollection = [];

foreach ($data['packageNames'] ?? [] as $package) {
    /** @var \GuzzleHttp\Promise\PromiseInterface[] $promiseCollection */
    $promiseCollection[] = $fetchPackageData($package);
}

$sumGithubStars = 0;
$sumDownloads = 0;

foreach ($promiseCollection as $promise) {
    $data = $promise->wait();
    $sumGithubStars += $data['github_stars'];
    $sumDownloads += $data['downloads'];
}

\ProophFunFacts\Shared\Fn\upsertPackageStatsInStaticFile($sumGithubStars, $sumDownloads, getenv('FUN_FACTS_FILE'));
exit(0);