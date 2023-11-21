#!/bin/sh

set -e

APP_VERSION=develop
VERSION=alpha

docker pull nginxinc/nginx-unprivileged:alpine

docker build --pull --no-cache --build-arg APP_VERSION=${APP_VERSION} --tag=danskernesdigitalebibliotek/cover-service-upload:${VERSION} --file="cover-service-upload/Dockerfile" cover-service-upload
docker build --no-cache --build-arg VERSION=${VERSION} --tag=danskernesdigitalebibliotek/cover-service-upload-nginx:${VERSION} --file="nginx/Dockerfile" nginx

docker push danskernesdigitalebibliotek/cover-service-upload:${VERSION}
docker push danskernesdigitalebibliotek/cover-service-upload-nginx:${VERSION}
