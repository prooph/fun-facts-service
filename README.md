# prooph fun facts

prooph/micro service to fetch aggregated fun facts about prooph from packagist and gitter.

## Installation / Usage

Two php scripts fetch data from packagist and gitter api. The scripts are only accessible from the command line.
Both scripts use guzzle and a shared library installed via composer:

`docker run -v ./service/gather-package-stats:/app -v ./shared:/shared --rm prooph/composer:7.1 install`
`docker run -v ./service/gather-gitter-people:/app -v ./shared:/shared --rm prooph/composer:7.1 install`

The Gitter API requires authentication. Sign in at `https://developer.gitter.im` and find your personal access token.
Copy and rename the `app.env.dist` file to `app.env` and set your personal access token as `GITTER_AUTH_TOKEN`.

That's it. You can run `docker-compose up -d` now and fetch the fun facts:

`docker-compose run gather-gitter-people php bin/gather-stats.php`
`docker-compose run gather-package-stats php bin/gather-stats.php`

The result of both commands is merged into a static file: `service/fun-facts/data/fun_facts.json`.
This file is then served by Nginx. You can access it using the url: `http://localhost/api/v1/fun_facts.json`

