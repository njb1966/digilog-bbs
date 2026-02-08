'use strict';

const { createServer } = require('net');
const { readFileSync } = require('fs');
const { resolve } = require('path');
const { WebSocketServer } = require('ws');
const { createConnection } = require('net');
const crypto = require('crypto');
const { cp437ToUtf8, utf8ToCp437 } = require('./cp437');

// Load .env from project root
const envPath = resolve(__dirname, '..', '.env');
const envLines = readFileSync(envPath, 'utf8').split('\n');
const env = {};
for (const line of envLines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) continue;
    const eq = trimmed.indexOf('=');
    if (eq === -1) continue;
    let key = trimmed.slice(0, eq).trim();
    let val = trimmed.slice(eq + 1).trim();
    if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
        val = val.slice(1, -1);
    }
    env[key] = val;
}

const PORT = parseInt(env.DOOR_PROXY_PORT || '7682', 10);
const SECRET = env.DOOR_PROXY_SECRET || '';

if (!SECRET) {
    console.error('DOOR_PROXY_SECRET not set in .env');
    process.exit(1);
}

/**
 * Validate HMAC-signed token. Returns parsed payload or null.
 * Token format: base64(JSON payload) + '.' + hex(HMAC-SHA256)
 */
function validateToken(token) {
    const dotIdx = token.indexOf('.');
    if (dotIdx === -1) return null;

    const payloadB64 = token.slice(0, dotIdx);
    const signature = token.slice(dotIdx + 1);

    const expected = crypto.createHmac('sha256', SECRET).update(payloadB64).digest('hex');
    if (!crypto.timingSafeEqual(Buffer.from(signature, 'hex'), Buffer.from(expected, 'hex'))) {
        return null;
    }

    try {
        const payload = JSON.parse(Buffer.from(payloadB64, 'base64').toString('utf8'));
        if (payload.exp && Date.now() > payload.exp) {
            return null; // expired
        }
        return payload;
    } catch {
        return null;
    }
}

/**
 * Handle telnet IAC negotiation by responding WONT/DONT to everything.
 */
function handleIAC(buffer) {
    const clean = [];
    const responses = [];
    let i = 0;

    while (i < buffer.length) {
        if (buffer[i] === 0xFF && i + 1 < buffer.length) {
            const cmd = buffer[i + 1];
            if ((cmd === 0xFB || cmd === 0xFD) && i + 2 < buffer.length) {
                // WILL (0xFB) -> respond DONT (0xFE)
                // DO (0xFD) -> respond WONT (0xFC)
                const response = cmd === 0xFB ? 0xFE : 0xFC;
                responses.push(Buffer.from([0xFF, response, buffer[i + 2]]));
                i += 3;
                continue;
            } else if ((cmd === 0xFC || cmd === 0xFE) && i + 2 < buffer.length) {
                // WONT/DONT - just consume
                i += 3;
                continue;
            } else if (cmd === 0xFA) {
                // Sub-negotiation - skip until IAC SE (0xFF 0xF0)
                let j = i + 2;
                while (j < buffer.length - 1) {
                    if (buffer[j] === 0xFF && buffer[j + 1] === 0xF0) {
                        j += 2;
                        break;
                    }
                    j++;
                }
                i = j;
                continue;
            } else if (cmd === 0xFF) {
                // Escaped 0xFF
                clean.push(0xFF);
                i += 2;
                continue;
            }
        }
        clean.push(buffer[i]);
        i++;
    }

    return { clean: Buffer.from(clean), responses };
}

// WebSocket server
const wss = new WebSocketServer({ host: '127.0.0.1', port: PORT });

console.log(`Door proxy listening on 127.0.0.1:${PORT}`);

wss.on('connection', (ws, req) => {
    const url = new URL(req.url, `http://127.0.0.1:${PORT}`);
    const token = url.searchParams.get('token');

    if (!token) {
        ws.close(4001, 'Missing token');
        return;
    }

    const payload = validateToken(token);
    if (!payload) {
        ws.close(4002, 'Invalid or expired token');
        return;
    }

    const { host, port } = payload;
    if (!host || !port) {
        ws.close(4003, 'Invalid token payload');
        return;
    }

    console.log(`Connecting to ${host}:${port} for door=${payload.door || 'unknown'}`);

    const telnet = createConnection({ host, port }, () => {
        console.log(`Telnet connected to ${host}:${port}`);
    });

    telnet.on('data', (data) => {
        const { clean, responses } = handleIAC(data);

        // Send IAC responses back to telnet
        for (const resp of responses) {
            telnet.write(resp);
        }

        // Convert CP437 to UTF-8 and send to browser
        if (clean.length > 0) {
            const utf8 = cp437ToUtf8(clean);
            if (ws.readyState === 1) { // OPEN
                ws.send(utf8);
            }
        }
    });

    telnet.on('error', (err) => {
        console.error(`Telnet error: ${err.message}`);
        if (ws.readyState === 1) {
            ws.close(4010, 'Telnet connection error');
        }
    });

    telnet.on('close', () => {
        console.log('Telnet connection closed');
        if (ws.readyState === 1) {
            ws.close(1000, 'Game session ended');
        }
    });

    ws.on('message', (data) => {
        // Convert UTF-8 from browser to CP437 for telnet
        const str = typeof data === 'string' ? data : data.toString('utf8');
        const cp437 = utf8ToCp437(str);
        if (!telnet.destroyed) {
            telnet.write(cp437);
        }
    });

    ws.on('close', () => {
        console.log('WebSocket closed');
        if (!telnet.destroyed) {
            telnet.destroy();
        }
    });

    ws.on('error', (err) => {
        console.error(`WebSocket error: ${err.message}`);
        if (!telnet.destroyed) {
            telnet.destroy();
        }
    });
});

// Graceful shutdown
function shutdown() {
    console.log('Shutting down...');
    wss.close(() => {
        process.exit(0);
    });
    // Force exit after 5 seconds
    setTimeout(() => process.exit(0), 5000);
}

process.on('SIGTERM', shutdown);
process.on('SIGINT', shutdown);
