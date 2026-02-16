# UAT Results – Full Suite (Net-New Run)

**Run ID:** full-1771217080483  
**Bootstrap Run:** run1771216460835  
**Tenant:** febtest2  
**Generated:** 2026-02-16

## Summary

| Status | Count |
|--------|-------|
| PASS   | 29    |
| FAIL   | 6     |
| SKIP   | 5     |

## Blockers

1. **TC-LIFE-001** – Setup entities not visible (admin session/login issue during long run)

## Failures (Non-Blocking)

| Test Case   | Role    | Notes                                      |
|-------------|---------|--------------------------------------------|
| TC-NAV-003  | admin   | Admin pages not accessible                 |
| TC-JUDGE-003| Judge   | No category link to score                  |
| TC-BIO-002  | Emcee   | Modal overlay blocked filter click         |
| TC-CERT-002 | Tally   | Tally/certification UI not found           |
| TC-DED-001  | admin   | No deduction creation UI                   |

## Skipped

| Test Case   | Notes                                      |
|-------------|--------------------------------------------|
| TC-JUDGE-004| No comment field; may need prior certification |
| TC-JUDGE-005| No commentary upload control found         |
| TC-BIO-003  | No bio file/image links on page            |
| TC-MFA-001  | Tenant does not have MFA enforced          |
| TC-MFA-002  | Tenant does not have MFA enforced          |

## Scripts

- **scripts/uat/full-uat-bootstrap.js** – EMPTY_TENANT bootstrap (users, event, contest, categories, assignments)
- **scripts/uat/run-full-suite.js** – Full 40-test UAT suite

### Usage

```bash
# 1. Run bootstrap (creates run dir under /tmp/browser-runner/runs/)
cd scripts/uat && node full-uat-bootstrap.js

# 2. Run full suite (uses latest run or RUN_DIR)
RUN_DIR=/tmp/browser-runner/runs/run1771216460835 node run-full-suite.js
```

## Artifacts (in RUN_DIR)

- **bootstrap.json** – Bootstrap metadata (users, events, contests)
- **uat-ids-fresh.json** – Entity IDs from allowed API call
- **results_full_suite.json** – Full results for all 40 test cases
- **summary_full_suite.json** – Overall summary and blockers
- **test-evidence-final/** – Screenshots from test runs
