#!/bin/bash

echo "🔧 Fixing Password Hash for admin123"
echo "===================================="

# Navigate to the API directory
cd /opt/event-manager/event-manager-api

echo "[INFO] The current hash doesn't match 'admin123'. Let's generate the correct one..."

# Create a script to generate the correct hash for admin123
cat > generate_hash.js << 'EOF'
import bcrypt from 'bcryptjs'

const password = 'admin123'
const rounds = 10

console.log('Generating hash for password:', password)
console.log('Using bcrypt rounds:', rounds)

const hash = await bcrypt.hash(password, rounds)
console.log('Generated hash:', hash)

// Verify the hash works
const isValid = await bcrypt.compare(password, hash)
console.log('Verification test:', isValid ? 'PASS' : 'FAIL')
EOF

echo "[INFO] Generating correct hash for 'admin123'..."
node generate_hash.js

echo ""
echo "[INFO] Updating database with correct hash..."

# Get the generated hash and update the database
HASH_OUTPUT=$(node generate_hash.js | grep "Generated hash:" | cut -d' ' -f3)
echo "Using hash: $HASH_OUTPUT"

sudo -u postgres psql event_manager << EOF
-- Update password with the correct hash
UPDATE users SET password_hash = '$HASH_OUTPUT' WHERE email = 'admin@eventmanager.com';
SELECT 'Password updated with correct hash' as status;
\q
EOF

echo ""
echo "[INFO] Testing password verification..."

# Test the new hash
cat > test_new_hash.js << 'EOF'
import bcrypt from 'bcryptjs'

const password = 'admin123'
const hash = process.argv[2]

console.log('Testing password:', password)
console.log('Testing hash:', hash)

const isValid = await bcrypt.compare(password, hash)
console.log('Password valid:', isValid ? '✅ YES' : '❌ NO')
EOF

node test_new_hash.js "$HASH_OUTPUT"

echo ""
echo "[INFO] Testing login with correct password hash..."
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@eventmanager.com","password":"admin123"}' \
  -w "\nHTTP Status: %{http_code}\n"

echo ""
echo "[INFO] Cleanup..."
rm -f generate_hash.js test_new_hash.js

echo ""
echo "[SUCCESS] Password hash has been corrected!"
echo "[INFO] Login credentials: admin@eventmanager.com / admin123"
