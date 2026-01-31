# LogicPanel Security Audit - Quick Reference Guide

## 🔴 Critical Security Issues (Fix Immediately)

### 1. CORS Configuration - CRITICAL VULNERABILITY

**Current Code (VULNERABLE):**
```php
// src/Application/Middleware/CorsMiddleware.php
->withHeader('Access-Control-Allow-Origin', '*')
->withHeader('Access-Control-Allow-Credentials', 'true')
```

**Problem:** Allows ANY website to make authenticated requests to your API, enabling credential theft.

**Fix:**
```php
public function process(
    ServerRequestInterface $request,
    RequestHandlerInterface $handler
): ResponseInterface {
    $response = $handler->handle($request);
    
    // Whitelist allowed origins
    $allowedOrigins = [
        'https://panel.yourdomain.cloud',
        'https://admin.yourdomain.cloud'
    ];
    
    $origin = $request->getHeaderLine('Origin');
    
    if (in_array($origin, $allowedOrigins)) {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-API-Key')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }
    
    return $response;
}
```

---

### 2. Database Password Encryption - CRITICAL

**Current Code (INSECURE):**
```php
// src/Application/Controllers/DatabaseController.php
private function encryptPassword(string $password): string
{
    // Simple base64 encoding for now
    // TODO: Implement proper encryption with libsodium
    return base64_encode($password);
}
```

**Problem:** Base64 is NOT encryption! Anyone can decode database passwords.

**Fix:**
```php
private function encryptPassword(string $password): string
{
    // Get encryption key from environment
    $key = getenv('ENCRYPTION_KEY');
    
    if (empty($key)) {
        throw new \RuntimeException('ENCRYPTION_KEY not set');
    }
    
    // Decode the key (should be base64 encoded in .env)
    $key = base64_decode($key);
    
    if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new \RuntimeException('Invalid encryption key length');
    }
    
    // Generate nonce
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    
    // Encrypt
    $ciphertext = sodium_crypto_secretbox($password, $nonce, $key);
    
    // Return nonce + ciphertext, base64 encoded
    return base64_encode($nonce . $ciphertext);
}

private function decryptPassword(string $encrypted): string
{
    $key = base64_decode(getenv('ENCRYPTION_KEY'));
    $decoded = base64_decode($encrypted);
    
    // Extract nonce and ciphertext
    $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    
    // Decrypt
    $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
    
    if ($plaintext === false) {
        throw new \RuntimeException('Decryption failed');
    }
    
    return $plaintext;
}
```

**Generate encryption key:**
```bash
# Run once during installation
php -r "echo base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)) . PHP_EOL;"
# Add to .env: ENCRYPTION_KEY=<generated_key>
```

---

### 3. JWT Token Revocation

**Current Issue:** No way to invalidate compromised tokens.

**Fix - Add Redis-based Blacklist:**
```php
// Create new service: src/Application/Services/TokenBlacklistService.php
class TokenBlacklistService
{
    private $redis;
    
    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect('redis', 6379);
    }
    
    public function blacklist(string $token, int $expiresIn): void
    {
        // Store token hash in Redis with expiry
        $hash = hash('sha256', $token);
        $this->redis->setex("blacklist:{$hash}", $expiresIn, '1');
    }
    
    public function isBlacklisted(string $token): bool
    {
        $hash = hash('sha256', $token);
        return $this->redis->exists("blacklist:{$hash}");
    }
}

// Update AuthMiddleware.php
public function process(
    ServerRequestInterface $request,
    RequestHandlerInterface $handler
): ResponseInterface {
    // ... existing token extraction code ...
    
    // Check blacklist
    if ($this->blacklistService->isBlacklisted($token)) {
        return $this->unauthorized('Token has been revoked');
    }
    
    // ... rest of verification ...
}

// Add logout endpoint that blacklists token
public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
{
    $authHeader = $request->getHeaderLine('Authorization');
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        $decoded = $this->jwtService->verifyToken($token);
        $expiresIn = $decoded->exp - time();
        
        if ($expiresIn > 0) {
            $this->blacklistService->blacklist($token, $expiresIn);
        }
    }
    
    return $this->jsonResponse($response, ['message' => 'Logged out successfully']);
}
```

---

### 4. Docker Socket Security

**Current Risk:** App container has full Docker access via `/var/run/docker.sock`

**Recommendations:**
1. Use Docker Socket Proxy (tecnativa/docker-socket-proxy)
2. Or implement least-privilege access

**docker-compose.yml fix:**
```yaml
services:
  docker-proxy:
    image: tecnativa/docker-socket-proxy
    container_name: logicpanel_docker_proxy
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
    environment:
      - CONTAINERS=1
      - POST=1
      - IMAGES=1
      - NETWORKS=1
      - VOLUMES=1
      - EXEC=1
      - SERVICES=0
      - SWARM=0
      - NODES=0
      - SECRETS=0
      - CONFIGS=0
    networks:
      - logicpanel_network

  app:
    # Remove direct socket mount
    # volumes:
    #   - /var/run/docker.sock:/var/run/docker.sock
    environment:
      - DOCKER_HOST=tcp://docker-proxy:2375
    depends_on:
      - docker-proxy
```

---

## 🟠 High Priority Security Issues

### 5. Password Complexity Requirements

**Add to AuthController::register():**
```php
public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
{
    $data = $request->getParsedBody();
    $password = $data['password'] ?? '';
    
    // Password validation
    if (strlen($password) < 12) {
        return $this->jsonResponse($response, [
            'error' => 'Password must be at least 12 characters'
        ], 400);
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return $this->jsonResponse($response, [
            'error' => 'Password must contain at least one uppercase letter'
        ], 400);
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return $this->jsonResponse($response, [
            'error' => 'Password must contain at least one lowercase letter'
        ], 400);
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return $this->jsonResponse($response, [
            'error' => 'Password must contain at least one number'
        ], 400);
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return $this->jsonResponse($response, [
            'error' => 'Password must contain at least one special character'
        ], 400);
    }
    
    // Continue with registration...
}
```

---

### 6. Account Lockout After Failed Logins

**Create migration:**
```sql
ALTER TABLE users 
ADD COLUMN failed_login_attempts INT DEFAULT 0,
ADD COLUMN locked_until TIMESTAMP NULL;
```

**Update AuthController::login():**
```php
public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
{
    $data = $request->getParsedBody();
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    
    $user = User::where('username', $username)->first();
    
    if (!$user) {
        return $this->jsonResponse($response, [
            'error' => 'Invalid credentials'
        ], 401);
    }
    
    // Check if account is locked
    if ($user->locked_until && $user->locked_until > date('Y-m-d H:i:s')) {
        return $this->jsonResponse($response, [
            'error' => 'Account locked due to too many failed attempts. Try again later.',
            'locked_until' => $user->locked_until
        ], 403);
    }
    
    if (!$user->verifyPassword($password)) {
        // Increment failed attempts
        $user->failed_login_attempts++;
        
        // Lock account after 5 failed attempts for 30 minutes
        if ($user->failed_login_attempts >= 5) {
            $user->locked_until = date('Y-m-d H:i:s', time() + 1800); // 30 min
        }
        
        $user->save();
        
        return $this->jsonResponse($response, [
            'error' => 'Invalid credentials'
        ], 401);
    }
    
    // Reset failed attempts on successful login
    $user->failed_login_attempts = 0;
    $user->locked_until = null;
    $user->save();
    
    // Continue with normal login...
}
```

---

### 7. Restrict Database Ports to Localhost

**docker-compose.yml:**
```yaml
services:
  mysql:
    ports:
      - "127.0.0.1:3306:3306"  # Bind to localhost only
  
  postgres:
    ports:
      - "127.0.0.1:5432:5432"
  
  mongo:
    ports:
      - "127.0.0.1:27017:27017"
```

---

## ✅ RESTful API Improvements

### 8. Add API Versioning

**New routes structure:**
```php
// src/routes_user.php
return function (App $app) {
    $app->group('/v1', function (RouteCollectorProxy $group) {
        
        // Health check
        $group->get('/health', ...);
        
        // Public routes
        $group->group('/auth', function (RouteCollectorProxy $group) {
            $group->post('/login', [AuthController::class, 'login']);
            $group->post('/register', [AuthController::class, 'register']);
        });
        
        // Protected routes
        $group->group('', function (RouteCollectorProxy $group) {
            // All existing routes...
        })->add(AuthMiddleware::class)
          ->add(RateLimitMiddleware::class);
    });
};
```

---

### 9. Add Pagination to List Endpoints

**Update ServiceController::index():**
```php
public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
{
    $userId = $request->getAttribute('userId');
    $queryParams = $request->getQueryParams();
    
    // Pagination params
    $page = (int) ($queryParams['page'] ?? 1);
    $limit = min((int) ($queryParams['limit'] ?? 20), 100); // Max 100
    $offset = ($page - 1) * $limit;
    
    // Filtering
    $type = $queryParams['type'] ?? null;
    $status = $queryParams['status'] ?? null;
    
    // Sorting
    $sortBy = $queryParams['sort'] ?? 'created_at';
    $sortOrder = strtolower($queryParams['order'] ?? 'desc');
    
    // Validate sort order
    if (!in_array($sortOrder, ['asc', 'desc'])) {
        $sortOrder = 'desc';
    }
    
    // Build query
    $query = Service::where('user_id', $userId);
    
    if ($type) {
        $query->where('type', $type);
    }
    
    if ($status) {
        $query->where('status', $status);
    }
    
    // Get total count
    $total = $query->count();
    
    // Apply sorting and pagination
    $services = $query
        ->orderBy($sortBy, $sortOrder)
        ->limit($limit)
        ->offset($offset)
        ->get();
    
    return $this->jsonResponse($response, [
        'data' => $services->map(function ($service) {
            // ... mapping code ...
        }),
        'meta' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ],
        'links' => [
            'self' => "/v1/services?page={$page}&limit={$limit}",
            'first' => "/v1/services?page=1&limit={$limit}",
            'last' => "/v1/services?page=" . ceil($total / $limit) . "&limit={$limit}",
            'prev' => $page > 1 ? "/v1/services?page=" . ($page - 1) . "&limit={$limit}" : null,
            'next' => $page < ceil($total / $limit) ? "/v1/services?page=" . ($page + 1) . "&limit={$limit}" : null,
        ]
    ]);
}
```

---

### 10. Better RESTful Service Actions

**Instead of:**
```
POST /services/{id}/start
POST /services/{id}/stop
POST /services/{id}/restart
```

**Use:**
```
PATCH /services/{id}
Body: { "status": "running" }  // start
Body: { "status": "stopped" }  // stop
Body: { "action": "restart" }  // restart
```

**Implementation:**
```php
public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
{
    $serviceId = (int) $args['id'];
    $userId = $request->getAttribute('userId');
    $data = $request->getParsedBody();
    
    $service = Service::where('id', $serviceId)
        ->where('user_id', $userId)
        ->first();
    
    if (!$service) {
        return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
    }
    
    // Handle status changes
    if (isset($data['status'])) {
        $status = $data['status'];
        
        switch ($status) {
            case 'running':
                $this->dockerService->startContainer($service->container_id);
                $service->status = 'running';
                break;
            
            case 'stopped':
                $this->dockerService->stopContainer($service->container_id);
                $service->status = 'stopped';
                break;
            
            default:
                return $this->jsonResponse($response, ['error' => 'Invalid status'], 400);
        }
    }
    
    // Handle actions
    if (isset($data['action'])) {
        $action = $data['action'];
        
        switch ($action) {
            case 'restart':
                $this->dockerService->restartContainer($service->container_id);
                break;
            
            default:
                return $this->jsonResponse($response, ['error' => 'Invalid action'], 400);
        }
    }
    
    // Handle other updates (name, etc.)
    if (isset($data['name'])) {
        $service->name = $data['name'];
    }
    
    $service->save();
    
    return $this->jsonResponse($response, [
        'message' => 'Service updated successfully',
        'service' => [
            'id' => $service->id,
            'name' => $service->name,
            'status' => $service->status,
            // ...
        ]
    ]);
}
```

---

## 📊 Security Score Summary

| Category | Score | Priority |
|----------|-------|----------|
| **RESTful API Design** | 7.5/10 | Medium |
| **Authentication** | 6/10 | High |
| **Authorization** | 8/10 | Low |
| **Input Validation** | 7/10 | High |
| **CORS Configuration** | 2/10 | **CRITICAL** |
| **Password Security** | 8/10 | Low |
| **Database Security** | 3/10 | **CRITICAL** |
| **Rate Limiting** | 7/10 | Low |
| **Container Security** | 5/10 | High |
| **Code Modularity** | 8/10 | Low |

**Overall Security Score: 6.5/10**

---

## 🎯 Implementation Priority

1. **Week 1 - Critical Fixes:**
   - ✅ Fix CORS configuration
   - ✅ Implement proper database password encryption
   - ✅ Add JWT token revocation
   - ✅ Implement Docker socket proxy

2. **Week 2 - High Priority:**
   - ✅ Add password complexity requirements
   - ✅ Implement account lockout
   - ✅ Restrict database ports to localhost
   - ✅ Add API versioning

3. **Week 3-4 - Medium Priority:**
   - ✅ Redis-based rate limiting
   - ✅ Add pagination to all endpoints
   - ✅ Improve RESTful action endpoints
   - ✅ Add input length validation

4. **Month 2 - Enhancements:**
   - ✅ OpenAPI/Swagger documentation
   - ✅ File upload virus scanning
   - ✅ Client SDK development
   - ✅ Webhook system

---

## 📖 Additional Resources

- **OWASP Top 10:** https://owasp.org/www-project-top-ten/
- **REST API Best Practices:** https://restfulapi.net/
- **JWT Security:** https://jwt.io/introduction
- **Docker Security:** https://docs.docker.com/engine/security/
- **PHP Security:** https://www.php.net/manual/en/security.php

---

**Report Generated:** February 1, 2026  
**Audited Version:** LogicPanel v1.5.0 (Beta)  
**Auditor:** Claude AI Security Analyst
