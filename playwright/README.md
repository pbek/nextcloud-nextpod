# Playwright

## Regenerate the README and App Store screenshots

Start the [Docker development server](../docker/README.md) on `http://localhost:8081`
with NextPod and Nextcloud Notes enabled. Use Node.js 20.9 or later. Build the
NextPod frontend from the repository root with `npm ci` and `npm run build` if it
isn't built yet.

```bash
# Only needed if Chromium is not already on PATH
npm --prefix playwright ci
npm --prefix playwright run playwright:install -- chromium

just screenshots
```

`just screenshots '--headed'` shows the browser. You can set `CHROMIUM_PATH`
to use a specific Chromium binary. For a different **development** server or
account, set `NEXTCLOUD_URL`, `NEXTCLOUD_USER`, and `NEXTCLOUD_PASSWORD`.
Use a test account with an English, light-theme Nextcloud profile.

Playwright supplies fictional subscriptions, episode titles, artwork, listening
progress, and silent demo audio in the browser. It does not import SQL, fetch
external podcast feeds, create Notes, or sync playback progress. The Notes,
download, website, and RSS actions are shown in the episode menu.

Review the generated lossless WebP files in [`img/screenshots/`](../img/screenshots/)
before committing them. The App Store loads the files referenced by
`appinfo/info.xml` from `main`. Keep the legacy PNGs there so screenshot URLs
from older published releases keep working.
