# AGENTS.md

## Version Number Location

The version number is located in `appinfo/info.xml` (line 12: `<version>0.7.10</version>`).

## Signing Script

The signing script is at `docker/nextcloud/sign-app.sh`. New paths can be excluded there.

## Tooling

If you need tooling to run a command use `nix-shell` to get it, since we use Nix for development environment management. But ask before you run it!
