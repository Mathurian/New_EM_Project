"""Debug script to investigate login page structure"""
import asyncio
from playwright.async_api import async_playwright

async def debug_login():
    """Investigate the login page structure"""
    playwright = await async_playwright().start()
    browser = await playwright.chromium.launch(
        headless=True,
        args=['--no-sandbox', '--disable-setuid-sandbox']
    )
    
    page = await browser.new_page()
    
    print("Navigating to login page...")
    await page.goto('https://conmgr.com/login', wait_until='domcontentloaded', timeout=60000)
    await asyncio.sleep(3)  # Wait for any JS to load
    
    print(f"\nCurrent URL: {page.url}")
    
    # Get all input fields
    print("\n=== Input Fields Found ===")
    inputs = await page.query_selector_all('input')
    for i, inp in enumerate(inputs):
        input_type = await inp.get_attribute('type')
        input_name = await inp.get_attribute('name')
        input_id = await inp.get_attribute('id')
        input_placeholder = await inp.get_attribute('placeholder')
        input_class = await inp.get_attribute('class')
        print(f"\nInput {i+1}:")
        print(f"  Type: {input_type}")
        print(f"  Name: {input_name}")
        print(f"  ID: {input_id}")
        print(f"  Placeholder: {input_placeholder}")
        print(f"  Class: {input_class}")
    
    # Get all buttons
    print("\n=== Buttons Found ===")
    buttons = await page.query_selector_all('button')
    for i, btn in enumerate(buttons):
        button_type = await btn.get_attribute('type')
        button_text = await btn.inner_text()
        button_id = await btn.get_attribute('id')
        button_class = await btn.get_attribute('class')
        print(f"\nButton {i+1}:")
        print(f"  Type: {button_type}")
        print(f"  Text: {button_text}")
        print(f"  ID: {button_id}")
        print(f"  Class: {button_class}")
    
    # Get page title
    print(f"\n=== Page Info ===")
    print(f"Title: {await page.title()}")
    
    # Check for forms
    forms = await page.query_selector_all('form')
    print(f"Forms found: {len(forms)}")
    
    # Save full HTML
    html = await page.content()
    with open('/workspace/automation/login_page_debug.html', 'w') as f:
        f.write(html)
    print("\nFull HTML saved to: /workspace/automation/login_page_debug.html")
    
    # Take screenshot
    await page.screenshot(path='/workspace/automation/screenshots/debug_login_full.png', full_page=True)
    print("Screenshot saved to: /workspace/automation/screenshots/debug_login_full.png")
    
    await browser.close()
    await playwright.stop()

if __name__ == "__main__":
    asyncio.run(debug_login())
