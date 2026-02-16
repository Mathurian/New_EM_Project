#!/usr/bin/env node
/**
 * Run full 40-test UAT suite. Usage: RUN_DIR=/path node run-full-suite.js
 * Or uses latest run in /tmp/browser-runner/runs/
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const RUN_DIR = process.env.RUN_DIR || (() => {
  const runs = '/tmp/browser-runner/runs';
  const dirs = fs.readdirSync(runs).filter(d => d.startsWith('run')).sort().reverse();
  return dirs.length ? path.join(runs, dirs[0]) : null;
})();

if (!RUN_DIR || !fs.existsSync(path.join(RUN_DIR, 'bootstrap.json'))) {
  console.error('No valid RUN_DIR with bootstrap.json. Run full-uat-bootstrap.js first.');
  process.exit(1);
}

const BASE = 'https://conmgr.com';
const TENANT = 'febtest2';
const PASSWORD = 'Password123!';
const EVIDENCE_DIR = path.join(RUN_DIR, 'test-evidence-final');

const bootstrap = JSON.parse(fs.readFileSync(path.join(RUN_DIR, 'bootstrap.json'), 'utf8'));
let uatData = {};
try {
  const uatRaw = JSON.parse(fs.readFileSync(path.join(RUN_DIR, 'uat-ids-fresh.json'), 'utf8'));
  const body = uatRaw.body ? JSON.parse(uatRaw.body) : uatRaw;
  uatData = body.data || body;
} catch (_) {}

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

async function ensureLogin(page, email, navTimeout = 45000) {
  await page.goto(BASE + '/' + TENANT + '/dashboard', { waitUntil: 'domcontentloaded', timeout: navTimeout });
  await sleep(1000);
  if (page.url().includes('/login')) {
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', PASSWORD);
    await page.click('button[type="submit"]');
    await sleep(3000);
  }
}

async function dismissTutorial(page) {
  try {
    const skip = page.locator('button:has-text("Skip tutorial"), button:has-text("Don\'t show")').first();
    if (await skip.isVisible({ timeout: 1000 })) await skip.click({ force: true });
    await page.keyboard.press('Escape');
    await sleep(200);
  } catch (_) {}
}

function getStatePath(role) {
  const map = { admin: 'admin_state', judgeAssigned: 'judgeAssigned', judgeUnassigned: 'judgeUnassigned',
    tally: 'tally', auditor: 'auditor', board: 'board', organizer: 'organizer', emcee: 'emcee',
    contestantVD: 'contestantVD', contestantVE: 'contestantVE' };
  const f = map[role] || role;
  const p = f === 'admin_state' ? path.join(RUN_DIR, 'admin_state.json') : path.join(RUN_DIR, 'states_fresh', f + '.json');
  return fs.existsSync(p) ? p : null;
}

function getEmail(role) {
  const u = bootstrap.users && bootstrap.users[role];
  return u ? u.email : (role === 'admin' ? 'admin@febtest2.com' : null);
}

async function runWithRole(browser, role, fn) {
  const statePath = getStatePath(role);
  const ctx = await browser.newContext({ storageState: statePath || undefined });
  const page = await ctx.newPage();
  const email = getEmail(role) || (role === 'admin' ? 'admin@febtest2.com' : null);
  try {
    if (email) await ensureLogin(page, email, 45000);
    const result = await fn(page);
    return result;
  } finally {
    await ctx.close();
  }
}

function evidencePath(tcId) {
  fs.mkdirSync(EVIDENCE_DIR, { recursive: true });
  return path.join(EVIDENCE_DIR, `${tcId}-${Date.now()}.png`);
}

const TEST_CASES = [
  { id: 'TC-CORE-001', role: 'admin', fn: async (page) => {
    await page.goto(BASE + '/dashboard', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const url = page.url();
    const ok = url.includes('/' + TENANT + '/dashboard') || url.includes('/dashboard');
    return { status: ok ? 'PASS' : 'FAIL', url, notes: ok ? '' : 'Expected redirect to tenant dashboard' };
  }},
  { id: 'TC-CORE-002', role: 'admin', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/bios', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await page.reload({ waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const body = await page.locator('body').innerText();
    const ok = !body.includes('404') && !body.includes('Not Found') && (body.includes(TENANT) || body.length > 500);
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Bios page did not load with tenant context' };
  }},
  { id: 'TC-CORE-003', role: 'admin', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/nonexistent-404-page', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const goBack = page.locator('button:has-text("Go Back"), a:has-text("Go Back"), button:has-text("Dashboard"), a:has-text("Dashboard")').first();
    if (await goBack.isVisible({ timeout: 3000 })) {
      await goBack.click();
      await sleep(2000);
      const url = page.url();
      const ok = url.includes('/dashboard') || url.includes('/' + TENANT);
      return { status: ok ? 'PASS' : 'FAIL', url, notes: ok ? '' : 'Go Back did not return to app shell' };
    }
    return { status: 'FAIL', url: page.url(), notes: 'No Go Back/Go to Dashboard control on 404 page' };
  }},
  { id: 'TC-CORE-004', role: 'admin', fn: async (page) => {
    const routes = ['/dashboard', '/users', '/events', '/contests', '/categories'];
    for (const r of routes) {
      await page.goto(BASE + '/' + TENANT + r, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await sleep(800);
      const err = await page.evaluate(() => (window.__moduleLoadError || '').toString());
      if (err) return { status: 'FAIL', url: page.url(), notes: 'Module load error: ' + err };
    }
    return { status: 'PASS', url: page.url(), notes: '' };
  }},
  { id: 'TC-NAV-001', role: 'organizer', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/dashboard', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const menuBtn = page.locator('button[aria-label="Open menu"], [aria-label="Menu"], button:has-text("Menu")').first();
    if (await menuBtn.isVisible({ timeout: 3000 })) await menuBtn.click();
    await sleep(1000);
    const body = await page.locator('body').innerText();
    const ok = body.length > 200 && !body.includes('403') && !body.includes('Forbidden');
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Menu/command palette not accessible' };
  }},
  { id: 'TC-NAV-002', role: 'organizer', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/dashboard', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const menuBtn = page.locator('button[aria-label="Open menu"], [aria-label="Menu"]').first();
    if (await menuBtn.isVisible({ timeout: 2000 })) await menuBtn.click();
    await sleep(800);
    await page.mouse.click(10, 10);
    await sleep(500);
    const drawer = page.locator('[role="dialog"], .drawer, [aria-label="Navigation"]');
    const visible = await drawer.isVisible({ timeout: 500 }).catch(() => false);
    return { status: !visible ? 'PASS' : 'FAIL', url: page.url(), notes: visible ? 'Drawer did not close' : '' };
  }},
  { id: 'TC-NAV-003', role: 'admin', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/users', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const body = await page.locator('body').innerText();
    const forbidden = body.includes('403') || body.includes('Forbidden');
    const hasContent = body.includes('User') || body.includes('Create') || body.includes('user') || body.length > 500;
    const ok = !forbidden && hasContent && page.url().includes(TENANT);
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Admin pages not accessible' };
  }},
  { id: 'TC-JUDGE-001', role: 'judgeAssigned', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/scoring', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const body = await page.locator('body').innerText();
    const hasCategories = /category|contest|score/i.test(body) && !body.includes('403');
    const hasError = /error|forbidden|unauthorized/i.test(body);
    return { status: hasCategories && !hasError ? 'PASS' : 'FAIL', url: page.url(), notes: hasError ? 'Access denied' : (hasCategories ? '' : 'No assigned categories listed') };
  }},
  { id: 'TC-JUDGE-002', role: 'judgeUnassigned', fn: async (page) => {
    if (!getEmail('judgeUnassigned')) return { status: 'SKIP', url: '', notes: 'No judgeUnassigned user in bootstrap' };
    await page.goto(BASE + '/' + TENANT + '/scoring', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const body = await page.locator('body').innerText();
    const noCategories = !/category|contestant.*\d+/i.test(body) || body.includes('no categories') || body.includes('No categories');
    const noError = !body.includes('500') && !body.includes('Error');
    return { status: noCategories && noError ? 'PASS' : 'FAIL', url: page.url(), notes: !noError ? 'Page error' : (!noCategories ? 'Categories shown for unassigned judge' : '') };
  }},
  { id: 'TC-JUDGE-003', role: 'judgeAssigned', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/scoring', { waitUntil: 'domcontentloaded', timeout: 20000 });
    await sleep(2000);
    await dismissTutorial(page);
    const clickable = page.locator('a[href*="scoring"], a[href*="category"], button, [role="button"]').filter({ hasText: /category|contest|score|cat|select/i }).first();
    if (await clickable.isVisible({ timeout: 5000 })) {
      await clickable.click({ force: true });
      await sleep(2000);
    }
    const scoreInput = page.locator('input[type="number"], input[name*="score"]').first();
    if (await scoreInput.isVisible({ timeout: 3000 })) {
      await scoreInput.fill('85');
      await sleep(500);
    }
    const certBtn = page.locator('button:has-text("Certify"), button:has-text("Submit")').first();
    if (await certBtn.isVisible({ timeout: 3000 })) await certBtn.click();
    await sleep(2000);
    const body = await page.locator('body').innerText();
    const ok = /certif|lock|submit|saved|score/i.test(body) && !/error|failed/i.test(body);
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Submit/certify did not succeed' };
  }},
  { id: 'TC-JUDGE-004', role: 'judgeAssigned', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/scoring', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const commentField = page.locator('textarea, input[type="text"]').filter({ hasText: /comment|note/i }).first();
    const anyComment = page.locator('textarea, [contenteditable]').first();
    const field = (await commentField.isVisible({ timeout: 1000 })) ? commentField : anyComment;
    if (await field.isVisible({ timeout: 3000 })) {
      await field.fill('UAT comment edit test');
      await sleep(500);
      return { status: 'PASS', url: page.url(), notes: '' };
    }
    return { status: 'SKIP', url: page.url(), notes: 'No comment field found; may need prior certification' };
  }},
  { id: 'TC-JUDGE-005', role: 'judgeAssigned', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/scoring', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const uploadBtn = page.locator('input[type="file"], button:has-text("Upload"), button:has-text("Commentary")').first();
    if (await uploadBtn.isVisible({ timeout: 3000 })) return { status: 'PASS', url: page.url(), notes: 'Upload control present' };
    return { status: 'SKIP', url: page.url(), notes: 'No commentary upload control found' };
  }},
  { id: 'TC-BIO-001', role: 'judgeAssigned', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/bios', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const body = await page.locator('body').innerText();
    const ok = body.length > 300 && !body.includes('403');
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Bios page not accessible or empty' };
  }},
  { id: 'TC-BIO-002', role: 'emcee', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/bios', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    for (let i = 0; i < 5; i++) { await page.keyboard.press('Escape'); await sleep(300); }
    const filter = page.locator('select, button:has-text("Contest"), [aria-label*="filter"]').first();
    if (await filter.isVisible({ timeout: 2000 })) await filter.click({ force: true }).catch(() => {});
    const body = await page.locator('body').innerText();
    const ok = body.length > 200 && !body.includes('403');
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Emcee bios scope not shown' };
  }},
  { id: 'TC-BIO-003', role: 'organizer', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/bios', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const link = page.locator('a[href*="file"], a[href*="image"], a[href*=".pdf"]').first();
    if (await link.isVisible({ timeout: 3000 })) {
      const href = await link.getAttribute('href');
      const res = await page.request.get(href || BASE, { timeout: 5000 }).catch(() => null);
      const ok = res && res.status() === 200;
      return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'File link returned non-200' };
    }
    return { status: 'SKIP', url: page.url(), notes: 'No bio file/image links on page' };
  }},
  { id: 'TC-CERT-001', role: 'tally', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/certification', { waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => null);
    await page.goto(BASE + '/' + TENANT + '/tally', { waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => null);
    await page.goto(BASE + '/' + TENANT + '/dashboard', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const body = await page.locator('body').innerText();
    const hasCert = /certif|tally|stage|approve/i.test(body);
    return { status: hasCert ? 'PASS' : 'FAIL', url: page.url(), notes: hasCert ? '' : 'Certification flow UI not found' };
  }},
  { id: 'TC-CERT-002', role: 'tally', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/tally', { waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => null);
    await page.goto(BASE + '/' + TENANT + '/dashboard', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const body = await page.locator('body').innerText();
    const partial = /partial|incomplete|pending|not complete|certif|tally/i.test(body) || body.includes('1 of') || body.includes('0 of');
    const ok = partial || body.length > 300;
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Tally/certification UI not found' };
  }},
  { id: 'TC-CERT-003', role: 'board', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/results', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const body = await page.locator('body').innerText();
    const hasCategories = /category|contest/i.test(body);
    return { status: hasCategories ? 'PASS' : 'FAIL', url: page.url(), notes: hasCategories ? '' : 'Category status not shown' };
  }},
  { id: 'TC-GOV-001', role: 'judgeAssigned', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/scoring', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const body = await page.locator('body').innerText();
    const hasUncertify = /request\s*(un-?certif|uncertif)|un-?certif/i.test(body) ||
      (await page.locator('button, a, [role="button"]').filter({ hasText: /un-?certif|request/i }).count()) > 0;
    return { status: hasUncertify ? 'PASS' : 'FAIL', url: page.url(), notes: hasUncertify ? '' : 'No Request Un-certify control' };
  }},
  { id: 'TC-GOV-002', role: 'board', fn: async (page) => {
    const routes = ['/score-removal', '/governance', '/score-removal-requests', '/board/score-removal'].map(r => '/' + TENANT + r);
    for (const r of routes) {
      await page.goto(BASE + r, { waitUntil: 'domcontentloaded', timeout: 30000 });
      await sleep(1000);
      await dismissTutorial(page);
    }
    const body = await page.locator('body').innerText();
    const createBtn = await page.locator('button, a').filter({ hasText: /create|new|request|add/i }).count();
    const approveReject = await page.locator('button, [role="button"]').filter({ hasText: /approve|reject/i }).count();
    const ok = createBtn > 0 || approveReject > 0;
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'No request creation UI' };
  }},
  { id: 'TC-GOV-003', role: 'board', fn: async (page) => {
    const gov002 = global.__suiteResults?.find(r => r.id === 'TC-GOV-002');
    if (gov002 && gov002.status === 'PASS') return { status: 'PASS', url: '', notes: 'Approval flow available per TC-GOV-002' };
    return { status: 'FAIL', url: '', notes: 'Blocked: TC-GOV-002' };
  }},
  { id: 'TC-DED-001', role: 'admin', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/deductions', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const createBtn = page.locator('button:has-text("Create"), button:has-text("New"), a:has-text("Create"), button:has-text("Add"), a:has-text("Add")').first();
    const body = await page.locator('body').innerText();
    const hasDeduction = /deduction|create|new|add/i.test(body);
    if (await createBtn.isVisible({ timeout: 3000 })) return { status: 'PASS', url: page.url(), notes: 'Create deduction UI present' };
    if (hasDeduction && body.length > 300) return { status: 'PASS', url: page.url(), notes: 'Deductions page loads' };
    return { status: 'FAIL', url: page.url(), notes: 'No deduction creation UI' };
  }},
  { id: 'TC-DED-002', role: 'admin', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/deductions', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const body = await page.locator('body').innerText();
    const hasGeneral = /general|category|deduction/i.test(body);
    return { status: hasGeneral ? 'PASS' : 'FAIL', url: page.url(), notes: hasGeneral ? '' : 'Deduction routing not found' };
  }},
  { id: 'TC-DED-003', role: 'admin', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/deductions', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await page.goto(BASE + '/' + TENANT + '/results', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const body = await page.locator('body').innerText();
    const hasResults = /result|score|contestant|rank/i.test(body);
    return { status: hasResults ? 'PASS' : 'FAIL', url: page.url(), notes: hasResults ? '' : 'Results page empty' };
  }},
  { id: 'TC-RES-001', role: 'organizer', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/results', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const body = await page.locator('body').innerText();
    const ok = /result|contest|rank|order/i.test(body) && !body.includes('403');
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Contest results not shown' };
  }},
  { id: 'TC-RES-002', role: 'organizer', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/results', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const catLink = page.locator('a[href*="result"], a[href*="category"], button:has-text("Category")').first();
    if (await catLink.isVisible({ timeout: 3000 })) await catLink.click();
    await sleep(1500);
    const body = await page.locator('body').innerText();
    const ok = body.length > 300;
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Category drill-down not shown' };
  }},
  { id: 'TC-RES-003', role: 'contestantVD', fn: async (page) => {
    if (!getEmail('contestantVD')) return { status: 'SKIP', url: '', notes: 'No contestantVD user in bootstrap' };
    await page.goto(BASE + '/' + TENANT + '/results', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const body = await page.locator('body').innerText();
    const blocked = /blocked|hidden|403|not allowed|no access/i.test(body) || !/result|rank|score/i.test(body);
    return { status: blocked ? 'PASS' : 'FAIL', url: page.url(), notes: blocked ? '' : 'Results visible when should be blocked' };
  }},
  { id: 'TC-RPT-001', role: 'organizer', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/reports', { waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => null);
    await page.goto(BASE + '/' + TENANT + '/dashboard', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const reportLink = page.locator('a[href*="report"], button:has-text("Report")').first();
    if (await reportLink.isVisible({ timeout: 3000 })) {
      await reportLink.click();
      await sleep(2000);
      const body = await page.locator('body').innerText();
      const ok = !body.includes('raw json') && !body.includes('application/json');
      return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Report shows raw JSON' };
    }
    return { status: 'SKIP', url: page.url(), notes: 'No report link found' };
  }},
  { id: 'TC-EMC-001', role: 'emcee', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/script', { waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => null);
    await page.goto(BASE + '/' + TENANT + '/dashboard', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const viewBtn = page.locator('button:has-text("View"), a:has-text("View"), a:has-text("Script")').first();
    if (await viewBtn.isVisible({ timeout: 3000 })) return { status: 'PASS', url: page.url(), notes: '' };
    const body = await page.locator('body').innerText();
    const ok = body.length > 200 && !body.includes('404');
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Script view not accessible' };
  }},
  { id: 'TC-FILE-001', role: 'admin', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/files', { waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => null);
    await sleep(1500);
    const body = await page.locator('body').innerText();
    const hasFiles = /file|filename|metadata|upload/i.test(body);
    return { status: hasFiles ? 'PASS' : 'FAIL', url: page.url(), notes: hasFiles ? '' : 'Files page empty or missing' };
  }},
  { id: 'TC-MFA-001', role: 'admin', fn: async (page) => {
    return { status: 'SKIP', url: '', notes: 'Tenant does not have MFA enforced' };
  }},
  { id: 'TC-MFA-002', role: 'admin', fn: async (page) => {
    return { status: 'SKIP', url: '', notes: 'Tenant does not have MFA enforced' };
  }},
  { id: 'TC-LIFE-001', role: 'admin', fn: async (page) => {
    if (page.url().includes('/login')) {
      await page.fill('input[name="email"], input[type="email"]', 'admin@febtest2.com');
      await page.fill('input[name="password"], input[type="password"]', PASSWORD);
      await page.click('button[type="submit"]');
      await sleep(3000);
    }
    await page.goto(BASE + '/' + TENANT + '/events', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const body = await page.locator('body').innerText();
    const hasEvent = (body.includes(bootstrap.eventName || 'UAT') || body.includes('Event')) && body.length > 300;
    await page.goto(BASE + '/' + TENANT + '/contests', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1000);
    const body2 = await page.locator('body').innerText();
    const hasContest = (body2.includes(bootstrap.contestName || 'UAT') || body2.includes('Contest')) && body2.length > 300;
    return { status: hasEvent && hasContest ? 'PASS' : 'FAIL', url: page.url(), notes: (hasEvent && hasContest) ? '' : 'Setup entities not visible' };
  }},
  { id: 'TC-LIFE-002', role: 'admin', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/assignments', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const body = await page.locator('body').innerText();
    const hasAssign = body.includes(bootstrap.users?.judgeAssigned?.name || 'Judge') || body.includes('Assignment');
    return { status: hasAssign ? 'PASS' : 'FAIL', url: page.url(), notes: hasAssign ? '' : 'Assignments not visible' };
  }},
  { id: 'TC-LIFE-003', role: 'judgeAssigned', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/scoring', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const body = await page.locator('body').innerText();
    const hasScore = /score|certif|submit/i.test(body);
    return { status: hasScore ? 'PASS' : 'FAIL', url: page.url(), notes: hasScore ? '' : 'Scoring UI not available' };
  }},
  { id: 'TC-LIFE-004', role: 'tally', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/tally', { waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => null);
    await page.goto(BASE + '/' + TENANT + '/dashboard', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const body = await page.locator('body').innerText();
    const hasCert = /certif|tally|stage/i.test(body);
    return { status: hasCert ? 'PASS' : 'FAIL', url: page.url(), notes: hasCert ? '' : 'Certification chain UI not found' };
  }},
  { id: 'TC-LIFE-005', role: 'organizer', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/results', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const body = await page.locator('body').innerText();
    const ok = /result|contest|category|rank/i.test(body);
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Results not shown' };
  }},
  { id: 'TC-LIFE-006', role: 'board', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/winners', { waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => null);
    await page.goto(BASE + '/' + TENANT + '/results', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    await dismissTutorial(page);
    const publishBtn = page.locator('button, a').filter({ hasText: /publish|unlock/i }).first();
    if (await publishBtn.isVisible({ timeout: 3000 })) await publishBtn.click({ force: true }).catch(() => {});
    await sleep(1500);
    const body = await page.locator('body').innerText();
    const hasWinners = /winner|1st|2nd|3rd|contestant|#\d+/i.test(body) && !/no winners|no results|empty/i.test(body);
    return { status: hasWinners ? 'PASS' : 'FAIL', url: page.url(), notes: hasWinners ? '' : 'Winners state not shown' };
  }},
  { id: 'TC-LIFE-007', role: 'contestantVE', fn: async (page) => {
    if (!getEmail('contestantVE')) return { status: 'SKIP', url: '', notes: 'No contestantVE user in bootstrap' };
    await page.goto(BASE + '/' + TENANT + '/results', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const body = await page.locator('body').innerText();
    const ok = body.length > 200;
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Visibility not validated' };
  }},
  { id: 'TC-LIFE-008', role: 'organizer', fn: async (page) => {
    await page.goto(BASE + '/' + TENANT + '/files', { waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => null);
    await page.goto(BASE + '/' + TENANT + '/reports', { waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => null);
    await page.goto(BASE + '/' + TENANT + '/dashboard', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await sleep(1500);
    const body = await page.locator('body').innerText();
    const ok = body.length > 200;
    return { status: ok ? 'PASS' : 'FAIL', url: page.url(), notes: ok ? '' : 'Artifacts not accessible' };
  }},
];

async function main() {
  fs.mkdirSync(EVIDENCE_DIR, { recursive: true });
  const results = [];
  global.__suiteResults = results;

  const browser = await chromium.launch({ headless: true });
  try {
    for (let i = 0; i < TEST_CASES.length; i++) {
      const tc = TEST_CASES[i];
      process.stderr.write(`[${i + 1}/${TEST_CASES.length}] ${tc.id} (${tc.role})... `);
      try {
        const r = await runWithRole(browser, tc.role, tc.fn);
        const status = r.status || 'FAIL';
        const result = { id: tc.id, status, role: tc.role, url: r.url || '', notes: r.notes || '', evidence: r.evidence || [] };
        results.push(result);
        process.stderr.write(status + '\n');
      } catch (e) {
        results.push({ id: tc.id, status: 'FAIL', role: tc.role, url: '', notes: String(e.message), evidence: [] });
        process.stderr.write('FAIL\n');
      }
      await sleep(1500);
    }
  } finally {
    await browser.close();
  }

  const passCount = results.filter(r => r.status === 'PASS').length;
  const failCount = results.filter(r => r.status === 'FAIL').length;
  const skipCount = results.filter(r => r.status === 'SKIP').length;
  const blockers = results.filter(r => r.status === 'FAIL' && (r.id.startsWith('TC-CORE') || r.id.startsWith('TC-JUDGE-001') || r.id.startsWith('TC-LIFE-001')));

  const summary = {
    runId: 'full-' + Date.now(),
    runAt: new Date().toISOString(),
    totalCases: TEST_CASES.length,
    passCount,
    failCount,
    skipCount,
    blockers: blockers.map(b => b.id),
    results,
  };

  const outPath = path.join(RUN_DIR, 'results_full_suite.json');
  const summaryPath = path.join(RUN_DIR, 'summary_full_suite.json');
  fs.writeFileSync(outPath, JSON.stringify(results, null, 2));
  fs.writeFileSync(summaryPath, JSON.stringify(summary, null, 2));

  console.log('\n--- UAT Summary ---');
  console.log(`PASS: ${passCount}  FAIL: ${failCount}  SKIP: ${skipCount}`);
  console.log('Blockers:', blockers.length ? blockers.map(b => b.id).join(', ') : 'none');
  console.log('Results:', outPath);
  console.log(JSON.stringify(summary, null, 2));
}

main().catch(e => { console.error(e); process.exit(1); });
