# AGENTS.md

## Version Number Location

The version number is located in `appinfo/info.xml`. Keep `package.json` and both root version fields in `package-lock.json` in sync.

## Signing Script

The signing script is at `docker/nextcloud/sign-app.sh`. Only runtime paths listed in `release-files.txt` are packaged; add new runtime paths there. Release setup and operation are documented in `docs/deployment.md`.

## Tooling

If you need tooling to run a command use `nix-shell` to get it, since we use Nix for development environment management. But ask before you run it!
