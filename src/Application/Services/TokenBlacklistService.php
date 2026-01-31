<?php

declare(strict_types=1);

namespace LogicPanel\Application\Services;

use Redis;

class TokenBlacklistService
{
    private Redis $redis;
    private bool $connected = false;

    public function __construct()
    {
        try {
            $this->redis = new Redis();
            // Connect to Redis container
            $this->connected = $this->redis->connect('logicpanel_redis', 6379, 1.0);
        } catch (\Exception $e) {
            $this->connected = false;
        }
    }

    /**
     * Add a token to the blacklist
     * @param string $token The JWT token string
     * @param int $expiresIn Seconds until the token would naturally expire
     */
    public function blacklist(string $token, int $expiresIn): void
    {
        if (!$this->connected || $expiresIn <= 0) {
            return;
        }

        // We store the hash of the token for privacy and performance
        $tokenHash = hash('sha256', $token);

        // Add to Redis with a TTL matching the token's remaining life
        $this->redis->setex("blacklist:{$tokenHash}", $expiresIn, '1');
    }

    /**
     * Check if a token is in the blacklist
     */
    public function isBlacklisted(string $token): bool
    {
        if (!$this->connected) {
            return false;
        }

        $tokenHash = hash('sha256', $token);
        return (bool) $this->redis->exists("blacklist:{$tokenHash}");
    }
}
