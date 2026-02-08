<?php
/**
 * BBSLink Authentication Handler
 */

class BBSLinkAuth {
    private $host = "games.bbslink.net";
    private $syscode;
    private $authcode;
    private $schemecode;
    private $version = "0.3.1";

    public function __construct() {
        $this->syscode = $_ENV['BBSLINK_SYSCODE'] ?? '';
        $this->authcode = $_ENV['BBSLINK_AUTHCODE'] ?? '';
        $this->schemecode = $_ENV['BBSLINK_SCHEMECODE'] ?? '';
    }

    /**
     * Generate MD5 hash
     */
    private function getMD5Hash($s) {
        return md5($s);
    }

    /**
     * Generate random key
     */
    private function generateXKey() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $key = '';
        for ($i = 0; $i < 6; $i++) {
            $key .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return strtolower($key);
    }

    /**
     * Get authentication token from BBSLink
     */
    private function getToken($xkey) {
        $url = "http://{$this->host}/token.php?key={$xkey}";
        $token = file_get_contents($url);
        return trim($token);
    }

    /**
     * Authenticate with BBSLink
     */
    public function authenticate($doorcode, $usernumber, $screenrows = 24) {
        // Generate random key
        $xkey = $this->generateXKey();

        // Get token
        $token = $this->getToken($xkey);

        if (!$token) {
            return ['success' => false, 'error' => 'Failed to get token from BBSLink'];
        }

        // Build authentication headers
        $headers = [
            'X-User' => $usernumber,
            'X-Token' => $token,
            'X-Version' => $this->version,
            'X-Auth' => $this->getMD5Hash($this->authcode . $token),
            'X-Key' => $xkey,
            'X-Rows' => (string)$screenrows,
            'X-Door' => $doorcode,
            'X-Code' => $this->getMD5Hash($this->schemecode . $token),
            'X-System' => $this->syscode,
            'X-Type' => 'PHP'
        ];

        // Make authentication request
        $url = "http://{$this->host}/auth.php?key={$xkey}";

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => $this->formatHeaders($headers)
            ]
        ]);

        $status = file_get_contents($url, false, $context);
        $status = trim($status);

        if ($status === 'complete') {
            return [
                'success' => true,
                'host' => $this->host,
                'port' => 23,
                'xkey' => $xkey,
                'token' => $token,
                'headers' => $headers
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Authentication failed: ' . $status
            ];
        }
    }

    /**
     * Format headers for HTTP request
     */
    private function formatHeaders($headers) {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = "{$key}: {$value}";
        }
        return implode("\r\n", $formatted);
    }
}
