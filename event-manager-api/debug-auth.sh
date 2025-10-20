#!/bin/bash

echo "🔧 Debugging Authentication Logic"
echo "================================="

# Navigate to the API directory
cd /opt/event-manager/event-manager-api

echo "[INFO] Redis is working! Now debugging authentication logic..."

echo "[INFO] Checking user data in database..."
sudo -u postgres psql event_manager << 'EOF'
-- Get detailed user information
SELECT 
    email, 
    password_hash, 
    is_active, 
    role,
    first_name,
    last_name
FROM users 
WHERE email = 'admin@eventmanager.com';
\q
EOF

echo ""
echo "[INFO] Testing password hash manually..."

# Create a simple test script to verify password hashing
cat > test_password.js << 'EOF'
import bcrypt from 'bcryptjs'

const password = 'admin123'
const hash = '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'

console.log('Testing password:', password)
console.log('Testing hash:', hash)

const isValid = await bcrypt.compare(password, hash)
console.log('Password valid:', isValid)

// Test with different password
const hash2 = '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
const isValid2 = await bcrypt.compare('Dittibop5!', hash2)
console.log('Dittibop5! valid:', isValid2)
EOF

node test_password.js

echo ""
echo "[INFO] Checking UserService authenticateUser method..."
grep -A 20 "authenticateUser" src/services/UserService.js

echo ""
echo "[INFO] Testing authentication with debug logging..."

# Create a debug version of the auth route
cat > debug_auth.js << 'EOF'
import bcrypt from 'bcryptjs'
import { config } from './src/config/index.js'
import knex from 'knex'

// Create database connection
const db = knex({
  client: 'pg',
  connection: {
    host: config.database.host,
    port: config.database.port,
    database: config.database.name,
    user: config.database.user,
    password: config.database.password
  }
})

async function testAuth() {
  try {
    console.log('Testing authentication...')
    
    const email = 'admin@eventmanager.com'
    const password = 'admin123'
    
    console.log('Looking for user:', email)
    
    const user = await db('users')
      .where('email', email)
      .where('is_active', true)
      .first()
    
    console.log('User found:', user ? 'YES' : 'NO')
    if (user) {
      console.log('User details:', {
        id: user.id,
        email: user.email,
        is_active: user.is_active,
        role: user.role
      })
      console.log('Password hash:', user.password_hash)
      
      const isValidPassword = await bcrypt.compare(password, user.password_hash)
      console.log('Password valid:', isValidPassword)
      
      if (isValidPassword) {
        console.log('✅ Authentication should work!')
      } else {
        console.log('❌ Password comparison failed')
      }
    } else {
      console.log('❌ User not found or inactive')
    }
    
  } catch (error) {
    console.error('Error:', error)
  } finally {
    await db.destroy()
  }
}

testAuth()
EOF

node debug_auth.js

echo ""
echo "[INFO] If password is invalid, let's update it..."
echo "[INFO] Updating password to 'admin123'..."

sudo -u postgres psql event_manager << 'EOF'
-- Update password to admin123
UPDATE users SET password_hash = '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'admin@eventmanager.com';
-- This hash corresponds to 'admin123'
SELECT 'Password updated' as status;
\q
EOF

echo ""
echo "[INFO] Testing login again..."
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
  -w "\nHTTP Status: %{http_code}\n"

echo ""
echo "[INFO] Cleanup..."
rm -f test_password.js debug_auth.js
