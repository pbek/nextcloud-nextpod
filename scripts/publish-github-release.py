#!/usr/bin/env python3
# SPDX-FileCopyrightText: 2026 Patrizio Bekerle <patrizio@bekerle.com>
# SPDX-License-Identifier: AGPL-3.0-or-later

"""Publish signed assets without moving tags or overwriting existing assets."""

import argparse
import hashlib
import json
import os
import re
import subprocess
import sys
import tempfile
from pathlib import Path


def gh(*args: str, allow_missing: bool = False):
    result = subprocess.run(["gh", *args], capture_output=True, text=True, check=False)
    if result.returncode:
        if allow_missing and "HTTP 404" in result.stderr:
            return None
        raise ValueError(f"GitHub CLI failed: gh {' '.join(args[:2])}")
    return result.stdout


def publish(root: Path, commit: str, repository: str) -> None:
    if not re.fullmatch(r"[0-9a-f]{40}", commit):
        raise ValueError("Release commit must be a full commit SHA")
    if not re.fullmatch(r"[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+", repository):
        raise ValueError("GH_REPO must be owner/repository")
    notes = root / "release-notes.md"
    version = subprocess.check_output(
        [
            sys.executable,
            str(root / "scripts/check-release.py"),
            "--root",
            str(root),
            "--notes-output",
            str(notes),
        ],
        text=True,
    ).strip()
    archive = root / "build" / f"nextpod-{version}.tar.gz"
    checksum = archive.with_name(archive.name + ".sha256")
    expected = f"{hashlib.sha256(archive.read_bytes()).hexdigest()}  {archive.name}\n"
    if checksum.read_text() != expected:
        raise ValueError("Signed archive checksum does not match")
    assets = [archive, archive.with_name(archive.name + ".sig"), checksum]
    for asset in assets:
        if not asset.is_file() or not asset.stat().st_size:
            raise ValueError(f"Missing or empty signed asset: {asset.name}")
    tag = f"v{version}"
    release_data = gh(
        "api", f"repos/{repository}/releases/tags/{tag}", allow_missing=True
    )
    # The commits endpoint returns 422, not 404, for a missing tag. Check
    # the exact ref first, then resolve existing tags (including annotated tags).
    tag_ref = gh("api", f"repos/{repository}/git/ref/tags/{tag}", allow_missing=True)
    tag_data = (
        gh("api", f"repos/{repository}/commits/{tag}")
        if tag_ref is not None
        else None
    )
    if tag_data is not None and json.loads(tag_data)["sha"] != commit:
        raise ValueError(f"Existing {tag} tag points to a different commit")
    if release_data is None:
        gh(
            "release",
            "create",
            tag,
            "--repo",
            repository,
            "--target",
            commit,
            "--title",
            f"Release {tag}",
            "--notes-file",
            str(notes),
            "--draft",
        )
        release = {"draft": True, "assets": []}
    else:
        release = json.loads(release_data)
        if tag_data is None and release["target_commitish"] != commit:
            raise ValueError("Existing draft targets a different commit")
        if not release["draft"] and tag_data is None:
            raise ValueError("Published release has no resolvable tag")
    existing = {asset["name"] for asset in release["assets"]}
    for asset in assets:
        if asset.name in existing:
            with tempfile.TemporaryDirectory() as directory:
                gh(
                    "release",
                    "download",
                    tag,
                    "--repo",
                    repository,
                    "--pattern",
                    asset.name,
                    "--dir",
                    directory,
                )
                if (Path(directory) / asset.name).read_bytes() != asset.read_bytes():
                    raise ValueError(
                        f"Refusing to overwrite different existing asset: {asset.name}"
                    )
        elif not release["draft"]:
            raise ValueError(
                f"Published release is missing {asset.name}; refusing to modify it"
            )
        else:
            gh("release", "upload", tag, str(asset), "--repo", repository)
    if release["draft"]:
        gh(
            "release",
            "edit",
            tag,
            "--repo",
            repository,
            "--title",
            f"Release {tag}",
            "--notes-file",
            str(notes),
            "--draft=false",
            "--latest",
        )
    print(f"GitHub release {tag} published with verified signed assets")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--commit", required=True)
    parser.add_argument(
        "--root", type=Path, default=Path(__file__).resolve().parent.parent
    )
    args = parser.parse_args()
    try:
        publish(args.root.resolve(), args.commit, os.environ.get("GH_REPO", ""))
    except (OSError, ValueError, subprocess.CalledProcessError) as error:
        print(f"GitHub publication failed: {error}", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
