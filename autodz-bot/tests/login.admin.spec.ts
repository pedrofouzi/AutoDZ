import { test, expect } from '@playwright/test';
import 'dotenv/config';

test('Admin can login', async ({ page }) => {
  const baseURL = process.env.BASE_URL || 'https://caro.laravel.cloud/';
  const email = process.env.ADMIN_EMAIL;
  const password = process.env.ADMIN_PASSWORD;

  await page.goto(baseURL + 'login');

  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');

  // Vérif "souple" : on s’assure qu’on n’est plus sur /login
  await expect(page).not.toHaveURL(/\/login/);
});
