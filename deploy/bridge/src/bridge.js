import makeWASocket, {
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
} from '@whiskeysockets/baileys';

import pino from 'pino';
import { mkdirSync, existsSync, readdirSync, rmSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import { join } from 'node:path';
import QRCode from 'qrcode';

/**
 * ki-wa-bridge session manager (Faz 3.5 part 2).
 *
 * One isolated Baileys session per tenant key (subdomain or 'default'), living
 * in `<SESSIONS_DIR>/<tenant>/`. Sockets are created lazily on demand and
 * resumed at boot for already-paired sessions so a container restart keeps an
 * existing pairing alive. The bridge never stores message content; it only
 * keeps credential/auth state, the pending QR (while connecting) and the
 * app-supplied inbound webhook URL per tenant.
 *
 * @see https://github.com/WhiskeySockets/Baileys
 */

const SESSIONS_DIR = process.env.SESSIONS_DIR || '/app/sessions';

/**
 * @typedef {Object} SessionEntry
 * @property {string} tenant
 * @property {string} dir
 * @property {object|null} sock
 * @property {string} status connecting|connected|disconnected|error
 * @property {string|null} qr
 * @property {string|null} name
 * @property {string|null} error
 * @property {string|null} webhookUrl
 * @property {string|null} webhookSecret
 * @property {Promise|null} creating
 * @property {NodeJS.Timeout|null} reconnectTimer
 * @property {number} reconnectAttempts
 */

/** @type {Map<string, SessionEntry>} */
const sessions = new Map();

/** Backoff schedule for auto-reconnect after a transient connection close. */
const RECONNECT_DELAYS_MS = [2000, 5000, 10000];

/** @type {import('pino').Logger} */
const log = pino({ level: process.env.LOG_LEVEL || 'info' });

function sessionDir(tenant) {
    return join(SESSIONS_DIR, tenant);
}

function getEntry(tenant) {
    if (!sessions.has(tenant)) {
        sessions.set(tenant, {
            tenant,
            dir: sessionDir(tenant),
            sock: null,
            status: 'disconnected',
            qr: null,
            name: null,
            error: null,
            webhookUrl: null,
            webhookSecret: null,
            creating: null,
            reconnectTimer: null,
            reconnectAttempts: 0,
        });
    }

    return sessions.get(tenant);
}

/**
 * Extract a human-readable text payload from a Baileys message node, or null
 * when the node carries no text.
 */
function extractText(msg) {
    const m = msg.message || {};
    if (typeof m.conversation === 'string' && m.conversation.length > 0) {
        return m.conversation;
    }

    const ext = m.extendedTextMessage && m.extendedTextMessage.text;
    if (typeof ext === 'string' && ext.length > 0) {
        return ext;
    }

    return null;
}

/**
 * The message "type" the app expects: 'text' for plain text, otherwise the raw
 * proto field name (e.g. 'imageMessage').
 */
function messageType(msg) {
    const keys = Object.keys(msg.message || {});

    if (keys.includes('conversation') || keys.includes('extendedTextMessage')) {
        return 'text';
    }

    return keys[0] || 'unknown';
}

/**
 * Forward an inbound message to the app's webhook endpoint, signed with the
 * same secret the app already authenticated with (its stored bridge secret).
 */
function forwardInbound(entry, payload) {
    if (!entry.webhookUrl) {
        log.warn({ tenant: entry.tenant }, 'inbound message dropped: no webhookUrl configured');

        return;
    }

    const headers = {
        'Content-Type': 'application/json',
    };

    if (entry.webhookSecret) {
        headers['X-Bridge-Secret'] = entry.webhookSecret;
    }

    fetch(entry.webhookUrl, {
        method: 'POST',
        headers,
        body: JSON.stringify({ tenant: entry.tenant, ...payload }),
        signal: AbortSignal.timeout(8000),
    })
        .then((res) => {
            if (!res.ok) {
                log.warn({ tenant: entry.tenant, status: res.status }, 'inbound forward non-200');
            }
        })
        .catch((err) => {
            log.warn({ tenant: entry.tenant, err: err.message }, 'inbound forward failed');
        });
}

function onMessagesUpsert(entry, upsert) {
    for (const msg of upsert.messages || []) {
        if (!msg.message) {
            continue;
        }

        if (msg.key.fromMe) {
            continue;
        }

        if (!msg.key.remoteJid || !msg.key.remoteJid.endsWith('@s.whatsapp.net')) {
            continue;
        }

        const from = msg.key.remoteJid.split('@')[0];
        const type = messageType(msg);
        const text = extractText(msg);
        const body = text ?? `[${type} message]`;

        forwardInbound(entry, {
            from,
            body,
            type,
            message_id: `WA-${msg.key.id || 'unknown'}`,
        });
    }
}

async function createSocket(entry) {
    mkdirSync(entry.dir, { recursive: true });

    const { state, saveCreds } = await useMultiFileAuthState(entry.dir);
    const { version } = await fetchLatestBaileysVersion();

    const sock = makeWASocket({
        version,
        auth: state,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false,
    });

    entry.sock = sock;

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            entry.status = 'connecting';
            entry.qr = qr;
            entry.error = null;
            return;
        }

        if (connection === 'open') {
            entry.status = 'connected';
            entry.qr = null;
            entry.error = null;
            entry.name = sock.user?.name || sock.user?.verifiedName || entry.name || null;
            entry.reconnectAttempts = 0;

            if (entry.reconnectTimer) {
                clearTimeout(entry.reconnectTimer);
                entry.reconnectTimer = null;
            }

            return;
        }

        if (connection === 'close') {
            const code = lastDisconnect?.error?.output?.statusCode;
            const loggedOut = code === DisconnectReason.loggedOut;

            if (loggedOut) {
                entry.status = 'disconnected';
                entry.qr = null;
                entry.error = null;
                entry.name = null;
                closeEntry(entry);
                return;
            }

            // Transient close (e.g. "Stream Errored (restart required)"): the dead
            // socket reference MUST be dropped here - ensureSocket() refuses to
            // create a new socket while entry.sock is set, so keeping it around
            // would wedge the tenant in "error" forever (start() would silently
            // reuse a dead socket and no QR would ever be produced again).
            // Log the reason, drop the reference and let a bounded backoff retry
            // the connection by itself.
            entry.sock = null;
            entry.status = 'error';
            entry.error = lastDisconnect?.error?.message || 'connection_closed';
            entry.qr = null;
            log.warn({ tenant: entry.tenant, code, error: entry.error }, 'connection closed');

            scheduleReconnect(entry);
        }
    });

    sock.ev.on('messages.upsert', (upsert) => onMessagesUpsert(entry, upsert));
}

/**
 * Bounded auto-reconnect after a transient close. After the schedule is
 * exhausted the session stays in "error" until an explicit start() call - which
 * resets the attempts so a human-initiated retry always gets a fresh socket.
 */
function scheduleReconnect(entry) {
    if (entry.reconnectTimer) {
        clearTimeout(entry.reconnectTimer);
        entry.reconnectTimer = null;
    }

    if (entry.reconnectAttempts >= RECONNECT_DELAYS_MS.length) {
        log.warn({ tenant: entry.tenant }, 'auto-reconnect attempts exhausted; waiting for explicit start');

        return;
    }

    const delay = RECONNECT_DELAYS_MS[entry.reconnectAttempts];
    entry.reconnectAttempts += 1;

    entry.reconnectTimer = setTimeout(() => {
        entry.reconnectTimer = null;

        if (entry.sock || entry.status === 'connected' || entry.status === 'disconnected') {
            return;
        }

        ensureSocket(entry).catch((err) => {
            log.warn({ tenant: entry.tenant, err: err.message }, 'auto-reconnect failed');
        });
    }, delay);
}

function closeEntry(entry) {
    if (entry.reconnectTimer) {
        clearTimeout(entry.reconnectTimer);
        entry.reconnectTimer = null;
    }

    entry.reconnectAttempts = 0;

    try {
        if (entry.sock) {
            entry.sock.end(new Error('session closed'));
            entry.sock = null;
        }

        if (existsSync(entry.dir)) {
            rmSync(entry.dir, { recursive: true, force: true });
        }
    } catch (err) {
        log.warn({ tenant: entry.tenant, err: err.message }, 'closeEntry cleanup failed');
    }

    sessions.delete(entry.tenant);
}

/**
 * Kick a socket into life for a tenant. Used by both start() and the boot
 * resume pass; guarded so parallel callers never spawn duplicate sockets.
 */
async function ensureSocket(entry) {
    if (entry.sock && entry.status === 'connected') {
        return { reused: true };
    }

    if (entry.sock) {
        return { reused: true };
    }

    if (entry.creating) {
        await entry.creating;

        return { reused: true };
    }

    entry.creating = createSocket(entry).finally(() => {
        entry.creating = null;
    });

    await entry.creating;

    return { reused: false };
}

/**
 * Start pairing / resume the session for a tenant.
 * Registers the app webhook URL (needed for inbound forwarding) on every call.
 *
 * @returns {Promise<{status: string, name?: string|null}>}
 */
export async function startSession(tenant, webhookUrl, webhookSecret) {
    const entry = getEntry(tenant);

    if (webhookUrl) {
        entry.webhookUrl = webhookUrl;
    }

    if (webhookSecret) {
        entry.webhookSecret = webhookSecret;
    }

    if (entry.status === 'connected') {
        return { status: 'connected', name: entry.name };
    }

    if (entry.status === 'connecting') {
        return { status: 'connecting' };
    }

    // Explicit human retry always gets a fresh attempt budget (and cancels any
    // pending auto-reconnect timer; the entry.creating guard still serializes
    // concurrent socket creation).
    if (entry.status === 'error') {
        entry.reconnectAttempts = 0;

        if (entry.reconnectTimer) {
            clearTimeout(entry.reconnectTimer);
            entry.reconnectTimer = null;
        }
    }

    await ensureSocket(entry);

    return { status: entry.status === 'connected' ? 'connected' : 'connecting', name: entry.name };
}

/**
 * Current session state for the admin panel polling loop.
 */
export async function sessionStatus(tenant) {
    const entry = sessions.get(tenant);

    if (!entry || !entry.sock) {
        return { status: 'disconnected' };
    }

    if (entry.status === 'connected') {
        return { status: 'connected', name: entry.name };
    }

    if (entry.status === 'connecting' && entry.qr) {
        try {
            const qr = await QRCode.toDataURL(entry.qr, { errorCorrectionLevel: 'L', width: 280, margin: 1 });

            return { status: 'connecting', qr };
        } catch (err) {
            log.warn({ tenant, err: err.message }, 'QR encoding failed');

            return { status: 'connecting' };
        }
    }

    if (entry.status === 'error') {
        return { status: 'error', error: entry.error };
    }

    return { status: entry.status || 'disconnected' };
}

/**
 * Log out / close a tenant session and drop its auth state so the next start
 * re-pairs from a fresh QR.
 */
export async function logoutSession(tenant) {
    const entry = sessions.get(tenant);

    if (!entry) {
        return { status: 'disconnected' };
    }

    if (entry.reconnectTimer) {
        clearTimeout(entry.reconnectTimer);
        entry.reconnectTimer = null;
    }

    entry.reconnectAttempts = 0;

    try {
        if (entry.sock) {
            entry.sock.logout().catch(() => {});
            entry.sock = null;
        }
    } catch (err) {
        log.warn({ tenant, err: err.message }, 'logout error');
    }

    closeEntry(entry);

    return { status: 'disconnected' };
}

/**
 * Send a text message through the tenant's connected session.
 *
 * @returns {Promise<{success: boolean, message_id?: string|null, error?: string|null}>}
 */
export async function sendMessage(tenant, to, text) {
    const entry = sessions.get(tenant);

    if (!entry || !entry.sock || entry.status !== 'connected') {
        return { success: false, error: 'no_connected_session' };
    }

    const digits = String(to || '').replace(/^\+/, '').replace(/\D/g, '');

    if (digits.length < 8) {
        return { success: false, error: 'invalid_number' };
    }

    try {
        const sent = await entry.sock.sendMessage(`${digits}@s.whatsapp.net`, { text: String(text) });

        return { success: true, message_id: `WA-${sent?.id || 'unknown'}` };
    } catch (err) {
        log.warn({ tenant, err: err.message }, 'send failed');

        return { success: false, error: String(err.message || 'send_failed').slice(0, 200) };
    }
}

/**
 * Resume every already-paired session at boot (creds.json with a device `me`),
 * so a container restart does not drop an existing pairing.
 */
export async function resumeExistingSessions() {
    if (!existsSync(SESSIONS_DIR)) {
        return;
    }

    for (const tenant of readdirSync(SESSIONS_DIR)) {
        const credsPath = join(SESSIONS_DIR, tenant, 'creds.json');

        if (!existsSync(credsPath)) {
            continue;
        }

        try {
            const creds = JSON.parse(await readFile(credsPath, 'utf8'));

            if (!creds.me) {
                continue;
            }

            const entry = getEntry(tenant);
            await ensureSocket(entry);
            log.info({ tenant }, 'resumed existing session');
        } catch (err) {
            log.warn({ tenant, err: err.message }, 'session resume skipped');
        }
    }
}

export function sessionSummary() {
    return Object.fromEntries(
        [...sessions.entries()].map(([tenant, entry]) => [tenant, entry.status]),
    );
}