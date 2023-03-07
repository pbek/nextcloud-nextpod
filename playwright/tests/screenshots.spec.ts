import { test, expect } from '@playwright/test';

test('Create screenshots', async ({ page }) => {
  await page.goto('http://localhost:8081/index.php/login?redirect_url=/index.php/apps/nextpod/actions');

  // Login
  await page.getByLabel('Account name or email').fill('admin');
  // await page.getByLabel('Account name or email').press('Tab');
  await page.getByLabel('Password').fill('admin');
  await page.getByLabel('Password').press('Enter');

  //
  // Take screenshot of "Episodes" page
  //

  //await page.locator('class=list-item-content__main').hover()
  //await page.locator('class=list-item-content__actions').hover()
  //await page.locator('class=list-item-content__actions').click()
  //await page.getByRole('button', { name: 'Actions for item with title "Leadership Lessons from a Disastrous Arctic Expedition"' }).hover()

  // Wait until page renders
  await page.waitForTimeout(3000);
  // Playwright tests run on a default viewport size of 1280x720
  // Try to aim for the 2nd element and open the description page
  await page.mouse.click(1000, 288);
  await page.waitForTimeout(2000);
  await page.screenshot({ path: '../img/screenshots/episode-description.png', fullPage: true });

  // Try to open the context menu
  await page.mouse.click(1150, 288);
  await page.waitForTimeout(500);
  await page.mouse.click(1150, 288);
  // Wait until the context menu opens
  await page.waitForTimeout(1000);

  //await page.getByRole('link', { name: 'LL Leadership Lessons from a Disastrous Arctic Expedition The Art of Manliness Actions for item with title "Leadership Lessons from a Disastrous Arctic Expedition"' }).hover()
  //await page.getByRole('button', { name: 'Actions for item with title "Leadership Lessons from a Disastrous Arctic Expedition"' }).click();
  await page.screenshot({ path: '../img/screenshots/episodes.png', fullPage: true });

  //
  // Take screenshot of "Podcasts" page
  //
  await page.getByRole('link', { name: 'Podcasts' }).click();
  // Wait until page renders
  await page.waitForTimeout(10000);
  // Playwright tests run on a default viewport size of 1280x720
  // Try to aim for the 2nd element
  await page.mouse.click(1150, 288);
  // Wait until the context menu opens
  await page.waitForTimeout(1000);
  // await page.getByRole('button', { name: 'Actions for item with title "3D Printing Today"' }).click();
  await page.screenshot({ path: '../img/screenshots/podcasts.png', fullPage: true });
});
