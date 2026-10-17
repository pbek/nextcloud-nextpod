# Signing and releasing NextPod

The [Sign and publish release workflow](../.github/workflows/create_release.yml)
builds NextPod, signs it with Nextcloud's code-signing tools, publishes a GitHub
release, and submits that exact archive to the Nextcloud App Store.
It runs **only when a commit is pushed to `release`**. Tags, pull requests,
development-branch pushes and manual workflow dispatch do not trigger it.

## One-time setup

### Nextcloud certificate and App Store account

If you already have the `nextpod` signing key and certificate used for previous
releases, reuse them. Do not generate a replacement unnecessarily. Certificates
for another app (including `qownnotes`) cannot be reused.

For a new app registration, follow Nextcloud's
[certificate instructions](https://nextcloudappstore.readthedocs.io/en/latest/developer.html#obtaining-a-certificate).
Generate the private key and certificate signing request with app ID `nextpod`:

```bash
mkdir -p ~/.nextcloud/certificates
cd ~/.nextcloud/certificates
openssl req -nodes -newkey rsa:4096 -keyout nextpod.key -out nextpod.csr -subj "/CN=nextpod"
chmod 600 nextpod.key
```

Submit the CSR as described by Nextcloud and save the signed certificate as
`nextpod.crt`. **Never share the private key or commit it to Git.** Keep an offline
backup; losing it affects your ability to publish updates.

Register `nextpod` in the [App Store](https://apps.nextcloud.com/developer/apps/new)
if it is not already registered. Registration requires the certificate and a
signature over the app ID, generated without a trailing newline:

```bash
printf nextpod | openssl dgst -sha512 -sign ~/.nextcloud/certificates/nextpod.key | openssl base64 -A
```

Obtain the owning account's API token from
[App Store account settings](https://apps.nextcloud.com/account/token).

### GitHub environments and permissions

Under the repository's **Settings → Environments**, create:

| Environment          | Secret            | Value                                  |
| -------------------- | ----------------- | -------------------------------------- |
| `release-signing`    | `APP_PRIVATE_KEY` | Complete PEM contents of `nextpod.key` |
| `release-signing`    | `APP_PUBLIC_CRT`  | Complete PEM contents of `nextpod.crt` |
| `release-publishing` | `APPSTORE_TOKEN`  | App Store account API token            |

Paste the PEM headers, footers and all intervening lines, not a file path or
base64-encoded file. The certificate is public; storing it as a secret is merely
convenient. The private key and token are sensitive. The token may affect other
apps owned by the same account. Never upload either as an artifact or print them.

Restrict **both environments to the `release` branch** using deployment branch
rules. Configure required reviewers where your GitHub plan supports them.
Protect `release` against unreviewed changes and force pushes, especially changes
to workflows and scripts that receive secrets. Review the commit before approving
its deployments; an environment does not make untrusted code safe.

Enable GitHub Actions and allow the actions used by the workflow. Its default
`GITHUB_TOKEN` permission is `contents: read`; only the GitHub publishing job
requests `contents: write`. No personal GitHub token is required. The repository
and release assets must be publicly downloadable for the App Store.

Builds and formatting run in separate jobs without signing credentials. Signing
and publishing use GitHub-hosted ephemeral runners. The signing image and actions
in the release workflow are pinned; update them deliberately. The reused formatting
workflow has its own dependencies, which also need review.

## Preparing a release

1. Set the same stable `major.minor.patch` version (for example `0.7.13`) in:
   - `appinfo/info.xml`, inside `<version>`;
   - `package.json`, top-level `version`;
   - `package-lock.json`, top-level `version` and `packages[""].version`.
     Do not change dependency versions merely to match the app version.
2. Add a nonempty `## <version>` section as the newest section in `CHANGELOG.md`.
   That section's text becomes the GitHub release description; release notes are
   not generated from commit messages. The App Store reads metadata from the app.
3. Run `just release-check` (or `python3 scripts/check-release.py`) and ensure
   ordinary CI, including PHPUnit, passes for the reviewed development commit.
   The preflight checks versions, changelog, runtime inputs, symlinks and screenshot
   paths without certificates or a frontend build.
4. Check [`release-files.txt`](../release-files.txt) if runtime files were added.
   Only those literal top-level paths are packaged, including translations in
   `l10n/` and the built frontend in `js/`. Development dependencies, databases,
   Docker configuration and secrets are not packaged. Existing `signature.json`
   files and hidden files are excluded; Nextcloud creates a fresh signature.
5. Commit, review and push the intended release commit to `release`. For example,
   from that reviewed commit:

   ```bash
   git push origin HEAD:release
   ```

   Follow your branch protection/PR policy if direct pushes are disallowed.
   **Do not run `just create-tag` or create a release manually for this path.**
   The workflow creates the tag itself. Do not queue another release while one is
   awaiting approval: active runs are not cancelled, but GitHub concurrency may
   replace an older pending run with a newer one.

## What the workflow does

1. **Validate:** run the release regression tests and metadata preflight. A version
   mismatch or invalid changelog fails before building or accessing secrets.
2. **Build and format:** run `npm ci`, a production build with Node 20, a built-input
   check, and the reusable formatting workflow. NextPod has no frontend unit-test
   command. The standalone PHPUnit matrix is separate, not a release-job gate;
   ensure it is green before pushing the release commit.
3. **Sign:** after approval of `release-signing` (if reviewers are configured),
   download the built frontend without rebuilding it. `scripts/ci-sign-app.sh`
   starts an isolated Nextcloud 35.0.1 container, signs the manifest-selected app
   with `docker/nextcloud/sign-app.sh`, and creates:
   - `build/nextpod-<version>.tar.gz`, containing a top-level `nextpod/` directory;
   - `build/nextpod-<version>.tar.gz.sig`, the base64 SHA-512 archive signature;
   - `build/nextpod-<version>.tar.gz.sha256`, the archive checksum.

   Nextcloud's `appinfo/signature.json` protects the files inside the app; the
   separate `.sig` signs the entire compressed archive for the App Store. Both
   are needed. The job verifies the archive signature using the certificate,
   extracts the actual archive, requires its `signature.json`, runs Nextcloud's
   integrity check, enables that extracted app and checks its installed version.
   This is an installation/integrity smoke test, not a podcast-sync acceptance
   test. No GPodderSync server or development volumes are used. The container and
   temporary key files are removed on exit. Archives over 20 MiB are rejected.

4. **Publish GitHub:** approve `release-publishing` if prompted. The publisher checks
   the archive checksum, creates or resumes a draft tagged `v<version>` at the exact
   workflow commit, attaches all three files, and publishes it as the latest stable
   release with the matching changelog text. Existing tags are never moved; assets
   with different bytes are never overwritten.
5. **Publish App Store:** a separate job in `release-publishing` (which may require
   another approval) downloads the signed artifact, verifies that the public GitHub
   download is byte-identical, and submits its URL and existing `.sig` to the
   [App Store API](https://nextcloudappstore.readthedocs.io/en/latest/api/restapi.html#api-create-release)
   with `nightly: false`. It receives the API token, **not the private key**.

Afterwards, check the [GitHub release](https://github.com/pbek/nextcloud-nextpod/releases)
and [NextPod App Store page](https://apps.nextcloud.com/apps/nextpod), then test the
update on a Nextcloud instance with GPodderSync. Actions artifacts are retained for
14 days; published GitHub release assets remain available beyond that.

## Failures and retries

- **Validation/build/formatting:** fix the problem and push a reviewed commit. No
  signing or publication occurs before these jobs pass.
- **Signing:** check the PEM secrets, matching `nextpod` certificate and container
  initialization. Correct configuration and use **Re-run failed jobs**.
- **GitHub publication:** retry failed jobs while the signed artifact is retained.
  A partial draft resumes only if its commit and existing assets agree. A tag at
  another commit or different existing asset bytes cause a failure, not replacement.
- **App Store submission:** GitHub remains published. Fix token, app registration
  or public-download issues, then choose **Re-run failed jobs**, not all jobs.
  The retry reuses the exact signed artifact without rebuilding or signing. The API
  may return HTTP 200 (updated) instead of HTTP 201 (created).

Do not rerun all jobs once assets have been uploaded: rebuilding/signing can
produce different bytes for the same commit. Never replace published assets to
repair a release; ship a new version instead.

If Actions artifacts have expired, download the archive and `.sig` from the
published GitHub release and submit them locally, using the actual version:

```bash
# Set APPSTORE_TOKEN securely in the environment, not in shell history.
python3 scripts/publish-appstore.py \
  --download-url https://github.com/pbek/nextcloud-nextpod/releases/download/v0.7.13/nextpod-0.7.13.tar.gz \
  --archive build/nextpod-0.7.13.tar.gz \
  --signature build/nextpod-0.7.13.tar.gz.sig
```

## Local signing fallback

Do not race this path with the automated workflow. If releasing manually, do not
push to `release` unless you have intentionally disabled the automated workflow.

1. Copy `nextpod.key` and `nextpod.crt` into `docker/nextcloud/certificates/`
   (Git-ignored). Keep the private key readable only by its owner.
2. Update versions and changelog as above, then run `just release-check` and ensure
   CI passes.
3. Run `just build-release` at the repository root. Prepare the Docker development
   environment as described in [docker/README.md](../docker/README.md), then run
   `cd docker && just sign-app`.
4. Review the archive contents and verify its signature and Nextcloud integrity
   before publishing. Signing is now noninteractive and uses the same manifest as
   CI. Outputs are `build/nextpod-<version>.tar.gz`, `.sig` and `.sha256`, **not the
   old `nextpod-nc.tar.gz`**. The source tree's `appinfo/signature.json` is not changed.
5. Create a GitHub release tagged `v<version>` at the intended commit, use the
   matching changelog text, and attach the three files. Submit the public archive
   URL and `.sig` contents through the
   [App Store upload form](https://apps.nextcloud.com/developer/apps/releases/new)
   or use `scripts/publish-appstore.py` as above.

For isolated local signing with the same verification as CI, export
`APP_PRIVATE_KEY` and `APP_PUBLIC_CRT` securely and run `sh scripts/ci-sign-app.sh`
after building. This requires Python 3, Docker and OpenSSL on the host. It creates
its own disposable container and does not use the development Docker volumes.
