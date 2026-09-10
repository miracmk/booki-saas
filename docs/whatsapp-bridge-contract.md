# WhatsApp Bridge REST Contract (ki-wa-bridge)

> Dalga 3 / Faz 3.5 - scaffold for the **unofficial** WhatsApp sender mode.
> The PHP application never talks to WhatsApp directly in this mode; it calls a
> separate Node sidecar container ("ki-wa-bridge", Baileys / whatsapp-web.js
> based) over this small HTTP contract. The sidecar owns the device pairing
> (QR), websocket connection and per-tenant session state.

This document is the **contract the sidecar must implement**. The PHP side
(`Whatsapp_bridge` library, `Whatsapp` controller) already implements the
client half of everything below.

## Tenants / sessions

Each tenant has an isolated session, keyed by its **subdomain** (multi-tenant
mode) or the fixed key `default` (standalone deployments). Session storage must
live per tenant (e.g. `sessions/<tenant>/` directory), so a QR re-pair or logout
of one tenant never affects another.

## Transport / auth

- Base URL: configured in the tenant's `messaging_settings.whatsapp_bridge_url`.
- Authentication: `X-Bridge-Secret` header on every request, compared
  constant-time against the secret the admin configures in
  `messaging_settings.whatsapp_bridge_secret`.
- Payloads: JSON, `Content-Type: application/json`.
- The bridge MUST NOT expose CORS-enabled endpoints or accept requests without
  a valid secret.

## Endpoints (implemented by the sidecar)

### `GET /health`
Small liveness probe used by the "check connection" wizard step.

**200 OK**
```json
{ "status": "ok", "version": "1.0.0" }
```

### `POST /v1/session/:tenant/start`
Begin a coupling session for tenant `:tenant`. The app passes its webhook URL so
the bridge knows where to forward inbound messages.

**Request body**
```json
{ "webhookUrl": "https://salon.sub.domain/whatsapp/bridge_inbound" }
```

**200 OK** - pairing session created. QR data is NOT returned here; the app
polls `GET /v1/session/:tenant/status` (see below).
```json
{ "status": "connecting" }
```
If a session already exists (connected), return the existing state rather than
forcing a re-pair:
```json
{ "status": "connected" }
```

### `GET /v1/session/:tenant/status`
Poll this while connecting. While pairing, return the QR code the admin will
scan from the panel.

**200 OK**
```json
{ "status": "connecting", "qr": "data:image/png;base64,..." }
```
After successful pairing:
```json
{ "status": "connected", "name": "Salon Flora" }
```
Other states: `disconnected` (no session / after logout), `error`
(`"error": "reason"`).

### `POST /v1/session/:tenant/logout`
Log out/close the device session for `:tenant`. 200 `{ "status": "disconnected" }`.

### `POST /v1/send`
Send a text message on behalf of the given tenant. The bridge resolves `:tenant`
inside the body to that tenant's connected session.

**Request body**
```json
{ "tenant": "salon", "to": "905321234567", "text": "Randevunuz ..." }
```

**200 OK**
```json
{ "success": true, "message_id": "WA-..." }
```
**200 OK on failure** (app treats HTTP 200 + `success:false` as a send failure)
```json
{ "success": false, "error": "no_connected_session" }
```

### Inbound (bridge -> PHP app)

When the paired device receives a message, POST it to the webhook URL the app
supplied at `start`:

```
POST {webhookUrl}
X-Bridge-Secret: <shared secret>
Content-Type: application/json
```

```json
{
  "tenant": "salon",
  "from": "905321234567",
  "body": "Merhaba",
  "type": "text",
  "message_id": "WA-..."
}
```

- `from` should be a dialing-prefixed phone number without a leading `+`
  (Baileys JIDs are already `<number>@s.whatsapp.net`; strip the domain).
- `type`: `text` (other types allowed, body may then be `[<type> message]`).
- The app verifies the secret header, matches `from` against
  `users.whatsapp_wa_id`, stores the message and returns `200`. Any non-200
  response should be treated as "retry later" by the bridge; the app logs and
  drops gracefully on parse errors either way.

## Auth example (sidecar verification)

```js
const secret = process.env.WA_BRIDGE_SECRET;
const received = req.headers['x-bridge-secret'];
if (!received || !crypto.timingSafeEqual(Buffer.from(received), Buffer.from(secret))) {
    return res.status(401).json({ success: false, error: 'unauthorized' });
}
```

## Deployment shape (next phase)

- Separate container (`ki-wa-bridge`, Node 20+/24), NOT part of the PHP app
  container. Runs alongside `ki-reservation-app` in the deploy docker-compose.
- Per-tenant session directories volume-mounted (`sessions/`), so container
  restarts survive an already-paired session.
- `WA_BRIDGE_SECRET` env on the container is a platform default; the per-tenant
  secret stored in `messaging_settings` overrides it for that tenant's calls.
- Health-checks: app's "check connection" already calls `GET /health`.

## Out of scope of this phase

- The actual Baileys/whatsapp-web.js implementation and the `ki-wa-bridge`
  container (delivered in a follow-up phase of Faz 3.5).
- Media messages, groups, multi-device edge cases.