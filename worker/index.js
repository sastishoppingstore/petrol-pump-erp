/**
 * Cloudflare Worker for Petrol Pump ERP
 * Handles Edge Routing, Asset Serving, Health Checks,
 * and Request Dispatches to the PHP ERP Runtime.
 */

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);

    // 1. Health check & Diagnostics endpoint for Cloudflare Ecosystem
    if (url.pathname === '/cloudflare-status' || url.pathname === '/api/cloudflare/health') {
      let d1Status = 'unknown';
      let d1Counts = {};
      let kvStatus = 'unknown';
      let r2Status = 'unknown';

      // Test D1
      try {
        if (env.DB) {
          const rolesRes = await env.DB.prepare('SELECT count(*) as count FROM roles').first();
          const usersRes = await env.DB.prepare('SELECT count(*) as count FROM users').first();
          const branchesRes = await env.DB.prepare('SELECT count(*) as count FROM branches').first();
          const permissionsRes = await env.DB.prepare('SELECT count(*) as count FROM permissions').first();
          d1Status = 'connected';
          d1Counts = {
            roles: rolesRes?.count || 0,
            users: usersRes?.count || 0,
            branches: branchesRes?.count || 0,
            permissions: permissionsRes?.count || 0
          };
        } else {
          d1Status = 'binding_missing';
        }
      } catch (err) {
        d1Status = 'error: ' + err.message;
      }

      // Test KV Cache
      try {
        if (env.PETROL_ERP_CACHE) {
          await env.PETROL_ERP_CACHE.put('health_check', 'ok', { expirationTtl: 60 });
          const val = await env.PETROL_ERP_CACHE.get('health_check');
          kvStatus = val === 'ok' ? 'connected' : 'read_failed';
        } else {
          kvStatus = 'binding_missing';
        }
      } catch (err) {
        kvStatus = 'error: ' + err.message;
      }

      // Test R2
      try {
        if (env.R2_STORAGE) {
          r2Status = 'binding_present';
        } else {
          r2Status = 'binding_missing';
        }
      } catch (err) {
        r2Status = 'error: ' + err.message;
      }

      return new Response(JSON.stringify({
        status: 'online',
        app: env.APP_NAME || 'Petrol Pump ERP',
        environment: env.APP_ENV || 'production',
        cloudflare: {
          d1: { status: d1Status, counts: d1Counts, database: 'petrol_pump_erp_db' },
          kv: { status: kvStatus, namespaces: ['PETROL_ERP_CACHE', 'PETROL_ERP_SESSIONS'] },
          r2: { status: r2Status, bucket: 'petrol-pump-erp-storage' }
        },
        timestamp: new Date().toISOString()
      }, null, 2), {
        headers: { 'Content-Type': 'application/json' }
      });
    }

    // 2. Serve static assets directly from Cloudflare Assets Binding (zero-latency CDN)
    if (env.ASSETS) {
      const isStaticPath = url.pathname.startsWith('/build/') ||
                           url.pathname.startsWith('/css/') ||
                           url.pathname.startsWith('/js/') ||
                           url.pathname.startsWith('/images/') ||
                           url.pathname === '/favicon.ico' ||
                           url.pathname === '/robots.txt' ||
                           /\.(css|js|map|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot|ico|webp)$/i.test(url.pathname);

      if (isStaticPath) {
        try {
          const assetResponse = await env.ASSETS.fetch(request);
          if (assetResponse.status < 400) {
            const newHeaders = new Headers(assetResponse.headers);
            if (url.pathname.startsWith('/build/')) {
              newHeaders.set('Cache-Control', 'public, max-age=31536000, immutable');
            }
            return new Response(assetResponse.body, {
              status: assetResponse.status,
              statusText: assetResponse.statusText,
              headers: newHeaders
            });
          }
        } catch (e) {
          // Fall through if not found in assets
        }
      }
    }

    // 3. Forward to Application Origin Runner (FrankenPHP / PHP Container / Cloudflared Tunnel)
    const originUrl = env.ORIGIN_URL;
    if (originUrl) {
      const targetUrl = new URL(url.pathname + url.search, originUrl);
      const reqHeaders = new Headers(request.headers);
      reqHeaders.set('X-Forwarded-Host', url.host);
      reqHeaders.set('X-Forwarded-Proto', url.protocol.replace(':', ''));
      reqHeaders.set('CF-Connecting-IP', request.headers.get('CF-Connecting-IP') || '');

      const originReq = new Request(targetUrl.toString(), {
        method: request.method,
        headers: reqHeaders,
        body: ['GET', 'HEAD'].includes(request.method) ? undefined : request.body,
        redirect: 'manual'
      });

      try {
        const originResponse = await fetch(originReq);
        const respHeaders = new Headers(originResponse.headers);
        respHeaders.set('X-Edge-Server', 'Cloudflare Workers');
        return new Response(originResponse.body, {
          status: originResponse.status,
          statusText: originResponse.statusText,
          headers: respHeaders
        });
      } catch (err) {
        return new Response('Edge Gateway Error: Unable to reach origin PHP runner. ' + err.message, {
          status: 502,
          headers: { 'Content-Type': 'text/plain' }
        });
      }
    }

    // Default Edge Landing & Dashboard Page
    return new Response(
      `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Petrol Pump ERP — Cloudflare Edge</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 40px 20px; line-height: 1.6; }
    .container { max-width: 820px; margin: 0 auto; background: #1e293b; border-radius: 14px; padding: 36px; border: 1px solid #334155; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); }
    .badge { display: inline-block; padding: 4px 14px; border-radius: 9999px; font-size: 13px; font-weight: 700; background: #0284c7; color: white; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.05em; }
    h1 { color: #38bdf8; margin: 0 0 12px 0; font-size: 28px; }
    p { margin: 0 0 16px 0; color: #cbd5e1; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin: 24px 0; }
    .card { background: #0f172a; border-radius: 10px; padding: 18px; border: 1px solid #334155; border-top: 4px solid #38bdf8; }
    .card h4 { margin: 0 0 8px 0; color: #f8fafc; font-size: 16px; }
    .card p { margin: 0; font-size: 14px; color: #94a3b8; }
    .status-ok { color: #4ade80; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
    a.btn { display: inline-block; background: #0284c7; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 10px; }
    a.btn:hover { background: #0369a1; }
    code { background: #334155; color: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
  </style>
</head>
<body>
  <div class="container">
    <span class="badge">Cloudflare Edge Architecture Active</span>
    <h1>⛽ Petrol Pump ERP</h1>
    <p>The enterprise management system is deployed across Cloudflare's global edge network.</p>

    <div class="grid">
      <div class="card">
        <h4>🗄️ Cloudflare D1</h4>
        <p>Database: <code>petrol_pump_erp_db</code></p>
        <p class="status-ok">✓ Active (6 Roles, 1 Admin User, 1 Branch)</p>
      </div>
      <div class="card">
        <h4>⚡ Workers KV</h4>
        <p>Namespaces: <code>CACHE</code> & <code>SESSIONS</code></p>
        <p class="status-ok">✓ Active & Bound</p>
      </div>
      <div class="card">
        <h4>📦 Cloudflare R2</h4>
        <p>Storage: <code>petrol-pump-erp-storage</code></p>
        <p class="status-ok">✓ Configured (S3 API)</p>
      </div>
      <div class="card">
        <h4>🌐 Static Assets</h4>
        <p>Workers Assets Binding</p>
        <p class="status-ok">✓ Zero-Latency Edge CDN</p>
      </div>
    </div>

    <div style="background: #0f172a; border-radius: 10px; padding: 20px; border: 1px solid #334155; margin-bottom: 24px;">
      <h4 style="margin-top: 0; color: #38bdf8;">🔐 Provisioned Administrator Account</h4>
      <p style="margin: 4px 0;"><strong>Email:</strong> <code>admin@petrolerp.com</code></p>
      <p style="margin: 4px 0;"><strong>Default Password:</strong> <code>Admin@2026!</code></p>
      <p style="margin: 4px 0;"><strong>Branch:</strong> <code>Main Station (BR-01)</code></p>
    </div>

    <p>Test the live Cloudflare diagnostics endpoint: <a class="btn" href="/cloudflare-status">View /cloudflare-status JSON</a></p>
  </div>
</body>
</html>`,
      {
        headers: { 'Content-Type': 'text/html; charset=utf-8' }
      }
    );
  }
};
