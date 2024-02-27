#!/bin/bash

# Deploy a docker version of the application to the Azure Container Registry. Allows for
# remote debugging when needed (currently still using phar as deployment method..)

docker build \
  --target prod \
  --platform linux/amd64 \
  -f .docker/Dockerfile \
  -t "ciukdevopscreg.azurecr.io/git-ops-image" \
  .
docker push "ciukdevopscreg.azurecr.io/git-ops-image"


# EXAMPLE RUN CODE FOR TEAMCITY SERVER:
# docker run \
#    --rm \
#    -ti \
#    -v /opt/buildagent/system/git:/opt/buildagent/system/git \
#    -v /var/run/docker.sock:/var/run/docker.sock \
#    -v ${PWD}:/app/project \
#    ciukdevopscreg.azurecr.io/git-ops-image \
#        bash
#
#
# -c "cd /app/project && php ../bin/main"
