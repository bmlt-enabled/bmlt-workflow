#!/bin/sh

docker compose down
docker compose -f ../../bmlt2x/docker/docker-compose.yml down
docker compose -f ../../bmlt2x/docker/docker-compose.yml up --detach
docker compose -f ../../bmlt3x/docker/docker-compose.yml down
docker compose -f ../../bmlt3x/docker/docker-compose.yml up --detach
docker compose up --detach
