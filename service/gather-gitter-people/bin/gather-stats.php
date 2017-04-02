<?php
declare(strict_types = 1);

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

$client = new \GuzzleHttp\Client();
$token = getenv('GITTER_AUTH_TOKEN');

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

\ProophFunFacts\Shared\Fn\upsertGitterPeopleInStaticFile($people, getenv('FUN_FACTS_FILE'));
exit(0);