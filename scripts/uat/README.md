# UAT Browser Automation

Playwright-based acceptance tests for ConMGR. Requires Node.js and Playwright.

## Setup

```bash
cd scripts/uat
npm init -y
npm install playwright
```

## Usage

1. **Bootstrap** (EMPTY_TENANT – creates users, event, contest, categories, assignments):

   ```bash
   node full-uat-bootstrap.js
   ```

   Output: `/tmp/browser-runner/runs/run<timestamp>/` with `bootstrap.json`, `admin_state.json`, `states_fresh/*.json`, `uat-ids-fresh.json`.

2. **Run full 40-test suite**:

   ```bash
   RUN_DIR=/tmp/browser-runner/runs/run<timestamp> node run-full-suite.js
   ```

   Or omit `RUN_DIR` to use the latest run in `/tmp/browser-runner/runs/`.

## Configuration

- **BASE_URL:** https://conmgr.com
- **TENANT:** febtest2
- **ADMIN:** admin@febtest2.com / Password123!

Edit constants in the scripts to change.
