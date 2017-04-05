<?php
declare(strict_types = 1);

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

$client = new \GuzzleHttp\Client();
$token = getenv('GITTER_AUTH_TOKEN');
$funFactsFile = getenv('FUN_FACTS_FILE');

echo "Fetching active users of gitter room prooph/improoph\n";

$fetchChunk = function (int $skip = 0, int $limit = 100) use ($client, $token) {
    $request = new \GuzzleHttp\Psr7\Request(
        'GET',
        'https://api.gitter.im/v1/rooms/55c4e9050fc9f982beac9c4f/users?skip='.$skip.'&limit='.$limit,
        ['Authorization' => 'Bearer ' . $token]
    );

    $res = $client->send($request);

    if($res->getStatusCode() !== 200) {
        echo "Failed to fetch gitter people: [{$res->getStatusCode()}] {$res->getBody()}\n";
        exit(1);
    }

    return json_decode((string)$res->getBody(), true);
};

$people = [];
$skip = 0;
$limit = 100;

do {
    $chunkOfPeople = $fetchChunk($skip, $limit);

    $chunkCount = count($chunkOfPeople);

    $nextChunkAvailable = $chunkCount === $limit;

    foreach ($chunkOfPeople as $dev) {
        $people[] = [
            'username' => $dev['username'],
            'avatarUrlSmall' => $dev['avatarUrlSmall']
        ];
    }

    $skip += $limit;

} while ($nextChunkAvailable);

$validPeople = [];
$promiseCollection = [];

echo "Fetched " . count($people) . " people from Gitter. Now checking if avatars can be loaded:\n";

foreach ($people as $person) {
    //Note: async requests did not work well here, maybe because too much requests in parallel ...
    $response = $client->head($person['avatarUrlSmall'], [
        'http_errors' => false
    ]);

    if($response->getStatusCode() === 200) {
        $validPeople[] = $person;
        echo "\033[32m" . $person['username'] . " is in\033[0m\n";
    } else {
        echo "\033[31m" . $person['username'] . " is out\e[0m\n";
    }

    //short break before next request
    usleep(100);
}

\Prooph\FunFacts\StatsCollector\Fn\upsertGitterPeopleInStaticFile($validPeople, $funFactsFile);

$numPeople = count($validPeople);

echo "Successfully fetched {$numPeople} avatars and written to $funFactsFile\n";

echo "Fetching packagist stats for prooph packages\n";

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

\Prooph\FunFacts\StatsCollector\Fn\upsertPackageStatsInStaticFile($sumGithubStars, $sumDownloads,$funFactsFile);

echo "Successfully fetched stats: GitHub Stars {$sumGithubStars}, Downloads {$sumDownloads} and written to file {$funFactsFile}\n";

exit(0);
