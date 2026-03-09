<?php

class SimpleSMTP {
    private $host;
    private $port;
    private $username;
    private $password;
    private $timeout = 30;
    private $debug = false;
    private $sock;

    public function __construct($host, $port, $username, $password, $debug = false) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->debug = $debug;
    }

    public function send($to, $subject, $body, $fromName = 'Listaria') {
        $from = $this->username;
        
        try {
            $this->connect();
            $this->auth();
            
            $this->sendCommand("MAIL FROM: <$from>");
            $this->sendCommand("RCPT TO: <$to>");
            $this->sendCommand("DATA", 354);
            
            $domain     = substr(strrchr($from, '@'), 1) ?: gethostname();
            $messageId  = '<' . time() . '.' . bin2hex(random_bytes(8)) . '@' . $domain . '>';
            $date       = date('r');

            $headers  = "Date: $date\r\n";
            $headers .= "Message-ID: $messageId\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: $fromName <$from>\r\n";
            $headers .= "Reply-To: $from\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "X-Mailer: Listaria-SMTP/1.0\r\n";
            
            $this->sendRaw($headers . "\r\n" . $body . "\r\n.");
            
            $this->sendCommand("QUIT");
            fclose($this->sock);
            
            return true;
        } catch (Exception $e) {
            if ($this->sock) {
                @fclose($this->sock);
            }
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }

    private function connect() {
        $socket_context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);
        
        $protocol = ($this->port == 465) ? 'ssl://' : 'tcp://';
        $this->sock = stream_socket_client($protocol . $this->host . ':' . $this->port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $socket_context);
        
        if (!$this->sock) {
            throw new Exception("Connection failed: $errno $errstr");
        }
        
        $this->readResponse();
        
        $this->sendCommand("EHLO " . gethostname());
    }

    private function auth() {
        if ($this->username && $this->password) {
            $this->sendCommand("AUTH LOGIN", 334);
            $this->sendCommand(base64_encode($this->username), 334);
            $this->sendCommand(base64_encode($this->password), 235);
        }
    }

    private function sendRaw($data) {
        fputs($this->sock, $data . "\r\n");
        $response = $this->readResponse();
        if (strpos($response, '250') !== 0) {
            throw new Exception("Unexpected response: $response");
        }
        return $response;
    }

    private function sendCommand($cmd, $expectedCode = null) {
        if ($this->debug) {
            error_log("CLIENT: $cmd");
        }
        
        fputs($this->sock, $cmd . "\r\n");
        $response = $this->readResponse();
        
        if ($expectedCode && strpos($response, (string)$expectedCode) !== 0) {
            throw new Exception("Unexpected response: $response");
        }
        
        return $response;
    }

    private function readResponse() {
        $response = "";
        while ($str = fgets($this->sock, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        
        if ($this->debug) {
            error_log("SERVER: $response");
        }
        
        return $response;
    }
}
?>
