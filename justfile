# Use `just <recipe>` to run a recipe
# https://just.systems/man/en/

# By default, run the `--list` command
default:
    @just --list

# Variables

transferDir := `if [ -d "$HOME/NextcloudPrivate/Transfer" ]; then echo "$HOME/NextcloudPrivate/Transfer"; else echo "$HOME/Nextcloud/Transfer"; fi`
version := `xmllint --xpath "string(/info/version)" appinfo/info.xml`

# Open a terminal with the nextcloud-nextpod session
[group('dev')]
term:
    zellij --layout term.kdl attach nextcloud-nextpod -c

# Kill the nextcloud-nextpod session
[group('dev')]
term-kill:
    zellij delete-session nextcloud-nextpod -f

# Apply the patch to the nextcloud-nextpod repository
[group('patch')]
git-apply-patch:
    git apply {{ transferDir }}/nextcloud-nextpod.patch

# Create a patch from the staged changes in the nextcloud-nextpod repository
[group('patch')]
@git-create-patch:
    echo "transferDir: {{ transferDir }}"
    git diff --no-ext-diff --staged --binary > {{ transferDir }}/nextcloud-nextpod.patch
    ls -l1t {{ transferDir }}/ | head -2

# Create a tag for the release
[group('dev')]
create-tag:
    git tag -a v{{ version }} -m "Tagging the {{ version }} release." && git push origin v{{ version }}

# Create a screenshot of the nextcloud-nextpod app
[group('doc')]
screenshots:
    cd playwright && npx playwright test tests/screenshots.spec.ts --project=chromium --headed

# Build the nextcloud-nextpod app
[group('dev')]
build-release:
    rm -rf js/*
    composer install --no-dev
    npm install
    npm run build

# Format all justfiles
[group('linter')]
just-format:
    #!/usr/bin/env bash
    # Find all files named "justfile" recursively and run just --fmt --unstable on them
    find . -type f -name "justfile" -print0 | while IFS= read -r -d '' file; do
        echo "Formatting $file"
        just --fmt --unstable -f "$file"
    done
