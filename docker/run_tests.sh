#!/bin/sh
export BMLT=$(ipconfig getifaddr en0)
export BMLT_PORT=3001
docker compose down
docker compose up --detach
