"""Test single login to verify credentials"""
import asyncio
from playwright.async_api import async_playwright

async def test_login():
    """Test login with provided credentials"""
    playwright = await async_playwright().start()
    browser = await playwright.chromium.launch(
        headless=True,
        args=['--no-sandbox', '--disable-setuid-sandbox']
    )
    
    page = await browser.new_page()
    
    # Test credentials from handoff template
    test_users = [
        ('judge1@febtest1.com', 'Password123!'),
        ('organizer1@febtest1.com', 'Password123!'),
        ('emcee1@febtest1.com', 'Password123!'),
    ]
    
    for email, password in test_users:
        print(f"\n{'='*60}")
        print(f"Testing login: {email}")
        print(f"{'='*60}")
        
        try:
            # Navigate to login
            print("→ Navigating to login page...")
            await page.goto('https://conmgr.com/login', wait_until='domcontentloaded', timeout=30000)
            await asyncio.sleep(2)
            
            # Clear any previous values
            await page.fill('#email', '')
            await page.fill('#password', '')
            
            # Fill login form
            print(f"→ Filling email: {email}")
            await page.fill('#email', email)
            
            print("→ Filling password...")
            await page.fill('#password', password)
            
            await asyncio.sleep(0.5)
            
            # Take screenshot before submit
            await page.screenshot(path=f'/workspace/automation/screenshots/before_submit_{email.split("@")[0]}.png')
            
            # Click submit and wait for navigation
            print("→ Clicking submit button...")
            
            # Set up response listener to catch any errors
            async def handle_response(response):
                if 'api' in response.url:
                    print(f"  API Response: {response.url} - Status: {response.status}")
                    if response.status >= 400:
                        try:
                            body = await response.json()
                            print(f"  Error body: {body}")
                        except:
                            pass
            
            page.on('response', handle_response)
            
            # Click and wait
            await page.click('button[type="submit"]')
            
            # Wait a bit for the response
            await asyncio.sleep(3)
            
            current_url = page.url
            print(f"→ Current URL after submit: {current_url}")
            
            # Check if login was successful
            if current_url != 'https://conmgr.com/login' and 'login' not in current_url.lower():
                print(f"✓ LOGIN SUCCESSFUL - Redirected to: {current_url}")
                await page.screenshot(path=f'/workspace/automation/screenshots/success_{email.split("@")[0]}.png')
                
                # Try to navigate to dashboard
                await page.goto('https://conmgr.com/febtest1/dashboard', wait_until='domcontentloaded', timeout=10000)
                await asyncio.sleep(1)
                print(f"→ Dashboard URL: {page.url}")
                await page.screenshot(path=f'/workspace/automation/screenshots/dashboard_{email.split("@")[0]}.png')
                
            else:
                print(f"✗ LOGIN FAILED - Still on login page")
                
                # Check for error messages
                error_selectors = [
                    '.error',
                    '.alert',
                    '[role="alert"]',
                    '.text-red-500',
                    '.text-red-600'
                ]
                
                for selector in error_selectors:
                    try:
                        error_elem = await page.query_selector(selector)
                        if error_elem:
                            error_text = await error_elem.inner_text()
                            if error_text.strip():
                                print(f"  Error message: {error_text}")
                    except:
                        pass
                
                await page.screenshot(path=f'/workspace/automation/screenshots/failed_{email.split("@")[0]}.png')
            
        except Exception as e:
            print(f"✗ EXCEPTION: {str(e)}")
        
        # Wait before next test
        await asyncio.sleep(2)
    
    await browser.close()
    await playwright.stop()

if __name__ == "__main__":
    asyncio.run(test_login())
