"""Simplified test runner with better error handling"""
import asyncio
import sys
import os
from pathlib import Path

# Add current directory to path
sys.path.insert(0, str(Path(__file__).parent))

print("Starting test runner...")
print(f"Python version: {sys.version}")
print(f"Working directory: {os.getcwd()}")

try:
    print("Importing modules...")
    from test_executor import main
    
    print("Running tests...")
    asyncio.run(main())
    print("Tests completed!")
    
except Exception as e:
    print(f"ERROR: {type(e).__name__}: {str(e)}")
    import traceback
    traceback.print_exc()
    sys.exit(1)
