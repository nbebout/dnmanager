#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

docker build -f Dockerfile.dev -t php-with-intl .

# Share a network with the stub server (test-api/start-stub-server.sh) so the
# app can reach it at http://dns-test-api.
docker network create dnmanager 2>/dev/null || true
docker run -d -p 8080:80 --name dnmanager -v "$DIR":/var/www/html --network dnmanager php-with-intl

echo "Open a browser and go to: http://localhost:8080"
