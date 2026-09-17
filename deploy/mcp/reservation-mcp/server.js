import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StreamableHTTPServerTransport } from '@modelcontextprotocol/sdk/server/streamableHttp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { z } from 'zod';
import { randomUUID } from 'node:crypto';
import http from 'node:http';

const PORT = Number(process.env.PORT || 8765);
const BASE_PATH = process.env.BASE_PATH || '/mcp';
const AGENT_API_BASE = (process.env.KI_AGENT_API_BASE || '').replace(/\/+$/, '');
const AGENT_API_TOKEN = process.env.KI_AGENT_API_TOKEN || '';
const LOG_LEVEL = (process.env.LOG_LEVEL || 'info').toLowerCase();

function log(level, msg, data) {
  if (LOG_LEVEL !== 'debug' && level === 'debug') return;
  console.log(JSON.stringify({ ts: new Date().toISOString(), level, msg, ...(data || {}) }));
}

if (!AGENT_API_BASE) log('warn', 'KI_AGENT_API_BASE is not set - agent/v1 tools will return configuration errors.');
if (!AGENT_API_TOKEN) log('warn', 'KI_AGENT_API_TOKEN is not set - agent/v1 tools will return configuration errors.');

async function callApi(pathname, { method = 'GET', query, body } = {}) {
  if (!AGENT_API_BASE) {
    throw new Error('Reservation agent API is not configured. Set KI_AGENT_API_BASE.');
  }

  const url = new URL(AGENT_API_BASE + pathname);
  if (query) {
    for (const [key, value] of Object.entries(query)) {
      if (value !== undefined && value !== null && value !== '') url.searchParams.set(key, String(value));
    }
  }

  const headers = {
    Accept: 'application/json',
    Authorization: `Bearer ${AGENT_API_TOKEN}`,
    'User-Agent': 'kirsv-mcp/1.0',
  };

  if (method === 'POST' && body !== undefined) headers['Content-Type'] = 'application/json';

  log('debug', 'agent/v1 request', { method, url: url.toString() });

  const response = await fetch(url, {
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
  name: 'kirsv-mcp',
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
  'create_appointment',
  {
    title: 'Book an appointment',
    description:
      "Books an appointment for a customer using the full booking pipeline. Body: service_id, provider_id, start_datetime (Y-m-d H:i:s), optional notes/location, and customer {first_name, last_name, email, phone_number, timezone, notes}. The email address must be valid and unique per customer - customer_lookup first so returning customers are re-booked under their existing record. Returns appointment_id, appointment_hash and manage_link. May fail with 409 if the time is no longer available.",
    inputSchema: {
      service_id: z.number().int().describe('Id of the service to book.'),
      provider_id: z.number().int().describe('Id of the provider who performs it.'),
      start_datetime: z.string().regex(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/).describe('Start time in Y-m-d H:i:s format (pick from availability).'),
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
  async ({ service_id, provider_id, start_datetime, notes, location, customer }) => {
    const data = await callApi('/create_appointment', {
      method: 'POST',
      body: { service_id, provider_id, start_datetime, notes: notes ?? '', location: location ?? '', customer },
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

// Transport selection: stdio (default for local/agent use) or HTTP streamable (/mcp via NPM proxy).
if (process.env.TRANSPORT === 'http') {
  await startHttp();
} else {
  const stdio = new StdioServerTransport();
  await server.connect(stdio);
  log('info', 'kirsv-mcp listening on stdio');
}

async function startHttp() {
  const transport = new StreamableHTTPServerTransport({
    sessionIdGenerator: () => randomUUID(),
    onsessioninitialized: (sessionId) => {
      log('info', 'MCP HTTP session started', { sessionId });
    },
  });

  await server.connect(transport);

  const httpServerInstance = http.createServer(async (req, res) => {
    const url = new URL(req.url, `http://${req.headers.host || 'localhost'}`);
    const pathname = url.pathname;

    if (req.method === 'OPTIONS') {
      const headers = {};
      applyCors(headers);
      headers['Allow'] = 'GET, POST, DELETE';
      headers['Content-Length'] = '0';
      res.writeHead(204, headers);
      res.end();
      return;
    }

    if (pathname !== BASE_PATH) {
      res.writeHead(404);
      res.end('Not found');
      return;
    }

    const origWriteHead = res.writeHead.bind(res);
    res.writeHead = (code, ...args) => {
      try {
        res.setHeader('Access-Control-Allow-Origin', '*');
        res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, MCP-Protocol-Version, MCP-Session-ID, Authorization');
        res.setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS');
        res.setHeader('Access-Control-Expose-Headers', 'MCP-Session-ID');
      } catch {
        // headers may already be sent; transport responses still carry the session id
      }
      return origWriteHead(code, ...args);
    };

    try {
      await transport.handleRequest(req, res);
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

  log('info', `kirsv-mcp HTTP streamable listening on :${PORT}${BASE_PATH}`);
}

function applyCors(headers) {
  headers['Access-Control-Allow-Origin'] = '*';
  headers['Access-Control-Allow-Headers'] = 'Content-Type, Accept, MCP-Protocol-Version, MCP-Session-ID, Authorization';
  headers['Access-Control-Allow-Methods'] = 'GET, POST, DELETE, OPTIONS';
  headers['Access-Control-Expose-Headers'] = 'MCP-Session-ID';
}