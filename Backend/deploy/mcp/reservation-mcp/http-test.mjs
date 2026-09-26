import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StreamableHTTPClientTransport } from '@modelcontextprotocol/sdk/client/streamableHttp.js';

const url = process.env.MCP_URL || 'http://kirsv-mcp:8765/mcp';

const transport = new StreamableHTTPClientTransport(new URL(url), {
  requestInit: {
    headers: { Accept: 'application/json, text/event-stream' },
  },
});

const client = new Client({ name: 'kirsv-http-test', version: '1.0.0' });
await client.connect(transport);

const tools = await client.listTools();
console.log('HTTP TOOLS (' + tools.tools.length + '): ' + tools.tools.map((t) => t.name).join(', '));

const result = await client.callTool({ name: 'business', arguments: {} });
console.log('business call =>', JSON.stringify(result));

await client.close();
process.exit(0);