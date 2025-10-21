# Path-Based Routing Architecture

## Summary
The relative URL implementation enables path-based routing, eliminating the need for subdomains and providing a unified domain structure for both frontend and backend services.

## 🌐 Path-Based Routing Architecture

### **Current Implementation (Relative URLs)**
```
Frontend: https://eventmanager.com/
API: https://eventmanager.com/api/
WebSocket: wss://eventmanager.com/
```

### **Reverse Proxy Configuration (Nginx)**
```nginx
server {
    listen 80;
    server_name eventmanager.com;
    
    # API routes - proxy to backend
    location /api/ {
        proxy_pass http://localhost:3000/api/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
    
    # WebSocket routes - proxy to backend
    location /socket.io/ {
        proxy_pass http://localhost:3000/socket.io/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    # Frontend routes - serve static files
    location / {
        root /path/to/event-manager/frontend/dist;
        try_files $uri $uri/ /index.html;
        
        # Security headers
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-XSS-Protection "1; mode=block" always;
    }
}
```

## ✅ Frontend Benefits

### **1. Simplified Configuration**
```javascript
// Before (absolute URLs)
const api = axios.create({
  baseURL: 'https://api.eventmanager.com',  // Hardcoded
  timeout: 10000,
})

// After (relative URLs)
const api = axios.create({
  baseURL: '/api',  // Adapts to current domain
  timeout: 10000,
})
```

### **2. Environment Portability**
```bash
# Development
http://localhost:3001/api/users

# Staging  
https://staging.eventmanager.com/api/users

# Production
https://eventmanager.com/api/users

# Same code, different environments!
```

### **3. Automatic Protocol Detection**
```javascript
// WebSocket automatically adapts
const socket = io()  // Uses current origin + protocol
// HTTP → ws://
// HTTPS → wss://
```

## 🚀 Subdomain Elimination Benefits

### **DNS Simplification**
```bash
# Before (subdomains)
app.eventmanager.com    A    192.168.80.246
api.eventmanager.com    A    192.168.80.246
ws.eventmanager.com     A    192.168.80.246

# After (path-based)
eventmanager.com        A    192.168.80.246
# Everything handled by reverse proxy
```

### **SSL Certificate Management**
```bash
# Before (multiple certificates)
*.eventmanager.com      # Wildcard certificate needed
api.eventmanager.com    # Additional certificate
ws.eventmanager.com     # Additional certificate

# After (single certificate)
eventmanager.com        # Single certificate covers all paths
```

### **Load Balancing Simplification**
```nginx
# Path-based load balancing
upstream backend {
    server backend1:3000;
    server backend2:3000;
    server backend3:3000;
}

location /api/ {
    proxy_pass http://backend/api/;
}
```

## 🔧 Implementation in Setup Script

The setup script now supports this architecture:

### **Domain-Based Configuration**
```bash
# Setup with domain (enables path-based routing)
./setup.sh --non-interactive --domain=eventmanager.com

# Results in:
# VITE_API_URL=https://eventmanager.com
# VITE_WS_URL=wss://eventmanager.com
```

### **Relative URL Configuration**
```bash
# Setup without domain (uses relative URLs)
./setup.sh --non-interactive

# Results in:
# VITE_API_URL=""  # Empty = relative URLs
# VITE_WS_URL=""   # Empty = relative URLs
```

## 📊 Comparison: Subdomain vs Path-Based

| Aspect | Subdomain Approach | Path-Based Approach |
|--------|-------------------|-------------------|
| **DNS Records** | Multiple A records | Single A record |
| **SSL Certificates** | Wildcard or multiple | Single certificate |
| **CORS Issues** | Cross-origin requests | Same-origin requests |
| **Load Balancing** | DNS-based complexity | Path-based simplicity |
| **CDN Integration** | Multiple origins | Single origin |
| **Development** | Different configs per env | Same config everywhere |
| **Maintenance** | Complex certificate mgmt | Simple certificate mgmt |

## 🌐 Real-World Examples

### **Successful Path-Based Implementations**
- **GitHub**: `github.com/api/` instead of `api.github.com`
- **Stripe**: `stripe.com/api/` instead of `api.stripe.com`
- **Vercel**: `vercel.com/api/` instead of `api.vercel.com`
- **Netlify**: `netlify.com/api/` instead of `api.netlify.com`

### **Benefits Observed**
- **Simplified Architecture**: Single domain, single certificate
- **Better Performance**: Reduced DNS lookups
- **Easier Maintenance**: Single point of configuration
- **Improved Security**: Same-origin policy benefits

## 🔍 Migration Path

### **Phase 1: Current State (IP-based)**
```
Frontend: http://192.168.80.246/
API: http://192.168.80.246:3000/api/
```

### **Phase 2: Domain with Path-Based Routing**
```
Frontend: https://eventmanager.com/
API: https://eventmanager.com/api/
```

### **Phase 3: CDN Integration**
```
Frontend: https://cdn.eventmanager.com/
API: https://eventmanager.com/api/
```

## ✅ Implementation Benefits Summary

### **Immediate Benefits**
- ✅ **Simplified Configuration**: No hardcoded URLs
- ✅ **Environment Portability**: Same build works everywhere
- ✅ **CORS Elimination**: Same-origin requests
- ✅ **Protocol Detection**: Automatic HTTP/HTTPS handling

### **Future Benefits**
- ✅ **Subdomain Elimination**: Single domain architecture
- ✅ **Certificate Simplification**: One certificate covers all
- ✅ **Load Balancing**: Path-based routing
- ✅ **CDN Ready**: Single origin deployment
- ✅ **Scalability**: Easier horizontal scaling

## Ready for Path-Based Architecture ✅
The relative URL implementation provides the foundation for a modern, scalable, path-based architecture that eliminates subdomain complexity while maintaining full functionality.

