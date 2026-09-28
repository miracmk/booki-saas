import http from 'node:http';
import crypto from 'node:crypto';

import {
    startSession,
    sessionStatus,
    logoutSession,
    sendMessage,
    resumeExistingSessions,
    sessionSummary,
    resolveSessionTenant,
} from './bridge.js';

/**
 * ki-wa-bridge HTTP server (Faz 3.5 part 2).
 *
 * Implements docs/whatsapp-bridge-contract.md from the PHP repository:
 *
 *   GET  /health
 *   POST /v1/session/:tenant/start
 *   GET  /v1/session/:tenant/status
 *   POST /v1/session/:tenant/logout
 *   POST /v1/send
 *
 * Every route except nothing is protected by the X-Bridge-Secret header
 * (constant-time). The accepted secrets are the platform default from
 * WA_BRIDGE_SECRET plus any per-tenant overrides in WA_BRIDGE_TENANT_SECRETS
 * (JSON object keyed by tenant). The secret a tenant authenticated with is
 * re-used when signing inbound webhook forwards to the app.
 */
const PORT = Number(process.env.PORT || 3000);
const VERSION = '1.0.0';

function resolveSecrets() {
    const global = process.env.WA_BRIDGE_SECRET || '';
    let tenant = {};

    try {
        tenant = JSON.parse(process.env.WA_BRIDGE_TENANT_SECRETS || '{}');
    } catch (_) {
        tenant = {};
    }

    return { global, tenant };
}

function safeEqual(a, b) {
    if (typeof a !== 'string' || typeof b !== 'string') {
        return false;
    }

    const aBuf = Buffer.from(a);
    const bBuf = Buffer.from(b);

    if (aBuf.length !== bBuf.length) {
        return false;
    }

    return crypto.timingSafeEqual(aBuf, bBuf);
}

/**
 * Resolve and verify the bridge secret from a request. Returns the matched
 * secret (sent back on inbound forwards) or null when unauthorized.
 */
function authenticate(req, tenant) {
    const received = req.headers['x-bridge-secret'];

    if (typeof received !== 'string' || received.length === 0) {
        return null;
    }

    const { global, tenant: tenantMap } = resolveSecrets();
    const resolved = resolveSessionTenant(tenant);
    const candidates = [global, tenant ? tenantMap[tenant] : null, resolved ? tenantMap[resolved] : null].filter(Boolean);

    for (const candidate of candidates) {
        if (safeEqual(received, candidate)) {
            return candidate;
        }
    }

    return null;
}

function sendJson(res, status, body) {
    const payload = JSON.stringify(body);

    res.writeHead(status, {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(payload),
        'Cache-Control': 'no-store',
    });

    res.end(payload);
}

function unauthorized(res) {
    sendJson(res, 401, { success: false, error: 'unauthorized' });
}

function readJson(req) {
    return new Promise((resolve) => {
        let raw = '';

        req.on('data', (chunk) => {
            raw += chunk;

            if (raw.length > 1_048_576) {
                req.destroy();

                return;
            }
        });

        req.on('end', () => {
            try {
                resolve(raw.length > 0 ? JSON.parse(raw) : {});
            } catch (_) {
                resolve(null);
            }
        });

        req.on('error', () => resolve(null));
    });
}

function tenantFromPath(pathname) {
    const match = pathname.match(/^\/v1\/session\/([^/]+)\//);
    if (!match) return null;
    const tenant = decodeURIComponent(match[1]);
    if (!/^[a-zA-Z0-9_-]+$/.test(tenant)) {
        return null;
    }
    return tenant;
}

async function handleHealth(req, res) {
    const secret = authenticate(req, null);

    if (!secret) {
        return unauthorized(res);
    }

    sendJson(res, 200, {
        status: 'ok',
        version: VERSION,
        sessions: sessionSummary(),
    });
}

async function handleStart(req, res, tenant) {
    const secret = authenticate(req, tenant);

    if (!secret) {
        return unauthorized(res);
    }

    const body = await readJson(req);

    if (body === null) {
        return sendJson(res, 400, { success: false, error: 'invalid_json' });
    }

    const webhookUrl = typeof body.webhookUrl === 'string' && body.webhookUrl.length > 0
        ? body.webhookUrl
        : null;

    try {
        const result = await startSession(tenant, webhookUrl, secret);

        return sendJson(res, 200, result);
    } catch (err) {
        return sendJson(res, 500, { status: 'error', error: String(err.message || 'start_failed').slice(0, 200) });
    }
}

async function handleStatus(req, res, tenant) {
    const secret = authenticate(req, tenant);

    if (!secret) {
        return unauthorized(res);
    }

    try {
        return sendJson(res, 200, await sessionStatus(tenant));
    } catch (err) {
        return sendJson(res, 500, { status: 'error', error: String(err.message || 'status_failed').slice(0, 200) });
    }
}

async function handleLogout(req, res, tenant) {
    const secret = authenticate(req, tenant);

    if (!secret) {
        return unauthorized(res);
    }

    try {
        return sendJson(res, 200, await logoutSession(tenant));
    } catch (err) {
        return sendJson(res, 500, { status: 'error', error: String(err.message || 'logout_failed').slice(0, 200) });
    }
}

async function handleSend(req, res) {
    const body = await readJson(req);

    if (body === null) {
        return sendJson(res, 400, { success: false, error: 'invalid_json' });
    }

    const tenant = typeof body.tenant === 'string' ? body.tenant : '';
    const secret = authenticate(req, tenant);

    if (!secret) {
        return unauthorized(res);
    }

    const to = typeof body.to === 'string' ? body.to : '';
    const text = typeof body.text === 'string' ? body.text : '';

    try {
        return sendJson(res, 200, await sendMessage(tenant, to, text));
    } catch (err) {
        return sendJson(res, 200, { success: false, error: String(err.message || 'send_failed').slice(0, 200) });
    }
}

const server = http.createServer(async (req, res) => {
    try {
        const url = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`);
        const { pathname } = url;

        if (req.method === 'GET' && pathname === '/health') {
            return handleHealth(req, res);
        }

        if (req.method === 'POST' && pathname.startsWith('/v1/session/') && pathname.endsWith('/start')) {
            return handleStart(req, res, tenantFromPath(pathname));
        }

        if (req.method === 'GET' && pathname.startsWith('/v1/session/') && pathname.endsWith('/status')) {
            return handleStatus(req, res, tenantFromPath(pathname));
        }

        if (req.method === 'POST' && pathname.startsWith('/v1/session/') && pathname.endsWith('/logout')) {
            return handleLogout(req, res, tenantFromPath(pathname));
        }

        if (req.method === 'POST' && pathname === '/v1/send') {
            return handleSend(req, res);
        }

        return sendJson(res, 404, { success: false, error: 'not_found' });
    } catch (err) {
        try {
            sendJson(res, 500, { success: false, error: 'internal_error', detail: String(err.message || '').slice(0, 200) });
        } catch (_) {
            res.end();
        }
    }
});

await resumeExistingSessions();

server.listen(PORT, () => {
    console.log(`ki-wa-bridge v${VERSION} listening on :${PORT}`);
});