<?php
/**
 * Door Play - xterm.js Terminal View
 */

require_once __DIR__ . '/../door_manager.php';

$door_manager = new DoorManager();
$door_code = $_GET['door'] ?? '';
$game = $door_manager->getGame($door_code);

if (!$game) {
    set_flash('error', 'Invalid door game.');
    redirect('doors');
}

$csrf_token = generate_csrf_token();

ob_start();
?>

<div class="door-play-header">
    <h1><?= e($game['name']) ?></h1>
    <a href="/?page=doors" class="btn btn-small">Back to Games</a>
</div>

<div class="terminal-status" id="terminal-status">
    <span>
        <span class="status-dot connecting" id="status-dot"></span>
        <span id="status-text">Connecting...</span>
    </span>
    <div class="terminal-actions" id="terminal-actions" style="display: none;">
        <button class="btn btn-small" id="btn-disconnect" onclick="disconnect()">Disconnect</button>
        <button class="btn btn-small" id="btn-reconnect" onclick="reconnect()" style="display: none;">Reconnect</button>
    </div>
</div>

<div class="terminal-wrapper">
    <div id="terminal"></div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/css/xterm.min.css">
<link rel="stylesheet" href="/assets/css/door-terminal.css">

<script src="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/lib/xterm.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@xterm/addon-fit@0.10.0/lib/addon-fit.min.js"></script>
<script>
(function() {
    const DOOR_CODE = <?= json_encode($door_code) ?>;
    const CSRF_TOKEN = <?= json_encode($csrf_token) ?>;

    const statusDot = document.getElementById('status-dot');
    const statusText = document.getElementById('status-text');
    const terminalActions = document.getElementById('terminal-actions');
    const btnDisconnect = document.getElementById('btn-disconnect');
    const btnReconnect = document.getElementById('btn-reconnect');

    // DOS ANSI color palette
    const DOS_COLORS = {
        black:          '#000000',
        red:            '#aa0000',
        green:          '#00aa00',
        yellow:         '#aa5500',
        blue:           '#0000aa',
        magenta:        '#aa00aa',
        cyan:           '#00aaaa',
        white:          '#aaaaaa',
        brightBlack:    '#555555',
        brightRed:      '#ff5555',
        brightGreen:    '#55ff55',
        brightYellow:   '#ffff55',
        brightBlue:     '#5555ff',
        brightMagenta:  '#ff55ff',
        brightCyan:     '#55ffff',
        brightWhite:    '#ffffff',
        background:     '#000000',
        foreground:     '#aaaaaa',
        cursor:         '#aaaaaa',
    };

    // Initialize xterm.js
    const term = new Terminal({
        cols: 80,
        rows: 24,
        fontFamily: "'WebPlus_IBM_VGA_8x16', monospace",
        fontSize: 16,
        letterSpacing: 0,
        lineHeight: 1,
        cursorBlink: true,
        theme: DOS_COLORS,
        allowProposedApi: true,
    });

    const fitAddon = new FitAddon.FitAddon();
    term.loadAddon(fitAddon);
    term.open(document.getElementById('terminal'));

    // Don't auto-fit - keep 80x24 fixed, but center it
    let ws = null;

    function setStatus(state, text) {
        statusDot.className = 'status-dot ' + state;
        statusText.textContent = text;
    }

    function connect() {
        setStatus('connecting', 'Authenticating with BBSLink...');
        terminalActions.style.display = 'flex';
        btnDisconnect.style.display = '';
        btnReconnect.style.display = 'none';

        // POST to door_connect to get WebSocket URL + token
        const formData = new FormData();
        formData.append('door', DOOR_CODE);
        formData.append('csrf_token', CSRF_TOKEN);

        fetch('/?page=door_connect', {
            method: 'POST',
            body: formData,
        })
        .then(res => {
            if (!res.ok) return res.json().then(j => { throw new Error(j.error || 'Connection failed'); });
            return res.json();
        })
        .then(data => {
            setStatus('connecting', 'Opening terminal connection...');
            openWebSocket(data.ws_url, data.token);
        })
        .catch(err => {
            setStatus('disconnected', 'Error: ' + err.message);
            btnDisconnect.style.display = 'none';
            btnReconnect.style.display = '';
            term.writeln('\r\n\x1b[1;31mConnection failed: ' + err.message + '\x1b[0m');
        });
    }

    function openWebSocket(wsUrl, token) {
        ws = new WebSocket(wsUrl + '?token=' + encodeURIComponent(token));

        ws.onopen = function() {
            setStatus('connected', 'Connected');
            term.focus();
        };

        ws.onmessage = function(event) {
            term.write(event.data);
        };

        ws.onclose = function(event) {
            setStatus('disconnected', 'Disconnected');
            btnDisconnect.style.display = 'none';
            btnReconnect.style.display = '';
            if (event.code !== 1000) {
                term.writeln('\r\n\x1b[1;33mConnection closed: ' + (event.reason || 'Unknown reason') + '\x1b[0m');
            } else {
                term.writeln('\r\n\x1b[1;32mGame session ended.\x1b[0m');
            }
        };

        ws.onerror = function() {
            setStatus('disconnected', 'Connection error');
            btnDisconnect.style.display = 'none';
            btnReconnect.style.display = '';
        };
    }

    // Send keyboard input to WebSocket
    term.onData(function(data) {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(data);
        }
    });

    function disconnect() {
        if (ws) {
            ws.close();
            ws = null;
        }
    }

    function reconnect() {
        term.clear();
        connect();
    }

    // Expose for button onclick
    window.disconnect = disconnect;
    window.reconnect = reconnect;

    // Start connection
    connect();
})();
</script>

<?php
$content = ob_get_clean();
$page_title = e($game['name']);
$active_page = 'doors';
require __DIR__ . '/layout.php';
?>
