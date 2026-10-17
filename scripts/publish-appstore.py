#!/usr/bin/env python3
# SPDX-FileCopyrightText: 2026 Patrizio Bekerle <patrizio@bekerle.com>
# SPDX-License-Identifier: AGPL-3.0-or-later

"""Publish an already signed, publicly downloadable archive to the App Store."""

import argparse
import base64
import hashlib
import json
import os
import re
import sys
import urllib.error
import urllib.request
from pathlib import Path

ENDPOINT = "https://apps.nextcloud.com/api/v1/apps/releases"
MAX_SIZE = 20 * 1024 * 1024


def publish(download_url: str, signature_path: Path, archive: Path, token: str) -> int:
    if not token.strip() or "\n" in token or "\r" in token:
        raise ValueError("APPSTORE_TOKEN is missing or invalid")
    match = re.fullmatch(
        r"https://github\.com/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+/releases/download/"
        r"v((?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*))/nextpod-\1\.tar\.gz",
        download_url,
    )
    if not match or archive.name != f"nextpod-{match[1]}.tar.gz":
        raise ValueError(
            "Download URL and archive must identify the same stable NextPod release"
        )
    size = archive.stat().st_size
    if not 0 < size <= MAX_SIZE:
        raise ValueError("App Store archive must be nonempty and at most 20 MiB")
    signature = signature_path.read_text().strip()
    if not base64.b64decode(signature, validate=True):
        raise ValueError("Archive signature is empty")

    # No credentials are sent to GitHub. Ensure the store will fetch exactly the
    # signed bytes, not a missing/private asset or a replaced archive.
    with urllib.request.urlopen(download_url, timeout=120) as response:
        if not response.geturl().startswith("https://"):
            raise ValueError("Archive download redirected to an insecure URL")
        public_archive = response.read(MAX_SIZE + 1)
    if (
        hashlib.sha256(public_archive).digest()
        != hashlib.sha256(archive.read_bytes()).digest()
    ):
        raise ValueError("Public GitHub archive differs from the local signed archive")
    request = urllib.request.Request(
        ENDPOINT,
        data=json.dumps(
            {"download": download_url, "signature": signature, "nightly": False}
        ).encode(),
        headers={"Authorization": f"Token {token}", "Content-Type": "application/json"},
        method="POST",
    )
    with urllib.request.urlopen(request, timeout=180) as response:
        if response.status not in (200, 201):
            raise ValueError(f"Unexpected App Store HTTP status: {response.status}")
        return response.status


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--download-url", required=True)
    parser.add_argument("--signature", type=Path, required=True)
    parser.add_argument("--archive", type=Path, required=True)
    args = parser.parse_args()
    try:
        status = publish(
            args.download_url,
            args.signature,
            args.archive,
            os.environ.get("APPSTORE_TOKEN", ""),
        )
    except urllib.error.HTTPError as error:
        # Do not print requests/headers or server responses containing credentials.
        print(
            f"Publication failed: HTTP {error.code}. Check the public URL, registration, signature and token.",
            file=sys.stderr,
        )
        return 1
    except (OSError, ValueError, urllib.error.URLError) as error:
        print(f"Publication failed: {error}", file=sys.stderr)
        return 1
    print(f"App Store release {'created' if status == 201 else 'updated'} successfully")
    return 0


if __name__ == "__main__":
    sys.exit(main())
