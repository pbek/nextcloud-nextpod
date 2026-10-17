#!/bin/sh
# SPDX-FileCopyrightText: 2026 Patrizio Bekerle <patrizio@bekerle.com>
# SPDX-License-Identifier: AGPL-3.0-or-later

# Use an isolated Nextcloud, never the developer's containers or volumes.
set -eu
ROOT=$(CDPATH='' cd -- "$(dirname -- "$0")/.." && pwd)
cd "${ROOT}"
VERSION=$(python3 scripts/check-release.py --built)
: "${APP_PRIVATE_KEY:?APP_PRIVATE_KEY is required}"
: "${APP_PUBLIC_CRT:?APP_PUBLIC_CRT is required}"
WORK_DIR=$(mktemp -d)
CONTAINER="nextpod-release-$(basename "${WORK_DIR}" | tr '[:upper:]' '[:lower:]')"
cleanup() {
  docker rm -fv "${CONTAINER}" >/dev/null 2>&1 || true
  rm -rf "${WORK_DIR}"
}
trap cleanup EXIT
trap 'exit 1' HUP INT TERM
umask 077
printf '%s\n' "${APP_PRIVATE_KEY}" >"${WORK_DIR}/nextpod.key"
printf '%s\n' "${APP_PUBLIC_CRT}" >"${WORK_DIR}/nextpod.crt"
unset APP_PRIVATE_KEY APP_PUBLIC_CRT

# Pin the signing runtime; update deliberately.
docker run -d --name "${CONTAINER}" \
  -e NEXTCLOUD_ADMIN_USER=admin -e NEXTCLOUD_ADMIN_PASSWORD=release-test-only \
  -e SQLITE_DATABASE=nextcloud -e NEXTCLOUD_TRUSTED_DOMAINS=localhost \
  nextcloud:35.0.1-apache@sha256:8f2f0e93aca5e9457019933936dea1f313052e72c696b4aecb31580a208b859f >/dev/null
ready=false
for _ in $(seq 1 90); do
  if docker exec -u www-data "${CONTAINER}" php occ status --output=json 2>/dev/null |
    python3 -c 'import json,sys; sys.exit(not json.load(sys.stdin).get("installed"))' 2>/dev/null; then
    ready=true
    break
  fi
  sleep 2
done
if [ "${ready}" != true ]; then
  echo "Nextcloud did not initialize within 180 seconds" >&2
  exit 1
fi
docker exec "${CONTAINER}" sh -c 'apt-get update -qq && apt-get install -y -qq --no-install-recommends rsync'
docker exec "${CONTAINER}" mkdir -p /var/www/html/custom_apps/nextpod/docker/nextcloud /var/www/.nextcloud/certificates
while IFS= read -r entry; do
  case "${entry}" in
  '' | '#'*) continue ;;
  esac
  docker cp "${entry}" "${CONTAINER}:/var/www/html/custom_apps/nextpod/"
done <release-files.txt
docker cp release-files.txt "${CONTAINER}:/var/www/html/custom_apps/nextpod/"
docker cp docker/nextcloud/sign-app.sh "${CONTAINER}:/var/www/html/custom_apps/nextpod/docker/nextcloud/"
docker cp "${WORK_DIR}/nextpod.key" "${CONTAINER}:/var/www/.nextcloud/certificates/"
docker cp "${WORK_DIR}/nextpod.crt" "${CONTAINER}:/var/www/.nextcloud/certificates/"
docker exec "${CONTAINER}" sh /var/www/html/custom_apps/nextpod/docker/nextcloud/sign-app.sh
mkdir -p build
ARCHIVE="nextpod-${VERSION}.tar.gz"
docker cp "${CONTAINER}:/var/www/html/custom_apps/nextpod/build/${ARCHIVE}" "build/${ARCHIVE}"
docker cp "${CONTAINER}:/var/www/html/custom_apps/nextpod/build/${ARCHIVE}.sig" "build/${ARCHIVE}.sig"
if [ "$(stat -c '%s' "build/${ARCHIVE}")" -gt 20971520 ]; then
  echo "Signed archive exceeds the App Store's 20 MiB limit" >&2
  exit 1
fi
openssl x509 -in "${WORK_DIR}/nextpod.crt" -pubkey -noout >"${WORK_DIR}/public.pem"
openssl base64 -d -A -in "build/${ARCHIVE}.sig" -out "${WORK_DIR}/signature"
openssl dgst -sha512 -verify "${WORK_DIR}/public.pem" -signature "${WORK_DIR}/signature" "build/${ARCHIVE}"

# Verify the actual archive, not the unsigned tree. Unsigned apps otherwise skip
# the integrity check, so explicitly require signature.json.
docker exec "${CONTAINER}" sh -c "mkdir /signed-release && tar xzf /var/www/html/custom_apps/nextpod/build/${ARCHIVE} -C /signed-release && test -s /signed-release/nextpod/appinfo/signature.json"
docker exec -u www-data "${CONTAINER}" php occ integrity:check-app nextpod --path=/signed-release/nextpod
docker exec "${CONTAINER}" sh -c 'mv /var/www/html/custom_apps/nextpod /unsigned-source && mv /signed-release/nextpod /var/www/html/custom_apps/nextpod && chown -R www-data:www-data /var/www/html/custom_apps/nextpod'
docker exec -u www-data "${CONTAINER}" php occ app:enable nextpod
docker exec -u www-data "${CONTAINER}" php occ config:app:get nextpod installed_version >"${WORK_DIR}/installed-version"
test "$(cat "${WORK_DIR}/installed-version")" = "${VERSION}"
(cd build && sha256sum "${ARCHIVE}" >"${ARCHIVE}.sha256")
echo "Signed archive integrity and app installation passed: build/${ARCHIVE}"
