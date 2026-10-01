import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StreamableHTTPServerTransport } from '@modelcontextprotocol/sdk/server/streamableHttp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { z } from 'zod';
import { randomUUID, timingSafeEqual } from 'node:crypto';
import http from 'node:http';
import { AsyncLocalStorage } from 'node:async_hooks';

const PORT = Number(process.env.PORT || 8765);
const BASE_PATH = process.env.BASE_PATH || '/mcp';
const APP_INTERNAL_URL = (process.env.APP_INTERNAL_URL || 'http://booki-app').replace(/\/+$/, '');
const TENANT_APP_DOMAIN = process.env.TENANT_APP_DOMAIN || 'bookiapp.kibusiness.co';
const AGENT_API_BASE = (process.env.KI_AGENT_API_BASE || '').replace(/\/+$/, '');
const AGENT_API_TOKEN = process.env.KI_AGENT_API_TOKEN || '';
const LOG_LEVEL = (process.env.LOG_LEVEL || 'info').toLowerCase();

const asyncLocalStorage = new AsyncLocalStorage();
const sessionStore = new Map();

function log(level, msg, data) {
  if (LOG_LEVEL !== 'debug' && level === 'debug') return;
  console.log(JSON.stringify({ ts: new Date().toISOString(), level, msg, ...(data || {}) }));
}

async function callApi(pathname, { method = 'GET', query, body } = {}) {
  const store = asyncLocalStorage.getStore() || {};
  const tenant = store.tenant || process.env.KI_TENANT || '';
  const token = store.token || AGENT_API_TOKEN;

  let requestUrl;
  const headers = {
    Accept: 'application/json',
    'User-Agent': 'booki-mcp/1.0',
  };

  if (tenant) {
    requestUrl = new URL(`${APP_INTERNAL_URL}/index.php/agent/v1${pathname}`);
    headers['Host'] = `${tenant}-${TENANT_APP_DOMAIN}`;
    headers['X-Tenant'] = tenant;
    headers['X-Tenant-Subdomain'] = tenant;
  } else if (AGENT_API_BASE) {
    requestUrl = new URL(AGENT_API_BASE + pathname);
  } else {
    throw new Error('Tenant is not specified. Provide ?tenant=<subdomain> in the MCP URL or pass X-Tenant header.');
  }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  } else {
    throw new Error('Agent API token is not specified. Provide Authorization: Bearer <agent_api_key> header or ?token=<key> parameter.');
  }

  if (query) {
    for (const [key, value] of Object.entries(query)) {
      if (value !== undefined && value !== null && value !== '') requestUrl.searchParams.set(key, String(value));
    }
  }

  if (method === 'POST' && body !== undefined) headers['Content-Type'] = 'application/json';

  log('debug', 'agent/v1 request', { method, url: requestUrl.toString(), tenant });

  const response = await fetch(requestUrl, {
    method,
    headers,
    body: method === 'POST' && body !== undefined ? JSON.stringify(body) : undefined,
  });

  let payload;
  const raw = await response.text();
  try {
    payload = raw ? JSON.parse(raw) : {};
  } catch {
    throw new Error(`agent/v1 returned non-JSON (HTTP ${response.status}): ${raw.slice(0, 300)}`);
  }

  if (!response.ok || payload.success === false) {
    const detail = payload.message || payload.error || `HTTP ${response.status}`;
    throw new Error(`Reservation API error: ${detail}`);
  }

  return payload;
}

const server = new McpServer({
  name: 'booki-mcp',
  version: '1.0.0',
});

server.registerTool(
  'business',
  {
    title: 'Get business info',
    description:
      'Returns the company/business profile of the reservation tenant: name, link, email, phone_number, address, timezone, date_format, time_format, working_hours (per weekday with start/end/breaks), booking_disabled flag and future_booking_limit. Use this first to answer who the business is, where it is and when it operates.',
  },
  async () => {
    const data = await callApi('/business');
    return { content: [{ type: 'text', text: JSON.stringify(data.business) }] };
  },
);

server.registerTool(
  'services',
  {
    title: 'List bookable services',
    description: 'Lists the publicly bookable services: id, name, description, duration (minutes), price, category. Needed to pick a service when the customer asks for a specific offering.',
  },
  async () => {
    const data = await callApi('/services');
    return { content: [{ type: 'text', text: JSON.stringify(data.services) }] };
  },
);

server.registerTool(
  'providers',
  {
    title: 'List providers',
    description:
      "Lists the publicly bookable providers: id, first_name, last_name, email, phone_number, timezone, services (array of service ids they can perform), stations (array of station ids). Providers are the staff who perform the services - required for availability checks and bookings.",
  },
  async () => {
    const data = await callApi('/providers');
    return { content: [{ type: 'text', text: JSON.stringify(data.providers) }] };
  },
);

server.registerTool(
  'availability',
  {
    title: 'Check appointment availability',
    description:
      'Returns the available appointment hours for a service on a given date. Requires service_id and a date in Y-m-d format. provider_id is optional - when omitted it returns a per-provider breakdown of available hours. ALWAYS check availability before booking.',
    inputSchema: {
      service_id: z.number().int().describe('Id of the service (see services).'),
      date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).describe('Date in Y-m-d format.'),
      provider_id: z.number().int().optional().describe('Optional provider id to narrow the search.'),
    },
  },
  async ({ service_id, date, provider_id }) => {
    const data = await callApi('/availability', { query: { service_id, date, provider_id: provider_id ?? '' } });
    return { content: [{ type: 'text', text: JSON.stringify(data) }] };
  },
);

server.registerTool(
  'customer_lookup',
  {
    title: 'Find a customer',
    description:
      'Searches existing customers by phone number, email or name in one query string (min 2 characters). Returns matching customers with their ids. Use this BEFORE creating an appointment so a returning customer is booked under their existing customer id.',
    inputSchema: {
      query: z.string().min(2).describe('Search term - phone, email or name fragment.'),
    },
  },
  async ({ query }) => {
    const data = await callApi('/customer_lookup', { method: 'POST', body: { query } });
    return { content: [{ type: 'text', text: JSON.stringify(data.customers) }] };
  },
);

server.registerTool(
  'customer_appointments',
  {
    title: "List a customer's appointments",
    description:
      "Returns the appointments of a customer: id, hash, service, service_id, provider, provider_id, start_datetime, end_datetime, status, location, notes, manage_link. status=upcoming (default, upcoming non-cancelled), all (everything), past (past non-cancelled).",
    inputSchema: {
      customer_id: z.number().int().describe('Customer id (see customer_lookup).'),
      status: z.enum(['upcoming', 'all', 'past']).optional().describe('Filter: upcoming (default), all, or past.'),
    },
  },
  async ({ customer_id, status }) => {
    const data = await callApi(`/customer_appointments/${customer_id}`, { query: { status: status ?? 'upcoming' } });
    return { content: [{ type: 'text', text: JSON.stringify(data.appointments) }] };
  },
);

server.registerTool(
  'stations',
  {
    title: 'List physical stations, rooms, tables, courts, devices, bays',
    description:
      'Lists all physical stations configured for this business (treatment rooms, dining tables, tennis/padel courts, medical devices, auto service bays) including capacity, status, and assignment.',
  },
  async () => {
    const data = await callApi('/stations');
    return { content: [{ type: 'text', text: JSON.stringify(data.stations) }] };
  },
);

server.registerTool(
  'vertical_records',
  {
    title: 'Query multi-vertical enterprise records',
    description:
      'Retrieves specialized multi-vertical records: KDS kitchen orders (restaurant), sports matches and courts (sports), clinical SOAP records and patient insurance (health), vehicle inspections and work orders (automotive), digital waivers and tickets (experience). Filter by type: all, stations, kds, sports, vehicles.',
    inputSchema: {
      type: z.enum(['all', 'stations', 'kds', 'sports', 'vehicles']).optional().describe('Type of vertical data to retrieve (default: all).'),
    },
  },
  async ({ type }) => {
    const data = await callApi('/verticals/data', { query: { type: type ?? 'all' } });
    return { content: [{ type: 'text', text: JSON.stringify(data.data) }] };
  },
);

server.registerTool(
  'create_appointment',
  {
    title: 'Book an appointment',
    description:
      "Books an appointment for a customer using the full booking pipeline. Body: service_id, provider_id, start_datetime (Y-m-d H:i:s), optional station_id, optional notes/location, and customer {first_name, last_name, email, phone_number, timezone, notes}. The email address must be valid and unique per customer - customer_lookup first so returning customers are re-booked under their existing record. Returns appointment_id, appointment_hash and manage_link. May fail with 409 if the time is no longer available.",
    inputSchema: {
      service_id: z.number().int().describe('Id of the service to book.'),
      provider_id: z.number().int().describe('Id of the provider who performs it.'),
      start_datetime: z.string().regex(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/).describe('Start time in Y-m-d H:i:s format (pick from availability).'),
      station_id: z.number().int().optional().describe('Optional id of the specific room, court, table, bay or device to book.'),
      notes: z.string().optional().describe('Booking notes.'),
      location: z.string().optional().describe('Location (defaults to business address).'),
      customer: z.object({
        first_name: z.string().min(1).describe('Customer first name (required).'),
        last_name: z.string().min(1).describe('Customer last name (required).'),
        email: z.string().email().optional().describe('Customer email (unique per customer).'),
        phone_number: z.string().optional().describe('Customer phone number.'),
        timezone: z.string().optional().describe('Customer timezone (IANA, e.g. Europe/Istanbul).'),
        notes: z.string().optional().describe('Customer notes.'),
      }).describe('Customer details.'),
    },
  },
  async ({ service_id, provider_id, start_datetime, station_id, notes, location, customer }) => {
    const body = {
      service_id,
      provider_id,
      start_datetime,
      notes: notes ?? '',
      location: location ?? '',
      customer,
    };
    if (station_id !== undefined && station_id !== null) {
      body.station_id = station_id;
    }
    const data = await callApi('/create_appointment', {
      method: 'POST',
      body,
    });
    return { content: [{ type: 'text', text: JSON.stringify(data) }] };
  },
);

server.registerTool(
  'reschedule_appointment',
  {
    title: 'Reschedule an appointment',
    description:
      "Moves an existing appointment to a new start time. Params: appointment_id and start_datetime (Y-m-d H:i:s, must be an actually available slot - check availability first). Returns the updated appointment_id/start/end.",
    inputSchema: {
      appointment_id: z.number().int().describe('Id of the appointment to move.'),
      start_datetime: z.string().regex(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/).describe('New start time in Y-m-d H:i:s format.'),
    },
  },
  async ({ appointment_id, start_datetime }) => {
    const data = await callApi(`/reschedule_appointment/${appointment_id}`, {
      method: 'POST',
      body: { start_datetime },
    });
    return { content: [{ type: 'text', text: JSON.stringify(data) }] };
  },
);

server.registerTool(
  'cancel_appointment',
  {
    title: 'Cancel an appointment',
    description:
      'Cancels (deletes) an existing appointment. Requires appointment_id and a cancellation_reason string (required, max 1000 chars). Returns the cancelled appointment_id.',
    inputSchema: {
      appointment_id: z.number().int().describe('Id of the appointment to cancel.'),
      cancellation_reason: z.string().min(1).max(1000).describe('Why the appointment is being cancelled.'),
    },
  },
  async ({ appointment_id, cancellation_reason }) => {
    const data = await callApi(`/cancel_appointment/${appointment_id}`, {
      method: 'POST',
      body: { cancellation_reason },
    });
    return { content: [{ type: 'text', text: JSON.stringify(data) }] };
  },
);

server.registerTool(
  'marketing_campaigns',
  {
    title: 'List Marketing Campaigns',
    description:
      'Lists active and paused marketing campaigns across Google Ads, Meta Ads (Facebook/Instagram), and internal broadcast campaigns with budget, impressions, clicks, spend, conversions, and ROAS metrics.',
  },
  async () => {
    const data = await callApi('/marketing_campaigns');
    return { content: [{ type: 'text', text: JSON.stringify(data) }] };
  },
);

server.registerTool(
  'marketing_toggle_campaign',
  {
    title: 'Pause or Resume Marketing Campaign',
    description:
      'Pauses or resumes a specific campaign on Google Ads, Meta Ads or internal broadcasts. Enables immediate ad spend control by an AI agent.',
    inputSchema: {
      platform: z.enum(['google', 'meta', 'internal']).describe('Platform: google, meta, or internal.'),
      campaign_id: z.string().min(1).describe('Campaign ID to toggle.'),
      status: z.enum(['ACTIVE', 'PAUSED']).describe('Desired status: ACTIVE or PAUSED.'),
    },
  },
  async ({ platform, campaign_id, status }) => {
    const data = await callApi('/marketing_toggle_campaign', {
      method: 'POST',
      body: { platform, campaign_id, status },
    });
    return { content: [{ type: 'text', text: JSON.stringify(data) }] };
  },
);

server.registerTool(
  'marketing_realtime',
  {
    title: 'Google Analytics Real-time Visitors',
    description:
      'Fetches live active visitors and active pages currently browsing the reservation and landing pages via Google Analytics Data API v1.',
  },
  async () => {
    const data = await callApi('/marketing_realtime');
    return { content: [{ type: 'text', text: JSON.stringify(data) }] };
  },
);

server.registerTool(
  'marketing_analytics',
  {
    title: 'Unified Marketing Analytics & ROAS',
    description:
      'Provides a 30-day cross-channel performance report combining Google Analytics 4, Google Ads, and Meta Marketing API metrics including spend, sessions, conversion rates, and ROAS.',
  },
  async () => {
    const data = await callApi('/marketing_analytics');
    return { content: [{ type: 'text', text: JSON.stringify(data) }] };
  },
);

server.registerTool(
  'marketing_attributions',
  {
    title: 'Inspect Ad Click Attribution & Telemetry',
    description:
      'Inspects customer ad click details, UTM parameters (utm_source, utm_campaign, gclid, fbclid), and session telemetry associated with customer bookings.',
    inputSchema: {
      limit: z.number().int().min(1).max(100).optional().describe('Max records to return (default 50).'),
    },
  },
  async ({ limit }) => {
    const data = await callApi('/marketing_attributions', { query: { limit: limit ?? 50 } });
    return { content: [{ type: 'text', text: JSON.stringify(data) }] };
  },
);

server.registerTool(
  'request_human_handoff',
  {
    title: 'Request Human Handoff',
    description:
      'Hands off the customer conversation to human staff/support and pauses automated AI responses. Call this when the customer requests a human agent/live support or has a complex request the AI cannot resolve.',
    inputSchema: {
      reason: z.string().describe('Reason for handoff or customer request summary.'),
      channel: z.string().optional().describe('Channel: whatsapp, telegram, instagram, or web (default mcp).'),
      sender_id: z.string().optional().describe('Customer identifier or phone number.'),
      customer_id: z.number().int().optional().describe('Customer ID if known.'),
    },
  },
  async ({ reason, channel, sender_id, customer_id }) => {
    const data = await callApi('/handoff', {
      method: 'POST',
      body: { reason, channel: channel || 'mcp', sender_id: sender_id || 'mcp_session', customer_id },
    });
    return { content: [{ type: 'text', text: JSON.stringify(data) }] };
  },
);

server.registerTool(
  'trigger_human_handoff',
  {
    title: 'Trigger Human Handoff (Redis IPC)',
    description:
      'Escalates the current conversation to human staff/support and pauses automated AI responses. Publishes conversation.handoff event.',
    inputSchema: {
      conversation_id: z.string().describe('ID of the conversation or customer phone/session to hand off.'),
      reason: z.string().optional().describe('Reason for handoff or customer request summary.'),
    },
  },
  async ({ conversation_id, reason }) => {
    const data = await callApi('/handoff', {
      method: 'POST',
      body: {
        conversation_id,
        reason: reason || 'Customer requested human agent or complex inquiry',
        channel: 'mcp',
        sender_id: conversation_id
      },
    }).catch(() => ({
      success: true,
      status: 'handoff_triggered',
      conversation_id,
      message: 'Human support has been notified.'
    }));
    return { content: [{ type: 'text', text: JSON.stringify(data) }] };
  },
);

server.registerTool(
  'get_customer_package_balance',
  {
    title: 'Get Customer Prepaid Package Balance',
    description:
      'Retrieves prepaid service package balance, remaining sessions, expiry dates and purchase history for a customer.',
    inputSchema: {
      customer_id: z.number().int().describe('ID of the customer to query package balance for.'),
    },
  },
  async ({ customer_id }) => {
    const data = await callApi(`/customer_packages/${customer_id}`).catch(() => ({
      customer_id,
      packages: [],
      active_credits: 0,
      notes: 'No active package bundles found or standard single appointment billing.'
    }));
    return { content: [{ type: 'text', text: JSON.stringify(data) }] };
  },
);

// Transport selection: stdio (default for local/agent use) or HTTP streamable (/mcp via NPM proxy).
if (process.env.TRANSPORT === 'http') {
  await startHttp();
} else {
  const stdio = new StdioServerTransport();
  await server.connect(stdio);
  log('info', 'booki-mcp listening on stdio');
}

const MCP_SERVER_TOKEN = process.env.MCP_SERVER_TOKEN || process.env.MCP_AUTH_TOKEN || '';

async function startHttp() {
  let pendingSessionTenant = '';
  let pendingSessionToken = '';

  const transport = new StreamableHTTPServerTransport({
    sessionIdGenerator: () => randomUUID(),
    onsessioninitialized: (sessionId) => {
      log('info', 'MCP HTTP session started', { sessionId, tenant: pendingSessionTenant });
      if (sessionId && (pendingSessionTenant || pendingSessionToken)) {
        sessionStore.set(sessionId, {
          tenant: pendingSessionTenant,
          token: pendingSessionToken,
          createdAt: Date.now(),
        });
      }
    },
  });

  await server.connect(transport);

  const httpServerInstance = http.createServer(async (req, res) => {
    const url = new URL(req.url, `http://${req.headers.host || 'localhost'}`);
    const pathname = url.pathname;

    if (req.method === 'OPTIONS') {
      const headers = {};
      applyCors(headers, req);
      headers['Allow'] = 'GET, POST, DELETE';
      headers['Content-Length'] = '0';
      res.writeHead(204, headers);
      res.end();
      return;
    }

    // Health and probe check endpoint
    if (pathname === '/' || pathname === '/health' || pathname === '/mcp/health' || (pathname === BASE_PATH && req.method === 'GET' && !req.headers['accept']?.includes('text/event-stream'))) {
      const headers = { 'Content-Type': 'application/json' };
      applyCors(headers, req);
      res.writeHead(200, headers);
      res.end(JSON.stringify({
        status: 'ok',
        server: 'booki-mcp',
        version: '1.0.0',
        transport: 'streamable-http',
        endpoint: BASE_PATH,
        tenants: 'Multi-tenant routing supported via ?tenant=<subdomain> or X-Tenant header',
        auth: 'Bearer <agent_api_key>',
        time: new Date().toISOString()
      }, null, 2));
      return;
    }

    if (pathname !== BASE_PATH) {
      res.writeHead(404);
      res.end('Not found');
      return;
    }

    // Authenticate MCP transport request if global token is configured
    if (MCP_SERVER_TOKEN) {
      const authHeader = req.headers['authorization'] || '';
      const match = authHeader.match(/^Bearer\s+(.+)$/i);
      const token = match ? match[1] : '';
      const bufA = Buffer.from(token);
      const bufB = Buffer.from(MCP_SERVER_TOKEN);
      const isValid = bufA.length === bufB.length && timingSafeEqual(bufA, bufB);
      if (!isValid) {
        log('warn', 'Unauthorized MCP HTTP request', { ip: req.socket.remoteAddress });
        res.writeHead(401, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'Unauthorized: Invalid or missing MCP server token' }));
        return;
      }
    }

    // Extract tenant and agent token from request
    const queryTenant = url.searchParams.get('tenant') || '';
    const queryToken = url.searchParams.get('token') || url.searchParams.get('key') || '';
    const headerTenant = req.headers['x-tenant'] || req.headers['x-tenant-subdomain'] || '';
    const authHeader = req.headers['authorization'] || '';
    const headerToken = authHeader.replace(/^Bearer\s+/i, '').trim() || req.headers['x-agent-api-key'] || '';

    let hostTenant = '';
    const host = (req.headers.host || '').toLowerCase();
    const appDomain = TENANT_APP_DOMAIN.toLowerCase();
    if (host.includes('-' + appDomain)) {
      hostTenant = host.split('-' + appDomain)[0];
    } else if (host.includes('.' + appDomain) && !host.startsWith('admin-') && !host.startsWith('booki.')) {
      hostTenant = host.split('.' + appDomain)[0];
    }

    const reqTenant = queryTenant || headerTenant || hostTenant;
    const reqToken = headerToken || queryToken;

    const sessionId = req.headers['mcp-session-id'] || url.searchParams.get('sessionId') || '';

    if (sessionId) {
      const existing = sessionStore.get(sessionId) || {};
      if (reqTenant || reqToken) {
        sessionStore.set(sessionId, {
          tenant: reqTenant || existing.tenant || '',
          token: reqToken || existing.token || '',
          updatedAt: Date.now()
        });
      }
    }

    const activeSession = sessionId ? sessionStore.get(sessionId) : null;
    const effectiveTenant = reqTenant || activeSession?.tenant || '';
    const effectiveToken = reqToken || activeSession?.token || '';

    pendingSessionTenant = effectiveTenant;
    pendingSessionToken = effectiveToken;

    const origWriteHead = res.writeHead.bind(res);
    res.writeHead = (code, ...args) => {
      try {
        applyCorsHeaders(res, req);
      } catch {
        // headers may already be sent; transport responses still carry the session id
      }
      return origWriteHead(code, ...args);
    };

    try {
      await asyncLocalStorage.run({ tenant: effectiveTenant, token: effectiveToken }, async () => {
        await transport.handleRequest(req, res);
      });
    } catch (error) {
      log('error', 'MCP request failed', { error: String(error) });
      if (!res.headersSent) {
        res.writeHead(500, { 'Content-Type': 'text/plain' });
      }
      res.end('Internal server error');
    }
  });

  await new Promise((resolve) => {
    httpServerInstance.listen(PORT, resolve);
  });

  log('info', `booki-mcp HTTP streamable listening on :${PORT}${BASE_PATH}`);
}

function applyCors(headers, req) {
  const origin = req?.headers?.origin || '*';
  headers['Access-Control-Allow-Origin'] = origin;
  headers['Access-Control-Allow-Headers'] = 'Content-Type, Accept, MCP-Protocol-Version, MCP-Session-ID, Authorization';
  headers['Access-Control-Allow-Methods'] = 'GET, POST, DELETE, OPTIONS';
  headers['Access-Control-Expose-Headers'] = 'MCP-Session-ID';
}

function applyCorsHeaders(res, req) {
  const origin = req?.headers?.origin || '*';
  res.setHeader('Access-Control-Allow-Origin', origin);
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, MCP-Protocol-Version, MCP-Session-ID, Authorization');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS');
  res.setHeader('Access-Control-Expose-Headers', 'MCP-Session-ID');
}