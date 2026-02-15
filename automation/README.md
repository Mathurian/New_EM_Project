# Browser Automation Test Suite

Automated UAT testing for Contest Manager application using Playwright.

## Setup

```bash
pip install -r requirements.txt
playwright install chromium
```

## Configuration

- `temps/AI-UAT-Handoff-Template-v2.md` - Environment and credentials
- `temps/Acceptance-Test-Cases.json` - Test cases

## Usage

```bash
cd /workspace/automation
python test_executor.py
```

## Output

- Test results: `results/test_report_[timestamp].json`
- Screenshots: `screenshots/`

## Test Coverage

- **CORE**: Basic navigation and routing (4 tests)
- **NAV**: Navigation and menu behavior (3 tests)
- **JUDGE**: Scoring functionality (5 tests)
- **BIO**: Biography management (3 tests)
- **CERT**: Certification workflows (3 tests)
- **GOV**: Governance processes (3 tests)
- **DEDUCTIONS**: Deduction management (3 tests)
- **RESULTS**: Results viewing (3 tests)
- **REPORTS**: Report generation (1 test)
- **EMCEE**: Emcee functionality (1 test)
- **FILES**: File management (1 test)
- **MFA**: Multi-factor authentication (2 tests)
- **LIFECYCLE**: Full end-to-end workflows (8 tests)

Total: 36 test cases
