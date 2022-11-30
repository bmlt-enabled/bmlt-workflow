#!/bin/sh

docker compose down
docker compose -f ../../bmlt2x/docker/docker-compose.yml up &
docker compose -f ../../bmlt3x/docker/docker-compose.yml up &
docker compose up
