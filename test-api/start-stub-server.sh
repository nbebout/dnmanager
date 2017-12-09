#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

docker network create dnmanager 2>/dev/null || true
docker run -d -p 8081:80 --name dns-test-api -v "$DIR":/var/www/html --network dnmanager php:7.0-apache
