import { test, expect } from '@playwright/test';
import path from 'path';

async function selectByFieldTitle(page: any, title: RegExp, index = 1) {
  // Cherche un bloc qui contient le titre (ex: "Carburant") puis prend le 1er <select> dedans
  const container = page.locator('div', { hasText: title }).first();
  const sel = container.locator('select').first();
  await expect(sel).toBeVisible({ timeout: 15000 });
  await sel.selectOption({ index });
}


test('Create an ad (annonce) with required fields + 1 image', async ({ page }) => {
test.setTimeout(60000);
  const base = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
  const email = process.env.ADMIN_EMAIL!;
  const password = process.env.ADMIN_PASSWORD!;

  // --- Login ---
  await page.goto(base + '/login');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  // --- Go to create ad ---
  await page.goto(base + '/annonces/create');
  await page.waitForLoadState('domcontentloaded');

  // 1) Vehicle type (choose "Voiture" by visible text)
  await page.getByRole('button', { name: /voiture/i }).click().catch(async () => {
    // fallback if it's not a button (sometimes it's a div)
    await page.getByText(/voiture/i, { exact: false }).first().click();
  });

  // 2) Title (by label)
 await page.getByPlaceholder(/Renault Clio/i).fill('Test bot - annonce');

  // 3) Price (DA) (by label)
 await page.getByPlaceholder(/2500000/i).fill('2500000');

// Marque* (1ère option après placeholder)
await selectByFieldTitle(page, /Marque/i, 1);

// Carburant* (1ère option après placeholder)
await selectByFieldTitle(page, /Carburant/i, 1);

// Boîte de vitesses* (1ère option après placeholder)
await selectByFieldTitle(page, /Boîte de vitesses/i, 1);

  // Optional: Ville/Wilaya (not starred in your capture, but safe)
  const ville = page.getByLabel(/Ville|Wilaya/i).catch(() => null);
  try {
    // @ts-ignore
    if (ville) await ville.fill('Alger');
  } catch {}

  // Upload image
  const imagePath = path.resolve('tests/fixtures/car.jpg');
  const fileInput = page.locator('input[type="file"]').first();
  if (await fileInput.count()) {
    await fileInput.setInputFiles(imagePath);
  }

  // Submit ("Publier l'annonce")
  await page.getByRole('button', { name: /Publier l'annonce/i }).click();

  // Success: URL should change (not stay on create)
  await page.waitForLoadState('networkidle').catch(() => {});
  await expect(page).not.toHaveURL(/\/annonces\/create\/?$/i);
});
