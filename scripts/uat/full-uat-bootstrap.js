/**
 * Full EMPTY_TENANT bootstrap: create users, event, contest, categories, criteria, assignments.
 * Saves states for all roles and fetches UAT-IDs.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const RUN_ID = 'run' + Date.now();
const RUN_DIR = path.join('/tmp/browser-runner/runs', RUN_ID);
const BASE = 'https://conmgr.com';
const TENANT = 'febtest2';
const ADMIN_EMAIL = 'admin@febtest2.com';
const PASSWORD = 'Password123!';

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

async function dismissTutorial(page) {
  try {
    const skip = page.locator('text=Skip tutorial, text=Don\'t show, [aria-label="Close"]').first();
    if (await skip.isVisible({ timeout: 1500 })) await skip.click({ force: true });
    await page.keyboard.press('Escape');
    await sleep(300);
  } catch (_) {}
}

async function closeModalAndWait(page) {
  const closeBtn = page.locator('.fixed [aria-label="Close dialog"], .fixed button:has-text("Cancel")').first();
  if (await closeBtn.isVisible({ timeout: 1000 })) {
    await closeBtn.click({ force: true });
    await sleep(800);
  }
  for (let i = 0; i < 6; i++) {
    await page.keyboard.press('Escape');
    await sleep(400);
  }
  await sleep(800);
}

async function main() {
  fs.mkdirSync(RUN_DIR, { recursive: true });
  fs.mkdirSync(path.join(RUN_DIR, 'test-evidence'), { recursive: true });
  fs.mkdirSync(path.join(RUN_DIR, 'test-evidence-final'), { recursive: true });
  fs.mkdirSync(path.join(RUN_DIR, 'states_fresh'), { recursive: true });

  const runLabel = RUN_ID.replace('run', '');
  const users = {
    organizer: { role: 'Organizer', name: `UAT Organizer ${RUN_ID}`, email: `uat_${runLabel}_organizer@febtest2.com` },
    judgeAssigned: { role: 'Judge', name: `UAT Judge Assigned ${RUN_ID}`, email: `uat_${runLabel}_judge1@febtest2.com` },
    judgeUnassigned: { role: 'Judge', name: `UAT Judge Unassigned ${RUN_ID}`, email: `uat_${runLabel}_judge2@febtest2.com` },
    tally: { role: 'Tally Master', name: `UAT Tally ${RUN_ID}`, email: `uat_${runLabel}_tally@febtest2.com` },
    auditor: { role: 'Auditor', name: `UAT Auditor ${RUN_ID}`, email: `uat_${runLabel}_auditor@febtest2.com` },
    board: { role: 'Board', name: `UAT Board ${RUN_ID}`, email: `uat_${runLabel}_board@febtest2.com` },
    emcee: { role: 'Emcee', name: `UAT Emcee ${RUN_ID}`, email: `uat_${runLabel}_emcee@febtest2.com` },
    contestantVD: { role: 'Contestant', name: `UAT Contestant VD ${RUN_ID}`, email: `uat_${runLabel}_c_vd@febtest2.com`, contestantNumber: '401' },
    contestantVE: { role: 'Contestant', name: `UAT Contestant VE ${RUN_ID}`, email: `uat_${runLabel}_c_ve@febtest2.com`, contestantNumber: '402' },
    contestant3: { role: 'Contestant', name: `UAT Contestant 3 ${RUN_ID}`, email: `uat_${runLabel}_c_3@febtest2.com`, contestantNumber: '403' },
    contestant4: { role: 'Contestant', name: `UAT Contestant 4 ${RUN_ID}`, email: `uat_${runLabel}_c_4@febtest2.com`, contestantNumber: '404' },
  };

  const eventName = `UAT ${RUN_ID} Event`;
  const contestName = `UAT ${RUN_ID} Contest`;
  const catA = `UAT ${RUN_ID} Cat A`;
  const catB = `UAT ${RUN_ID} Cat B`;

  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();

  try {
    await page.goto(BASE + '/' + TENANT + '/login', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await sleep(1500);
    await page.fill('input[type="email"], input[name="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"], input[name="password"]', PASSWORD);
    await page.click('button[type="submit"], [type="submit"]');
    await sleep(5000);
    if (page.url().includes('/login')) {
      await sleep(3000);
      if (page.url().includes('/login')) throw new Error('Admin login failed');
    }
    await dismissTutorial(page);

    await page.goto(BASE + '/' + TENANT + '/dashboard', { waitUntil: 'domcontentloaded', timeout: 45000 });
    await sleep(2000);
    await dismissTutorial(page);

    await page.goto(BASE + '/' + TENANT + '/users', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await sleep(2000);
    await dismissTutorial(page);

    const roleMap = { 'Organizer': 'ORGANIZER', 'Judge': 'JUDGE', 'Tally Master': 'TALLY_MASTER', 'Auditor': 'AUDITOR', 'Board': 'BOARD', 'Emcee': 'EMCEE', 'Contestant': 'CONTESTANT' };
    for (const [key, u] of Object.entries(users)) {
      const createBtn = page.locator('button:has-text("Create User")');
      await createBtn.click({ timeout: 8000 });
      await sleep(1500);
      await page.locator('input[name="name"]').fill(u.name);
      await page.locator('input[name="email"]').fill(u.email);
      await page.locator('input[name="password"]').fill(PASSWORD);
      await page.locator('select[name="role"]').selectOption(roleMap[u.role] || u.role);
      await page.locator('form button[type="submit"]').click();
      await sleep(2500);
    }

    await page.goto(BASE + '/' + TENANT + '/events', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await sleep(2000);
    await dismissTutorial(page);
    const eventCreate = page.locator('button:has-text("Create"), a:has-text("Create"), button:has-text("New")').first();
    await eventCreate.click({ timeout: 8000 });
    await sleep(1000);
    await page.locator('input[name="name"]').fill(eventName);
    await page.locator('textarea[name="description"]').fill('UAT bootstrap event');
    await page.locator('input[name="startDate"]').fill('2026-02-15');
    await page.locator('input[name="endDate"]').fill('2026-02-22');
    await page.locator('form button[type="submit"]').click();
    await sleep(2000);

    await page.goto(BASE + '/' + TENANT + '/contests', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await sleep(2000);
    await dismissTutorial(page);
    const contestCreate = page.locator('button:has-text("Create"), a:has-text("Create")').first();
    await contestCreate.click({ timeout: 8000 });
    await sleep(1500);
    const eventSelect = page.locator('select[name="eventId"], select').first();
    await eventSelect.selectOption({ index: 1 });
    await sleep(500);
    await page.locator('input[name="name"]').fill(contestName);
    await page.locator('form button[type="submit"]').click();
    await sleep(2000);

    await page.goto(BASE + '/' + TENANT + '/categories', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await sleep(2000);
    await dismissTutorial(page);
    for (const [cName, cDesc] of [[catA, 'Cat A desc'], [catB, 'Cat B desc']]) {
      const closeBtn = page.locator('.fixed [aria-label="Close dialog"], .fixed button:has-text("Cancel")').first();
      if (await closeBtn.isVisible({ timeout: 500 })) await closeBtn.click();
      await page.keyboard.press('Escape');
      await sleep(800);
      const catCreate = page.locator('button:has-text("Create Category"), button:has-text("Create")').first();
      await catCreate.click({ timeout: 8000, force: true });
      await sleep(3000);
      const contestSel = page.locator('select[name="contestId"], form select').first();
      const optValues = await contestSel.locator('option').evaluateAll(opts => opts.map(o => o.value));
      const val = optValues.find((v, i) => i > 0 && v) || optValues[0];
      if (val) await contestSel.selectOption(val);
      await sleep(500);
      await page.locator('input[name="name"]').fill(cName);
      const descField = page.locator('textarea[name="description"], input[name="description"]');
      if (await descField.count() > 0) await descField.fill(cDesc);
      await page.locator('form button[type="submit"]').click();
      await sleep(2000);
      await page.keyboard.press('Escape');
      await page.locator('[aria-label="Close dialog"], button:has-text("Cancel")').first().click({ timeout: 1000 }).catch(() => {});
      await sleep(500);
    }

    await closeModalAndWait(page);
    for (let i = 0; i < 3; i++) { await page.keyboard.press('Escape'); await sleep(300); }
    await page.goto(BASE + '/' + TENANT + '/assignments', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await sleep(3000);
    await dismissTutorial(page);
    await page.locator('.fixed.inset-0.bg-black').first().click({ position: { x: 5, y: 5 }, timeout: 2000 }).catch(() => {});
    for (let i = 0; i < 5; i++) { await page.keyboard.press('Escape'); await sleep(400); }
    const assignJudge = page.locator('main button:has-text("New")').first();
    await assignJudge.click({ timeout: 8000, force: true });
    await sleep(2000);
    await page.locator('label:has-text("' + users.judgeAssigned.name + '")').click();
    await page.locator('form select').first().selectOption('category');
    await sleep(500);
    await page.locator('form select').nth(1).selectOption({ index: 1 });
    await sleep(1500);
    const selects = await page.locator('form select').all();
    if (selects.length >= 4) {
      await selects[2].selectOption({ index: 1 });
      await sleep(300);
      await selects[3].selectOption({ index: 1 });
    }
    await sleep(500);
    await page.locator('button[type="submit"]:has-text("Assign")').click();
    await sleep(2500);
    await closeModalAndWait(page);

    await page.locator('button:has-text("Tally Masters")').click({ force: true });
    await sleep(1000);
    const assignTally = page.locator('main button:has-text("New")').first();
    await assignTally.click({ timeout: 8000, force: true });
    await sleep(2000);
    await page.locator('label:has-text("' + users.tally.name + '")').click();
    await page.locator('form select').first().selectOption('contest');
    await sleep(500);
    await page.locator('form select').nth(1).selectOption({ index: 1 });
    await sleep(500);
    await page.locator('button[type="submit"]:has-text("Assign")').click();
    await sleep(2500);
    await closeModalAndWait(page);

    await page.locator('button:has-text("Auditors")').click({ force: true });
    await sleep(1000);
    const assignAuditor = page.locator('main button:has-text("New")').first();
    await assignAuditor.click({ timeout: 8000, force: true });
    await sleep(2000);
    await page.locator('label:has-text("' + users.auditor.name + '")').click();
    await page.locator('form select').first().selectOption('contest');
    await sleep(500);
    await page.locator('form select').nth(1).selectOption({ index: 1 });
    await sleep(500);
    await page.locator('button[type="submit"]:has-text("Assign")').click();
    await sleep(2500);
    await closeModalAndWait(page);

    const hasBoard = await page.locator('button:has-text("Board")').count() > 0;
    if (hasBoard) {
      await page.locator('button:has-text("Board")').click({ force: true });
      await sleep(1000);
      const assignBoard = page.locator('main button:has-text("New")').first();
      await assignBoard.click({ timeout: 8000, force: true });
      await sleep(2000);
      const boardLabel = page.locator('label:has-text("' + users.board.name + '")');
      if (await boardLabel.count() > 0) {
        await boardLabel.click();
        await page.locator('form select').first().selectOption('contest');
        await sleep(500);
        await page.locator('form select').nth(1).selectOption({ index: 1 });
        await sleep(500);
        await page.locator('button[type="submit"]:has-text("Assign")').click();
        await sleep(2500);
        await closeModalAndWait(page);
      }
    }

    await page.locator('button:has-text("Contestants")').click({ force: true });
    await sleep(1000);

    for (const c of [users.contestantVE, users.contestant3, users.contestant4]) {
      const assignC = page.locator('main button:has-text("New")').first();
      await assignC.click({ timeout: 8000, force: true });
      await sleep(2000);
      await page.locator('label:has-text("' + c.name + '")').click();
      await page.locator('form select').first().selectOption('category');
      await sleep(500);
      await page.locator('form select').nth(1).selectOption({ index: 1 });
      await sleep(1500);
      const selects = await page.locator('form select').all();
      if (selects.length >= 4) {
        await selects[2].selectOption({ index: 1 });
        await sleep(300);
        await selects[3].selectOption({ index: 1 });
      }
      await sleep(500);
      await page.locator('button[type="submit"]:has-text("Assign")').click();
      await sleep(2500);
      await closeModalAndWait(page);
    }

    await ctx.storageState({ path: path.join(RUN_DIR, 'admin_state.json') });

    const apiCtx = await browser.newContext({ storageState: path.join(RUN_DIR, 'admin_state.json') });
    const apiPage = await apiCtx.newPage();
    const uatRes = await apiPage.request.get(BASE + '/api/v1/test-runner/uat-ids', { headers: { 'Accept': 'application/json' } });
    const uatBody = await uatRes.text();
    fs.writeFileSync(path.join(RUN_DIR, 'uat-ids-fresh.json'), JSON.stringify({ status: uatRes.status(), body: uatBody }));
    await apiCtx.close();

    const rolesToLogin = [
      { key: 'judgeAssigned', stateFile: 'judgeAssigned.json' },
      { key: 'tally', stateFile: 'tally.json' },
      { key: 'auditor', stateFile: 'auditor.json' },
      { key: 'board', stateFile: 'board.json' },
      { key: 'organizer', stateFile: 'organizer.json' },
      { key: 'emcee', stateFile: 'emcee.json' },
    ];
    for (const { key, stateFile } of rolesToLogin) {
      const u = users[key];
      if (!u) continue;
      const roleCtx = await browser.newContext();
      const rolePage = await roleCtx.newPage();
      await rolePage.goto(BASE + '/' + TENANT + '/login', { waitUntil: 'domcontentloaded', timeout: 30000 });
      await sleep(1500);
      await rolePage.fill('input[name="email"]', u.email);
      await rolePage.fill('input[name="password"]', PASSWORD);
      await rolePage.click('button[type="submit"]');
      await sleep(4000);
      await roleCtx.storageState({ path: path.join(RUN_DIR, 'states_fresh', stateFile) });
      await roleCtx.close();
      await sleep(1500);
    }
  } catch (e) {
    console.error('Bootstrap error:', e.message);
    await page.screenshot({ path: path.join(RUN_DIR, 'test-evidence', 'bootstrap-error.png') });
    throw e;
  } finally {
    await browser.close();
  }

  const bootstrap = {
    runId: RUN_ID,
    outDir: RUN_DIR,
    users,
    eventName,
    contestName,
    catA,
    catB,
    adminStatePath: path.join(RUN_DIR, 'admin_state.json'),
  };
  fs.writeFileSync(path.join(RUN_DIR, 'bootstrap.json'), JSON.stringify(bootstrap, null, 2));
  console.log('Bootstrap complete. RUN_ID=' + RUN_ID);
  console.log('RUN_DIR=' + RUN_DIR);
}

main().catch(e => { console.error(e); process.exit(1); });
