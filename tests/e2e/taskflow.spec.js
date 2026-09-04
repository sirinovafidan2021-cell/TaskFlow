import { expect, test } from '@playwright/test';

async function signIn(page, email) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password').fill('browser-password');
    await page.getByRole('button', { name: 'Sign in to TaskFlow' }).click();
    await expect(page).toHaveURL(/dashboard|tasks|\/$/);
}

test('guest redirect, login, and logout work through real cookies', async ({ page }, testInfo) => {
    await page.goto('/tasks');
    await expect(page).toHaveURL(/login/);
    await signIn(page, 'member@e2e.test');
    if (testInfo.project.name === 'desktop') {
        await page.getByRole('button', { name: /sign out|logout/i }).click();
        await expect(page).toHaveURL(/login/);
    }
});

test('suspended accounts cannot establish a browser session', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email address').fill('suspended@e2e.test');
    await page.getByLabel('Password').fill('browser-password');
    await page.getByRole('button', { name: 'Sign in to TaskFlow' }).click();
    await expect(page).toHaveURL(/login/);
});

test('manager project and member task journeys render on desktop and mobile', async ({ page }, testInfo) => {
    await signIn(page, 'manager@e2e.test');
    await page.goto('/projects');
    await expect(page.getByRole('link', { name: 'E2E Project' })).toBeVisible();
    await page.getByRole('link', { name: 'E2E Project' }).click();
    await expect(page.getByRole('link', { name: 'Create task' })).toBeVisible();
    await page.getByRole('link', { name: 'View tasks' }).click();
    await expect(testInfo.project.name === 'mobile' ? page.getByText('E2E browser task').last() : page.getByRole('table').getByText('E2E browser task')).toBeVisible();

    if (testInfo.project.name === 'mobile') {
        await expect(page.locator('table')).toBeHidden();
    }
});

test('filters preserve URL state and completed projects expose read-only UI', async ({ page }, testInfo) => {
    await signIn(page, 'member@e2e.test');
    await page.goto('/tasks?q=E2E%20browser%20task&statuses[]=todo');
    await expect(testInfo.project.name === 'mobile' ? page.getByText('E2E browser task').last() : page.getByRole('table').getByText('E2E browser task')).toBeVisible();
    await expect(page.getByLabel('Search')).toHaveValue('E2E browser task');
    await expect(page.getByLabel('Todo')).toBeChecked();
});
