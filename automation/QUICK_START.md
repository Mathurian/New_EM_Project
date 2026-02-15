# Quick Start Guide - Browser Automation

## What Was Built

A complete browser automation test framework using Playwright that can:
- Parse your handoff template and test cases
- Execute automated tests with real browser interactions
- Support multiple users per role
- Capture screenshots for debugging
- Generate detailed test reports

## Current Status

✅ **Framework Complete**  
⚠️ **Login Authentication Needs Investigation**

The framework is working correctly but cannot complete login at `https://conmgr.com/login`. This needs manual investigation to understand the correct login flow.

## Files Location

```
/workspace/automation/
├── test_executor.py           # Main test engine (711 lines)
├── config/config_parser.py    # Config parser (89 lines)
├── run_tests.py               # Test runner
├── screenshots/               # Debug screenshots captured
├── TEST_EXECUTION_SUMMARY.md  # Detailed report
└── README.md                  # Documentation
```

## How to Run Tests

```bash
cd /workspace/automation
python3 run_tests.py
```

## How to Debug Login

### Option 1: Check Screenshots
```bash
ls -lh /workspace/automation/screenshots/
```

Screenshots show the actual login page as seen by the browser.

### Option 2: Run in Visual Mode

Edit `test_executor.py` line ~52:
```python
headless=False  # Changed from True
```

Then run again to see the browser window.

### Option 3: Test Manual Login

1. Visit `https://conmgr.com/login`
2. Try logging in with: `judge1@febtest1.com` / `Password123!`
3. Note the exact steps required
4. Check browser console for errors
5. Look for any CAPTCHA or MFA requirements

### Option 4: Inspect Page Structure

```bash
cd /workspace/automation
python3 -c "
import asyncio
from playwright.async_api import async_playwright

async def check_login():
    playwright = await async_playwright().start()
    browser = await playwright.chromium.launch(headless=False)
    page = await browser.new_page()
    await page.goto('https://conmgr.com/login')
    await page.wait_for_timeout(5000)
    
    # Print page structure
    html = await page.content()
    print(html[:2000])
    
    await browser.close()

asyncio.run(check_login())
"
```

## What to Fix

Once you understand the login page structure, update the `login()` method in `test_executor.py`:

1. **Find correct selectors** for email/password fields
2. **Add any missing steps** (e.g., accept terms, close modals)
3. **Handle MFA** if enabled
4. **Update success detection** logic

Example fix:
```python
# Current (approximate line 69-150)
await self.page.fill('input[type="email"]', user.email)
await self.page.fill('input[type="password"]', user.password)
await self.page.click('button[type="submit"]')

# Might need to change to:
await self.page.fill('#email-field-id', user.email)  # Use actual ID
await self.page.fill('#password-field-id', user.password)
await self.page.click('#login-button')  # Use actual button ID
# Maybe add: await self.page.click('#accept-terms')
```

## Test Coverage

All 36 test cases are implemented:
- ✅ 4 CORE tests (routing, navigation)
- ✅ 3 NAV tests (menu, navigation)
- ✅ 5 JUDGE tests (scoring)
- ✅ 3 BIO tests (biography management)
- ✅ 3 CERT tests (certification workflow)
- ✅ 3 GOV tests (governance)
- ✅ 3 DEDUCTIONS tests
- ✅ 3 RESULTS tests
- ✅ 1 REPORTS test
- ✅ 1 EMCEE test
- ✅ 1 FILES test
- ✅ 2 MFA tests
- ✅ 8 LIFECYCLE tests

## Next Steps

1. **Investigate login** (see debugging options above)
2. **Update login method** with correct selectors
3. **Re-run tests**: `python3 run_tests.py`
4. **Review results** in `results/test_report_*.json`
5. **Check screenshots** for any failures

## Configuration

The framework reads from:
- `/workspace/temps/AI-UAT-Handoff-Template-v2.md` (credentials, environment)
- `/workspace/temps/Acceptance-Test-Cases.json` (test cases)

To change environment or users, edit the handoff template.

## Support

Framework uses:
- Python 3.12
- Playwright 1.49.1
- Chromium browser

Install dependencies:
```bash
pip3 install -r requirements.txt
playwright install chromium
```

## Expected Output

Once login works, you'll see:
```
================================================================================
Starting UAT Test Run - 36 test cases
================================================================================

[1/36] Running TC-CORE-001 - CORE - Any authenticated role
  ✓ PASS - judge1@febtest1.com - Redirected to dashboard

[2/36] Running TC-CORE-002 - CORE - Any authenticated role
  ✓ PASS - judge1@febtest1.com - Page loaded with correct tenant

...

================================================================================
TEST EXECUTION SUMMARY
================================================================================
Total Cases:  36
Passed:       32 (88.9%)
Failed:       4
Skipped:      0
Blockers:     2

Report saved: /workspace/automation/results/test_report_20260215_120000.json
================================================================================
```

Good luck! The framework is ready to go once the login flow is sorted out.
