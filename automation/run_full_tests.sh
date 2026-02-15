#!/bin/bash
cd /workspace/automation
echo "Starting full UAT test execution..."
echo "Date: $(date)"
echo "="
python3 -u test_executor.py 2>&1 | tee test_execution_log.txt
echo ""
echo "Tests completed at: $(date)"
echo "Check results in: /workspace/automation/results/"
