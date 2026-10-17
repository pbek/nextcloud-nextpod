#!/bin/sh
# Creates the signed NextPod archive inside a Nextcloud container.
set -eu

APP_NAME=nextpod
APP_SOURCE=/var/www/html/custom_apps/${APP_NAME}
CERT_DIR=/var/www/.nextcloud/certificates
BUILD_DIR=${APP_SOURCE}/build

for file in "${CERT_DIR}/${APP_NAME}.key" "${CERT_DIR}/${APP_NAME}.crt"; do
  test -s "${file}" || {
    echo "Missing signing certificate/key; see docs/deployment.md" >&2
    exit 1
  }
done
test -s "${APP_SOURCE}/js/nextpod-main.js" || {
  echo "Run npm ci && npm run build first" >&2
  exit 1
}
VERSION=$(sed -n 's:.*<version>\(.*\)</version>.*:\1:p' "${APP_SOURCE}/appinfo/info.xml" | head -n 1)
WORK_DIR=$(mktemp -d)
APP_DEST=${WORK_DIR}/${APP_NAME}
ARCHIVE=${BUILD_DIR}/${APP_NAME}-${VERSION}.tar.gz
trap 'rm -rf "${WORK_DIR}"' EXIT
mkdir "${APP_DEST}"
cd "${APP_SOURCE}"
while IFS= read -r file; do
  case "${file}" in
  '' | '#'*) continue ;;
  esac
  # Only literal top-level paths, no options, globs, traversal or symlinks.
  case "${file}" in
  *[!a-zA-Z0-9_.-]* | .* | -*)
    echo "Invalid release input: ${file}" >&2
    exit 1
    ;;
  esac
  test -e "${file}" || {
    echo "Missing release input: ${file}" >&2
    exit 1
  }
  rsync -a --exclude '.*' --exclude 'signature.json' "${file}" "${APP_DEST}/"
done <release-files.txt
if [ -n "$(find "${APP_DEST}" -type l)" ]; then
  echo "Symlinks are not allowed in releases" >&2
  exit 1
fi
find "${APP_DEST}" -type d -empty -delete
mkdir "${WORK_DIR}/certificates"
cp "${CERT_DIR}/${APP_NAME}.key" "${CERT_DIR}/${APP_NAME}.crt" "${WORK_DIR}/certificates/"
chmod 600 "${WORK_DIR}/certificates/${APP_NAME}.key"
chown -R www-data:www-data "${WORK_DIR}"
su -s /bin/sh www-data -c "php /var/www/html/occ integrity:sign-app \
  --privateKey=${WORK_DIR}/certificates/${APP_NAME}.key \
  --certificate=${WORK_DIR}/certificates/${APP_NAME}.crt --path=${APP_DEST}"
printf '\nFiles in the signed archive:\n'
find "${APP_DEST}" -type f | sort
mkdir -p "${BUILD_DIR}"
tar czf "${ARCHIVE}" --owner=0 --group=0 --numeric-owner -C "${WORK_DIR}" "${APP_NAME}"
openssl dgst -sha512 -sign "${WORK_DIR}/certificates/${APP_NAME}.key" -out "${WORK_DIR}/archive-signature" "${ARCHIVE}"
openssl base64 -A -in "${WORK_DIR}/archive-signature" >"${ARCHIVE}.sig"
(cd "${BUILD_DIR}" && sha256sum "$(basename "${ARCHIVE}")" >"${ARCHIVE}.sha256")
chown -R "$(stat -c '%u:%g' "${APP_SOURCE}")" "${BUILD_DIR}"
printf '\nArchive: %s\nApp Store signature:\n' "${ARCHIVE}"
cat "${ARCHIVE}.sig"
echo
