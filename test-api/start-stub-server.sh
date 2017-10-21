#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

docker run -d -p 8081:80 --name enom-test-api -v "$DIR":/var/www/html --network dnmanager php:7.0-apache