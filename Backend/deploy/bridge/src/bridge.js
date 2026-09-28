import makeWASocket, {
    useMultiFileAuthState,
    makeCacheableSignalKeyStore,
    DisconnectReason,
    fetchLatestBaileysVersion,
    Browsers,
    isJidUser,
    isLidUser,
    jidNormalizedUser,
    normalizeMessageContent,
} from '@whiskeysockets/baileys';

import pino from 'pino';
import { mkdirSync, existsSync, readdirSync, rmSync, readFileSync, writeFileSync, statSync } from 'node:fs';
import { readFile, writeFile } from 'node:fs/promises';
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

/**
 * Persistent + in-memory message store for getMessage retry requests.
 * Prevents "Waiting for this message. This may take a while" on recipient devices
 * when end-to-end encryption keys are re-negotiated by WhatsApp or after container restart.
 */
const MAX_STORED_MESSAGES = 1000;
const messageStore = new Map();

function getMessagePath(tenant, id) {
    return join(sessionDir(tenant), 'messages', `${id}.json`);
}

function pruneStoredMessages(tenant) {
    try {
        const msgDir = join(sessionDir(tenant), 'messages');
        if (!existsSync(msgDir)) return;
        const files = readdirSync(msgDir);
        if (files.length > 2000) {
            const stats = files.map((f) => ({ f, time: statSync(join(msgDir, f)).mtimeMs }));
            stats.sort((a, b) => a.time - b.time);
            const toDelete = stats.slice(0, files.length - 2000);
            for (const item of toDelete) {
                rmSync(join(msgDir, item.f), { force: true });
            }
        }
    } catch {
        // ignore
    }
}

function storeMessage(tenant, id, message) {
    if (!id || !message) return;
    const key = `${tenant}:${id}`;
    if (messageStore.size >= MAX_STORED_MESSAGES) {
        const oldestKey = messageStore.keys().next().value;
        if (oldestKey) messageStore.delete(oldestKey);
    }
    messageStore.set(key, message);

    try {
        const msgDir = join(sessionDir(tenant), 'messages');
        if (!existsSync(msgDir)) {
            mkdirSync(msgDir, { recursive: true });
        }
        const filePath = getMessagePath(tenant, id);
        writeFileSync(filePath, JSON.stringify(message), 'utf8');
        if (Math.random() < 0.05) {
            pruneStoredMessages(tenant);
        }
    } catch (err) {
        log.warn({ tenant, id, err: err.message }, 'failed to persist message on disk');
    }
}

function getStoredMessage(tenant, id) {
    if (!id) return undefined;
    const mem = messageStore.get(`${tenant}:${id}`);
    if (mem) return mem;

    try {
        const filePath = getMessagePath(tenant, id);
        if (existsSync(filePath)) {
            const raw = JSON.parse(readFileSync(filePath, 'utf8'));
            messageStore.set(`${tenant}:${id}`, raw);
            return raw;
        }
    } catch (err) {
        log.debug({ tenant, id, err: err.message }, 'failed to read stored message from disk');
    }

    return undefined;
}

/** Backoff schedule for auto-reconnect after a transient connection close. */
const RECONNECT_DELAYS_MS = [2000, 5000, 10000];

/** @type {import('pino').Logger} */
const log = pino({ level: process.env.LOG_LEVEL || 'info' });

export function resolveSessionTenant(tenant) {
    if (!tenant) return tenant;
    const t = String(tenant).toLowerCase().trim();
    if (t === 'demo' || t.startsWith('demo-') || t.includes('_sb') || t.includes('-sb') || t === 'booki-demo') {
        return 'salonflora';
    }
    return tenant;
}

function sessionDir(tenant) {
    return join(SESSIONS_DIR, resolveSessionTenant(tenant));
}

function sessionConfigPath(tenant) {
    return join(sessionDir(tenant), 'bridge-config.json');
}

function jidMapPath(tenant) {
    return join(sessionDir(tenant), 'jid-map.json');
}

function loadJidMap(entry) {
    try {
        if (existsSync(entry.dir)) {
            const files = readdirSync(entry.dir);
            for (const f of files) {
                const m = f.match(/^session-(\d{13,})\./);
                if (m) {
                    const lidId = m[1];
                    entry.jidMap.set(lidId, `${lidId}@lid`);
                    entry.jidMap.set(`${lidId}@lid`, `${lidId}@lid`);
                }
            }
        }

        const file = jidMapPath(entry.tenant);
        if (existsSync(file)) {
            const data = JSON.parse(readFileSync(file, 'utf8'));
            for (const [k, v] of Object.entries(data)) {
                entry.jidMap.set(k, v);
            }
        }
    } catch {
        // ignore
    }
}

function saveJidMap(entry) {
    try {
        const file = jidMapPath(entry.tenant);
        const obj = Object.fromEntries(entry.jidMap);
        writeFileSync(file, JSON.stringify(obj, null, 2), 'utf8');
    } catch {
        // ignore
    }
}

function getEntry(tenant) {
    tenant = resolveSessionTenant(tenant);
    if (!sessions.has(tenant)) {
        const entry = {
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
            jidMap: new Map(),
            sentMessageIds: new Set(),
        };
        loadJidMap(entry);
        sessions.set(tenant, entry);
    }

    return sessions.get(tenant);
}

async function persistSessionConfig(entry) {
    if (!entry.webhookUrl || !entry.webhookSecret) {
        return;
    }

    // writeFile does not create parent directories - on a first-ever start the
    // tenant dir does not exist yet (createSocket's mkdirSync runs later), so
    // without this, startSession aborts with ENOENT before any QR can be shown.
    mkdirSync(entry.dir, { recursive: true });

    await writeFile(sessionConfigPath(entry.tenant), JSON.stringify({
        webhookUrl: entry.webhookUrl,
        webhookSecret: entry.webhookSecret,
    }), { mode: 0o600 });
}

async function restoreSessionConfig(entry) {
    try {
        const config = JSON.parse(await readFile(sessionConfigPath(entry.tenant), 'utf8'));

        if (typeof config.webhookUrl === 'string' && config.webhookUrl !== '') {
            let url = config.webhookUrl;
            if (entry.tenant === 'platform' && url.includes('/whatsapp/bridge_inbound')) {
                url = url.replace('/whatsapp/bridge_inbound', '/superadmin_tenants/platform_bridge_inbound');
            }
            if (url.includes('book.salonflora.tr')) {
                url = url.replace('book.salonflora.tr', 'salonflora-bookiapp.kibusiness.co');
            }
            entry.webhookUrl = url;
        }

        if (typeof config.webhookSecret === 'string' && config.webhookSecret !== '') {
            entry.webhookSecret = config.webhookSecret;
        }
    } catch (err) {
        if (err.code !== 'ENOENT') {
            log.warn({ tenant: entry.tenant, err: err.message }, 'session config restore failed');
        }
    }
}

/**
 * Extract a human-readable text payload from a Baileys message node, or null
 * when the node carries no text. Unwraps ephemeral, viewOnce, and other wrappers.
 */
function extractText(msg) {
    if (!msg || !msg.message) {
        return null;
    }

    const m = normalizeMessageContent(msg.message) || {};

    if (typeof m.conversation === 'string' && m.conversation.length > 0) {
        return m.conversation;
    }

    const ext = m.extendedTextMessage && m.extendedTextMessage.text;
    if (typeof ext === 'string' && ext.length > 0) {
        return ext;
    }

    if (typeof m.imageMessage?.caption === 'string' && m.imageMessage.caption.length > 0) {
        return m.imageMessage.caption;
    }

    if (typeof m.videoMessage?.caption === 'string' && m.videoMessage.caption.length > 0) {
        return m.videoMessage.caption;
    }

    if (typeof m.documentWithCaptionMessage?.message?.documentMessage?.caption === 'string') {
        return m.documentWithCaptionMessage.message.documentMessage.caption;
    }

    if (typeof m.buttonsResponseMessage?.selectedButtonId === 'string') {
        return m.buttonsResponseMessage.selectedButtonId;
    }

    if (typeof m.templateButtonReplyMessage?.selectedId === 'string') {
        return m.templateButtonReplyMessage.selectedId;
    }

    if (typeof m.listResponseMessage?.singleSelectReply?.selectedRowId === 'string') {
        return m.listResponseMessage.singleSelectReply.selectedRowId;
    }

    return null;
}

/**
 * The message "type" the app expects: 'text' for plain text, otherwise the raw
 * proto field name (e.g. 'imageMessage').
 */
function messageType(msg) {
    if (!msg || !msg.message) {
        return 'unknown';
    }

    const m = normalizeMessageContent(msg.message) || {};
    const keys = Object.keys(m);

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
        'X-Tenant': entry.tenant,
        'X-Tenant-Subdomain': entry.tenant,
    };

    if (entry.webhookSecret) {
        headers['X-Bridge-Secret'] = entry.webhookSecret;
    }

    let primaryUrl = entry.webhookUrl;
    try {
        const parsed = new URL(entry.webhookUrl);
        headers['Host'] = parsed.host;
        if (entry.tenant === 'platform') {
            headers['Host'] = process.env.SUPERADMIN_DOMAIN || 'admin-bookiapp.kibusiness.co';
        }
        if (parsed.hostname.endsWith('kibusiness.co') || parsed.hostname === 'booki-app') {
            // Direct internal HTTP call over docker network to booki-app:
            primaryUrl = `http://booki-app${parsed.pathname}${parsed.search}`;
        }
    } catch {
        // keep entry.webhookUrl
    }

    log.info({ tenant: entry.tenant, from: payload.from, url: primaryUrl }, 'forwarding inbound message to app');

    fetch(primaryUrl, {
        method: 'POST',
        headers,
        body: JSON.stringify({ tenant: entry.tenant, ...payload }),
        signal: AbortSignal.timeout(60000),
    })
        .then(async (res) => {
            if (!res.ok) {
                const text = await res.text().catch(() => '');
                log.warn({ tenant: entry.tenant, status: res.status, body: text.slice(0, 200), url: primaryUrl }, 'inbound forward non-200');
            } else {
                log.info({ tenant: entry.tenant, from: payload.from }, 'inbound forward succeeded (200)');
            }
        })
        .catch((err) => {
            log.warn({ tenant: entry.tenant, err: err.message, url: primaryUrl }, 'primary inbound forward failed, retrying original URL');
            if (primaryUrl !== entry.webhookUrl) {
                fetch(entry.webhookUrl, {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({ tenant: entry.tenant, ...payload }),
                    signal: AbortSignal.timeout(60000),
                }).catch((fallbackErr) => {
                    log.warn({ tenant: entry.tenant, err: fallbackErr.message, url: entry.webhookUrl }, 'fallback inbound forward failed');
                });
            }
        });
}

function onMessagesUpsert(entry, upsert) {
    log.info({ tenant: entry.tenant, count: upsert.messages?.length, type: upsert.type }, 'messages.upsert received');

    for (const msg of upsert.messages || []) {
        if (!msg.message) {
            continue;
        }

        if (msg.key?.id) {
            storeMessage(entry.tenant, msg.key.id, msg.message);
        }

        const rawJid = msg.key?.remoteJid;
        if (!rawJid || (!isJidUser(rawJid) && !isLidUser(rawJid))) {
            log.debug({ tenant: entry.tenant, rawJid }, 'ignoring non-user jid');
            continue;
        }

        // Avoid infinite loop if this message was sent by our own socket
        if (msg.key?.id && entry.sentMessageIds?.has(msg.key.id)) {
            continue;
        }

        // Check self-chat test vs normal outbound message:
        const botJid = entry.sock?.user?.id ? jidNormalizedUser(entry.sock.user.id) : null;
        const botLid = entry.sock?.user?.lid ? jidNormalizedUser(entry.sock.user.lid) : null;
        const normRemote = jidNormalizedUser(rawJid);
        const isSelfChat = (botJid && normRemote === botJid) || (botLid && normRemote === botLid);

        if (msg.key?.fromMe) {
            if (!isSelfChat) {
                // Outgoing message to another customer sent from phone/web, skip
                continue;
            }
            // In self-chat (user testing their own bot from the paired phone), allow it through
        }

        // Ignore reaction messages and empty protocol messages
        const normMsg = normalizeMessageContent(msg.message);
        if (normMsg?.reactionMessage || normMsg?.protocolMessage) {
            continue;
        }

        // Sender identifier: use participant if it contains a user jid, or rawJid
        let fromNumber = rawJid.split('@')[0];
        if (msg.key?.participant && isJidUser(msg.key.participant)) {
            fromNumber = msg.key.participant.split('@')[0];
        }

        // Track both fromNumber and rawJid in jidMap so sendMessage can route back
        if (!entry.jidMap) entry.jidMap = new Map();
        entry.jidMap.set(fromNumber, rawJid);
        entry.jidMap.set(rawJid, rawJid);
        saveJidMap(entry);

        const type = messageType(msg);
        const text = extractText(msg);
        const body = text ?? `[${type} message]`;

        log.info({
            tenant: entry.tenant,
            from: fromNumber,
            rawJid,
            fromMe: msg.key?.fromMe,
            pushName: msg.pushName,
            type,
            body: body.slice(0, 100),
        }, 'forwarding inbound WhatsApp message');

        forwardInbound(entry, {
            from: fromNumber,
            raw_jid: rawJid,
            push_name: msg.pushName || '',
            body,
            type,
            message_id: `WA-${msg.key?.id || 'unknown'}`,
        });
    }
}

async function createSocket(entry) {
    mkdirSync(entry.dir, { recursive: true });

    const { state, saveCreds } = await useMultiFileAuthState(entry.dir);
    const { version } = await fetchLatestBaileysVersion();

    const sock = makeWASocket({
        version,
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, log),
        },
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false,
        browser: Browsers.ubuntu('Chrome'),
        syncFullHistory: false,
        markOnlineOnConnect: false,
        generateHighQualityLinkPreview: true,
        retryRequestDelayMs: 250,
        maxMsgRetryCount: 5,
        getMessage: async (key) => {
            const stored = getStoredMessage(entry.tenant, key?.id);
            if (stored) {
                log.info({ tenant: entry.tenant, id: key?.id }, 'getMessage retry fulfilled');
                return stored;
            }
            log.warn({ tenant: entry.tenant, id: key?.id }, 'getMessage retry not found');
            return undefined;
        },
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
        let url = webhookUrl;
        if (tenant === 'platform' && url.includes('/whatsapp/bridge_inbound')) {
            url = url.replace('/whatsapp/bridge_inbound', '/superadmin_tenants/platform_bridge_inbound');
        }
        if (url.includes('book.salonflora.tr')) {
            url = url.replace('book.salonflora.tr', 'salonflora-bookiapp.kibusiness.co');
        }
        entry.webhookUrl = url;
    }

    if (webhookSecret) {
        entry.webhookSecret = webhookSecret;
    }

    await persistSessionConfig(entry);

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
    const entry = sessions.get(resolveSessionTenant(tenant));

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
    const resolved = resolveSessionTenant(tenant);
    if (resolved === 'salonflora' && tenant !== 'salonflora') {
        return { status: 'connected' };
    }
    const entry = sessions.get(resolved);

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
    const entry = sessions.get(resolveSessionTenant(tenant));

    if (!entry || !entry.sock || entry.status !== 'connected') {
        return { success: false, error: 'no_connected_session' };
    }

    const toStr = String(to || '').trim();
    let targetJid = null;
    let digits = toStr.replace(/^\+/, '').replace(/\D/g, '');
    if (digits.startsWith('0') && digits.length === 11) {
        digits = '9' + digits;
    } else if (digits.length === 10 && digits.startsWith('5')) {
        digits = '90' + digits;
    }

    if (toStr.endsWith('@s.whatsapp.net') || toStr.endsWith('@lid') || toStr.endsWith('@g.us')) {
        targetJid = toStr;
    } else if (entry.jidMap && entry.jidMap.has(toStr)) {
        targetJid = entry.jidMap.get(toStr);
    } else if (digits && entry.jidMap && entry.jidMap.has(digits)) {
        targetJid = entry.jidMap.get(digits);
    } else if (isJidUser(toStr) || isLidUser(toStr)) {
        targetJid = toStr;
    } else {
        if (digits.length < 8) {
            return { success: false, error: 'invalid_number' };
        }

        if (digits.length >= 13 && !digits.startsWith('90')) {
            targetJid = `${digits}@lid`;
        } else {
            targetJid = `${digits}@s.whatsapp.net`;
        }
    }

    // Resolve phone numbers to canonical LID using onWhatsApp to prevent Signal session clashes
    if (!targetJid.endsWith('@lid') && !targetJid.endsWith('@g.us')) {
        try {
            const queryJid = digits ? `${digits}@s.whatsapp.net` : targetJid;
            const results = await entry.sock.onWhatsApp(queryJid);
            if (results && results.length > 0 && results[0]?.exists) {
                const match = results[0];
                if (match.lid) {
                    log.info({ tenant, phone: digits || toStr, lid: match.lid }, 'onWhatsApp mapped phone to LID');
                    targetJid = match.lid;
                    if (!entry.jidMap) entry.jidMap = new Map();
                    if (digits) entry.jidMap.set(digits, match.lid);
                    entry.jidMap.set(toStr, match.lid);
                    if (match.jid) entry.jidMap.set(match.jid, match.lid);
                    saveJidMap(entry);

                    // Clean up conflicting phone-number session files if an LID session exists
                    if (digits) {
                        try {
                            const files = readdirSync(entry.dir);
                            for (const f of files) {
                                if (f.startsWith(`session-${digits}.`)) {
                                    rmSync(join(entry.dir, f), { force: true });
                                    log.info({ tenant, file: f }, 'removed conflicting phone session file');
                                }
                            }
                        } catch {}
                    }
                } else if (match.jid) {
                    targetJid = match.jid;
                }
            }
        } catch (owErr) {
            log.warn({ tenant, targetJid, err: owErr.message }, 'onWhatsApp resolution error, using targetJid directly');
        }
    }

    try {
        log.info({ tenant, targetJid, text: String(text).slice(0, 50) }, 'sending WhatsApp message');
        const sent = await entry.sock.sendMessage(targetJid, { text: String(text) });
        const messageId = sent?.key?.id;

        if (messageId) {
            if (!entry.sentMessageIds) entry.sentMessageIds = new Set();
            entry.sentMessageIds.add(messageId);
            if (entry.sentMessageIds.size > 1000) {
                const first = entry.sentMessageIds.values().next().value;
                if (first) entry.sentMessageIds.delete(first);
            }
            if (sent?.message) {
                storeMessage(tenant, messageId, sent.message);
            }
        }

        return { success: true, message_id: `WA-${messageId || 'unknown'}` };
    } catch (err) {
        log.warn({ tenant, err: err.message, targetJid }, 'send failed');

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
            await restoreSessionConfig(entry);
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