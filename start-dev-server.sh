#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

docker network create dnmanager 2>/dev/null || true
docker run -d -p 8080:80 --name dnmanager -v "$DIR":/var/www/html --network dnmanager php:7.0-apache

echo "Open a browser and go to: http://localhost:8080"
