# AI UAT Handoff Template

Use this template to brief a browser-based AI tester for a full UAT run.

## 1) Environment

- `BASE_URL`: https://conmgr.com>
- `TENANT_SLUG`: febtest1
- `DATE`: `<YYYY-MM-DD>`
- `RUN_LABEL`: `<optional-run-name>`
- `LIFECYCLE_MODE`: PRESEEDED_TENANT

## 2) Credentials By Role

Provide valid test accounts for each role used in the run.

- `SUPER_ADMIN`: `<email>` / `<password>`
- `ADMIN`: `<email>` / `<password>`
- `ORGANIZER`: `<email>` / `<password>`
- `BOARD`: `<email>` / `<password>`
- `TALLY_MASTER`: `<email>` / `<password>`
- `AUDITOR`: `<email>` / `<password>`
- `JUDGE`: `<email>` / `<password>`
- `EMCEE`: `<email>` / `<password>`
- `CONTESTANT`: `<email>` / `<password>`

### 2.1 Execution Mode

Choose one mode:
- `SINGLE_USER_PER_ROLE`: one account per role (fast baseline run)
- `MULTI_USER_PER_ROLE`: multiple accounts per role (scope and assignment validation)

Set:
- `EXECUTION_MODE`: MULTI_USER_PER_ROLE

### 2.2 Multi-User Role Map (Use when `MULTI_USER_PER_ROLE`)

Provide user lists for roles that need multi-user validation (typically `JUDGE`, `CONTESTANT`, optionally others):

```yaml
users_by_role:
  JUDGE:
    - email: judge1@febtest1.com
      password: Password123!
      label: assigned-judge
    - email: judge2@febtest1.com
      password: Password123!
      label: unassigned-judge
  CONTESTANT:
    - email: contestant12@febtest1.com
      password: Password123!
      label: visibility-disabled
    - email: contestant1@example.com
      password: Password123!
      label: visibility-enabled
  ORGANIZER:
    - email: organizer1@febtest1.com
      password: Password123!
  BOARD:
    - email: board1@febtest1.com
      password: Password123!
  TALLY_MASTER:
    - email: tally1@febtest1.com
      password: Password123!
    - email: tally2@febtest1.com
      password: Password123!
  AUDITOR:
    - email: auditor1@febtest1.com
      password: Password123!
    - email: auditor2@febtest1.com
      password: Password123!
  EMCEE:
    - email: emcee1@febtest1.com
      password: Password123!
```

## 3) Scenario ID Discovery (Required)

After login as `ORGANIZER` (or `ADMIN`/`SUPER_ADMIN`), call:

- `GET /api/v1/test-runner/uat-ids`

Extract and use:
- `singleCategoryScenario`
- `multiCategoryScenario`
- Any required IDs from nested:
  - `events[] -> contests[] -> categories[] -> contestants[]`

If `LIFECYCLE_MODE=EMPTY_TENANT`:
- do not require pre-existing IDs
- create IDs during bootstrap (TC-LIFE-001 / TC-LIFE-002) and reuse for all remaining lifecycle cases in the same run

## 4) Test Cases To Execute

Primary source:
- `docs/testing/Acceptance-Test-Guide.md`
- `docs/testing/E2E-Lifecycle-Track.md`

Machine-readable source:
- `docs/testing/Acceptance-Test-Cases.json`
  - Optional alternate: `docs/testing/Acceptance-Test-Cases.csv`

Human UX reference (if needed):
- `docs/testing/Acceptance-Test-Guide-v2.md`
- `docs/testing/Acceptance-Test-Quick-Run.md`

## 5) Execution Rules

- Test each role in isolation (log out before switching).
- Use tenant-aware URLs.
- Validate both behavior and role scoping.
- Treat runtime/module-load errors, broken routing, and unauthorized data exposure as blockers.
- Capture URL and exact repro steps for every failure.

### 5.1 Single-User Rules

- Run each case once with the single credential for that role.
- Report one result per case ID.

### 5.2 Multi-User Rules

- Run role-global cases against the first user in each role list.
- Run scope-sensitive/assignment-sensitive cases against all relevant users in that role list.
- Case targeting can be done via labels (for example `assigned-judge`, `unassigned-judge`).
- Report results keyed by case and user:
  - `<CASE_ID> | <email-or-label>`
- A case is considered failed if any required targeted user fails.

Suggested targeting examples:
- `TC-JUDGE-001`: all `JUDGE` users
- `TC-JUDGE-002`: `JUDGE` users labeled `unassigned-judge`
- `TC-RES-003`: `CONTESTANT` users labeled `visibility-disabled`
- `TC-LIFE-*`: run based on `LIFECYCLE_MODE`
  - `PRESEEDED_TENANT`: run full lifecycle using existing entities
  - `EMPTY_TENANT`: run setup bootstrap first, then full lifecycle

## 6) Required Output Format

Return one result object per case ID:

```json
{
  "id": "TC-CORE-001",
  "status": "PASS",
  "role": "ADMIN",
  "url": "https://conmgr.com/dashboard",
  "notes": "",
  "evidence": []
}
```

If failed, include:
- expected vs actual
- HTTP status code (if any)
- console/runtime error signature (if any)
- minimal reproducible steps

Final summary required:
- total cases
- pass count
- fail count
- skip count
- blocker list

For `MULTI_USER_PER_ROLE`, also include:
- tested users per role
- pass/fail counts by role
- pass/fail counts by user label (if labels were provided)

## 7) Optional Reset Instruction (Operator-Run)

If your operator supports shell access, reset test state before run:

```bash
scripts/uat/reset-tenant-uat-state.sh --tenant-slug <tenant-slug> --apply
```

For browser-only AI, skip shell reset and proceed with available tenant state.
