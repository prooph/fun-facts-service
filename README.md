# prooph fun facts

prooph/micro service to fetch aggregated fun facts about prooph from packagist and gitter.

## Installation / Usage

A php script fetches data from packagist and gitter api. The script is only accessible from the command line.
It uses guzzle which needs to be installed with composer:

`docker run -v $(pwd)/service/stats-collector:/app --rm prooph/composer:7.1 install`

The Gitter API requires authentication. Sign in at `https://developer.gitter.im` and find your personal access token.
Copy and rename the `app.env.dist` file to `app.env` and set your personal access token as `GITTER_AUTH_TOKEN`.

That's it. You can run `docker-compose up -d` now and fetch the fun facts:

`docker-compose run stats-collector php bin/gather-stats.php`

The result of the command is written to a static file: `service/fun-facts/data/fun_facts.json`.
This file is then served by Nginx. You can access it using the url: `http://localhost/api/v1/fun_facts.json`
