import { test, expect, type Page } from '@playwright/test';
import { resolve } from 'node:path';
import sharp from 'sharp';
import { featuredPosition, featuredTitle, useDemoData } from './demo-data';

const appPath = '/index.php/apps/nextpod';
const outputDir = resolve(__dirname, '../../img/screenshots');

async function capture(page: Page, name: string) {
  await expect(page.getByText('(Loading RSS data...)')).toHaveCount(0);
  const rows = page.locator('.actions .list-item, .podcasts .list-item');
  const covers = rows.locator('img[src*="/nextpod-screenshot-demo/"]');
  await expect(covers).toHaveCount(await rows.count());
  await covers.evaluateAll((images: HTMLImageElement[]) => Promise.all(images.map((image) => image.decode())));
  await page.evaluate(() => document.fonts.ready);
  await page.mouse.move(0, 899);
  const png = await page.screenshot({
    type: 'png',
    animations: 'disabled',
    caret: 'hide',
  });
  await sharp(png).webp({ lossless: true }).toFile(resolve(outputDir, `${name}.webp`));
}

async function openEpisodeMenu(page: Page) {
  await page.getByRole('listitem')
    .filter({ has: page.getByRole('link', { name: new RegExp(featuredTitle) }) })
    .getByRole('button', { name: /^Actions/ }).click();
}

test.beforeEach(async ({ page, baseURL }) => {
  if (!baseURL) {
    throw new Error('Set a Nextcloud baseURL in the Playwright configuration');
  }
  await useDemoData(page, baseURL);
  // Keep the first-login welcome overlay out of screenshots on fresh dev servers.
  await page.route(/\/apps\/firstrunwizard\/js\/firstrunwizard-activate\.(m?js)(\?|$)/,
    (route) => route.fulfill({ contentType: 'text/javascript', body: '' }));
  await page.goto(`${appPath}/actions`);
  await page.locator('#user').fill(process.env.NEXTCLOUD_USER || 'admin');
  await page.locator('#password').fill(process.env.NEXTCLOUD_PASSWORD || 'admin');
  await page.locator('#password').press('Enter');
  await expect(page.getByText(featuredTitle, { exact: true })).toBeVisible();
});

test('listening history and episode actions', async ({ page }) => {
  await expect(page.getByText('(35% of 24min 0s listened)')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Load more' })).toBeEnabled();
  await openEpisodeMenu(page);
  await expect(page.getByText('Open episode link', { exact: true })).toBeVisible();
  await expect(page.getByText('Play episode media', { exact: true })).toBeVisible();
  await expect(page.getByText('Create note of episode', { exact: true }),
    'Enable Nextcloud Notes on the screenshot server to showcase the Notes action').toBeVisible();
  await expect(page.getByText('Download episode media', { exact: true })).toBeVisible();
  await expect(page.getByText('Open RSS feed', { exact: true })).toBeVisible();
  await capture(page, 'episodes');
});

test('episode description', async ({ page }) => {
  await page.getByText(featuredTitle, { exact: true }).click();
  await expect(page.getByRole('heading', { name: featuredTitle })).toBeVisible();
  await expect(page.getByText('How street trees cool a neighborhood')).toBeVisible();
  await capture(page, 'episode-description');
});

test('download history', async ({ page }) => {
  await page.getByRole('main').getByRole('combobox').click();
  await page.getByRole('option', { name: 'Download', exact: true }).click();
  await expect(page.getByText('Why Postcards Still Matter', { exact: true })).toBeVisible();
  await expect(page.getByText(featuredTitle, { exact: true })).toHaveCount(0);
  await capture(page, 'downloads');
});

test('browser player and progress sync option', async ({ page }) => {
  await openEpisodeMenu(page);
  await page.getByText('Play episode media', { exact: true }).click();
  // Dismiss the episode menu behind the modal without closing the player.
  await page.getByRole('heading', { name: `Playing "${featuredTitle}"` }).click();
  await expect(page.getByText('Play episode media', { exact: true })).toBeHidden();
  await expect(page.getByText('Store progress while playing')).toBeVisible();
  // Let the real player resume from the saved position, then freeze it for capture.
  const player = page.locator('audio');
  await expect.poll(() => player.evaluate((audio: HTMLAudioElement) => audio.currentTime))
    .toBeGreaterThanOrEqual(featuredPosition);
  await player.evaluate((audio: HTMLAudioElement, position) => {
    audio.pause();
    audio.currentTime = position;
  }, featuredPosition);
  await capture(page, 'player');
});

test('subscriptions and listening totals', async ({ page }) => {
  await page.getByRole('link', { name: 'Podcasts' }).click();
  await expect(page.getByRole('heading', { name: 'Subscriptions' })).toBeVisible();
  await expect(page.getByText('Field Notes', { exact: true })).toBeVisible();
  await expect(page.getByText('(1h 3min listened)')).toBeVisible();
  await expect(page.getByText('(no time listened)')).toBeVisible();
  await page.getByRole('listitem')
    .filter({ has: page.getByRole('link', { name: /Paper Trails/ }) })
    .getByRole('button', { name: /^Actions/ }).click();
  await expect(page.getByText("Podcast's homepage", { exact: true })).toBeVisible();
  await expect(page.getByText('RSS feed', { exact: true })).toBeVisible();
  await capture(page, 'podcasts');
});
