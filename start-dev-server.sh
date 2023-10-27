#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

docker build -f Dockerfile.dev -t php-with-intl .

docker run -d -p 8080:80 --name dnmanager -v "$DIR":/var/www/html php-with-intl

echo "Open a browser and go to: http://localhost:8080"
