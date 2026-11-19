
install-playwright:
	npm ci
	npm run playwright:install -- chromium

screenshots:
	CHROMIUM_PATH="$${CHROMIUM_PATH:-$$(command -v chromium || true)}" npx playwright test tests/screenshots.spec.ts --project=chromium
