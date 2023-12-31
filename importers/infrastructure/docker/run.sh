#!/bin/sh

set -e

APP_VERSION=develop
VERSION=alpha

docker build --pull --no-cache --build-arg APP_VERSION=${APP_VERSION} --tag=danskernesdigitalebibliotek/cover-service-importers:${VERSION} --file="cover-service-importers/Dockerfile" cover-service-importers
docker build --no-cache --build-arg VERSION=${VERSION} --tag=danskernesdigitalebibliotek/cover-service-jobs:${VERSION} --file="cover-service-jobs/Dockerfile" cover-service-jobs

docker push danskernesdigitalebibliotek/cover-service-importers:${VERSION}
docker push danskernesdigitalebibliotek/cover-service-jobs:${VERSION}
