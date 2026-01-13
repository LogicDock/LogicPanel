<style>
    .lp-client-area {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    .lp-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 12px;
        margin-bottom: 20px;
        color: white;
    }
    
    .lp-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }
    
    .lp-header .lp-status {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        background: rgba(255,255,255,0.1);
        border-radius: 20px;
        font-size: 0.875rem;
    }
    
    .lp-header .lp-status.running::before {
        content: '';
        width: 8px;
        height: 8px;
        background: #4ade80;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    
    .lp-header .lp-status.stopped::before {
        content: '';
        width: 8px;
        height: 8px;
        background: #f87171;
        border-radius: 50%;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .lp-login-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: linear-gradient(135deg, #3C873A 0%, #2d6a2e 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }
    
    .lp-login-btn:hover {
        background: linear-gradient(135deg, #2d6a2e 0%, #1e4d1e 100%);
        color: white;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(60, 135, 58, 0.3);
    }
    
    .lp-login-btn svg {
        width: 20px;
        height: 20px;
    }
    
    .lp-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .lp-info-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px;
    }
    
    .lp-info-card .label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    
    .lp-info-card .value {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
    }
    
    .lp-info-card .value code {
        background: #e2e8f0;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: 'Monaco', 'Consolas', monospace;
        font-size: 0.875rem;
    }
    
    .lp-quick-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }
    
    .lp-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        color: #475569;
        font-size: 0.875rem;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    
    .lp-action-btn:hover {
        background: #f1f5f9;
        color: #1e293b;
        text-decoration: none;
    }
    
    .lp-features {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        margin-top: 15px;
    }
    
    .lp-feature {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.875rem;
        color: #64748b;
    }
    
    .lp-feature.enabled {
        color: #22c55e;
    }
    
    .lp-feature svg {
        width: 16px;
        height: 16px;
    }
</style>

<div class="lp-client-area">
    <div class="lp-header">
        <div>
            <h2>Node.js Hosting</h2>
            <p style="margin: 5px 0 0; opacity: 0.8; font-size: 0.9rem;">{$domain}</p>
        </div>
        <div class="lp-status {if $serviceInfo.status eq 'running'}running{else}stopped{/if}">
            {if $serviceInfo.status eq 'running'}Running{else}{$serviceInfo.status|default:'Pending'}{/if}
        </div>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{$ssoUrl}" target="_blank" class="lp-login-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                <polyline points="10 17 15 12 10 7"/>
                <line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Login to Control Panel
        </a>
    </div>
    
    <div class="lp-info-grid">
        <div class="lp-info-card">
            <div class="label">Domain</div>
            <div class="value"><code>{$domain}</code></div>
        </div>
        
        <div class="lp-info-card">
            <div class="label">Package</div>
            <div class="value">{$package|ucfirst}</div>
        </div>
        
        <div class="lp-info-card">
            <div class="label">Node.js Version</div>
            <div class="value">v{$nodeVersion}</div>
        </div>
        
        <div class="lp-info-card">
            <div class="label">Status</div>
            <div class="value">
                {if $serviceInfo.status eq 'running'}
                    <span style="color: #22c55e;">● Running</span>
                {elseif $serviceInfo.status eq 'stopped'}
                    <span style="color: #f59e0b;">● Stopped</span>
                {elseif $serviceInfo.status eq 'suspended'}
                    <span style="color: #ef4444;">● Suspended</span>
                {else}
                    <span style="color: #94a3b8;">● {$serviceInfo.status|default:'Pending'}</span>
                {/if}
            </div>
        </div>
    </div>
    
    <div class="lp-features">
        <div class="lp-feature enabled">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            File Manager
        </div>
        <div class="lp-feature enabled">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            Terminal Access
        </div>
        <div class="lp-feature enabled">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            Git Deploy
        </div>
        <div class="lp-feature enabled">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            Database Support
        </div>
    </div>
    
    <div class="lp-quick-actions">
        <a href="{$ssoUrl}" target="_blank" class="lp-action-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <line x1="3" y1="9" x2="21" y2="9"/>
                <line x1="9" y1="21" x2="9" y2="9"/>
            </svg>
            Dashboard
        </a>
        <a href="{$ssoUrl}" target="_blank" class="lp-action-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            File Manager
        </a>
        <a href="{$ssoUrl}" target="_blank" class="lp-action-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="4 17 10 11 4 5"/>
                <line x1="12" y1="19" x2="20" y2="19"/>
            </svg>
            Terminal
        </a>
        <a href="{$ssoUrl}" target="_blank" class="lp-action-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <ellipse cx="12" cy="5" rx="9" ry="3"/>
                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
            </svg>
            Database
        </a>
    </div>
</div>
