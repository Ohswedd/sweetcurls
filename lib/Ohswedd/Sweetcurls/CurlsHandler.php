<?php 

namespace Ohswedd\Sweetcurls;

class CurlRequest {
    private $url;
    private $urls;
    private $headers;
    private $method;
    private $options;
    private $bearerToken;
    private $authorization;
    private $logsEnabled;
    private $successLogPath;
    private $errorLogPath;
    private $response;
    private $responseRequired;
    private $customOptions;
    private $customHeaders;
    private $asyncResponseSave;
    private $asyncResponseSaveSuccessPath;
    private $asyncResponseSaveErrorPath;
    private $uuidLog;

    public function __construct($url = null, $urls = null, $headers = array(), $method = 'GET', $options = array(), $responseRequired = true) {
        $this->url = $url;
        $this->urls = $urls;
        $this->headers = $headers;
        $this->method = $method;
        $this->options = $options;
        $this->responseRequired = $responseRequired;
        $this->logsEnabled = false;
        $this->uuidLog = uniqid();
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function setUrls($urls) {
        $this->urls = $urls;
    }

    public function returnResponse() {
        return $this->response;
    }

    public function setBearerToken($bearerToken) {
        $this->bearerToken = $bearerToken;
    }

    public function setAuthorization($authorization) {
        $this->authorization = $authorization;
    }

    public function setMethod($method) {
        $this->method = $method;
    }

    public function setOption($key, $value) {
        $this->options[$key] = $value;
    }

    public function setOptions($options) {
        $this->options = $options;
    }

    public function addHeader($key, $value) {
        $this->headers[$key] = $value;
    }

    public function addHeaders($headers) {
        $this->headers = array_merge($this->headers, $headers);
    }

    public function enableLogs() {
        $this->logsEnabled = true;
    }

    public function setSuccessLogPath($path) {
        $this->successLogPath = $path;
    }

    public function setErrorLogPath($path) {
        $this->errorLogPath = $path;
    }

    public function singleRequest() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        if ($this->bearerToken) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer {$this->bearerToken}"));
        }

        if ($this->authorization) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: {$this->authorization}"));
        }

        if ($this->method) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
        }

        if ($this->options) {
            foreach ($this->options as $key => $value) {
                curl_setopt($ch, $key, $value);
            }
        }

        if ($this->headers) {
            $headers = array();
            foreach ($this->headers as $key => $value) {
                $headers[] = "{$key}: {$value}";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
    
        $this->response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        curl_close($ch);
    
        if ($this->logsEnabled) {
            $logMessage = "{$this->uuidLog}:Success code:{$httpCode}:{$this->response}";
            $this->logMessage($logMessage, $this->successLogPath);
        }
    
        return $this->response;
    }
    
    public function multipleRequests() {
        $mh = curl_multi_init();
        $handles = array();
    
        foreach ($this->urls as $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
    
            if ($this->bearerToken) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer {$this->bearerToken}"));
            }
    
            if ($this->authorization) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: {$this->authorization}"));
            }
    
            if ($this->method) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
            }
    
            if ($this->options) {
                foreach ($this->options as $key => $value) {
                    curl_setopt($ch, $key, $value);
                }
            }
    
            if ($this->headers) {
                $headers = array();
                foreach ($this->headers as $key => $value) {
                    $headers[] = "{$key}: {$value}";
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
    
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }
    
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);
    
        $responses = array();
        foreach ($handles as $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
            if ($this->logsEnabled) {
                if ($httpCode >= 200 && $httpCode < 300) {
                    $logMessage = "{$this->uuidLog}:Success code:{$httpCode}:{$response}";
                    $this->logMessage($logMessage, $this->successLogPath);
                } else {
                    $logMessage = "{$this->uuidLog}:Error code:{$httpCode}:{$response}";
                    $this->logMessage($logMessage, $this->errorLogPath);
                }
            }
    
            $responses[] = $response;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
    
        curl_multi_close($mh);
    
        return $responses;
    }
    
    public function asyncSingleRequest() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
    
        if ($this->bearerToken) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer {$this->bearerToken}"));
        }
    
        if ($this->authorization) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: {$this->authorization}"));
        }
    
        if ($this->method) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
        }
    
        if ($this->options) {
            foreach ($this->options as $key => $value) {
                curl_setopt($ch, $key, $value);
            }
        }
        if ($this->headers) {
            $headers = array();
            foreach ($this->headers as $key => $value) {
                $headers[] = "{$key}: {$value}";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
    
        $uuid = $this->uuidLog;
        $successLogPath = $this->successLogPath;
        $errorLogPath = $this->errorLogPath;
    
        $responseFile = null;
        if ($this->responseFileRequired) {
            $responseFile = $this->getResponseFilePath($uuid, 'response');
            $fh = fopen($responseFile, 'w');
            curl_setopt($ch, CURLOPT_FILE, $fh);
        }
    
        $errorFile = null;
        if ($this->errorFileRequired) {
            $errorFile = $this->getResponseFilePath($uuid, 'error');
            $efh = fopen($errorFile, 'w');
            curl_setopt($ch, CURLOPT_STDERR, $efh);
        }
    
        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);
    
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);
    
        $response = curl_multi_getcontent($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if ($this->logsEnabled) {
            if ($httpCode >= 200 && $httpCode < 300) {
                $logMessage = "{$uuid}:Success code:{$httpCode}:{$response}";
                $this->logMessage($logMessage, $successLogPath);
            } else {
                $logMessage = "{$uuid}:Error code:{$httpCode}:{$response}";
                $this->logMessage($logMessage, $errorLogPath);
            }
        }
    
        curl_multi_remove_handle($mh, $ch);
        curl_multi_close($mh);
    
        if ($responseFile) {
            fclose($fh);
        }
    
        if ($errorFile) {
            fclose($efh);
        }
    
        if ($httpCode >= 200 && $httpCode < 300) {
            if ($this->responseRequired) {
                return $response;
            } else {
                return true;
            }
        } else {
            if ($this->responseRequired) {
                return $response;
            } else {
                return false;
            }
        }
    }
    
    public function asyncMultipleRequests() {
        $mh = curl_multi_init();
        $handles = array();
    
        foreach ($this->urls as $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
    
            if ($this->bearerToken) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer {$this->bearerToken}"));
            }
    
            if ($this->authorization) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: {$this->authorization}"));
            }
    
            if ($this->method) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
            }
    
            if ($this->options) {
                foreach ($this->options as $key => $value) {
                    curl_setopt($ch, $key, $value);
                }
            }
    
            if ($this->headers) {
                $headers = array();
                foreach ($this->headers as $key => $value) {
                    $headers[] = "{$key}: {$value}";
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER,            $headers);
            }
    
            $uuid = $this->getNewUuid();
            $successLogPath = $this->successLogPath;
            $errorLogPath = $this->errorLogPath;
    
            $responseFile = null;
            if ($this->responseFileRequired) {
                $responseFile = $this->getResponseFilePath($uuid, 'response');
                $fh = fopen($responseFile, 'w');
                curl_setopt($ch, CURLOPT_FILE, $fh);
            }
    
            $errorFile = null;
            if ($this->errorFileRequired) {
                $errorFile = $this->getResponseFilePath($uuid, 'error');
                $efh = fopen($errorFile, 'w');
                curl_setopt($ch, CURLOPT_STDERR, $efh);
            }
    
            curl_multi_add_handle($mh, $ch);
            $handles[$uuid] = $ch;
        }
    
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);
    
        foreach ($handles as $uuid => $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
            if ($this->logsEnabled) {
                if ($httpCode >= 200 && $httpCode < 300) {
                    $logMessage = "{$uuid}:Success code:{$httpCode}:{$response}";
                    $this->logMessage($logMessage, $successLogPath);
                } else {
                    $logMessage = "{$uuid}:Error code:{$httpCode}:{$response}";
                    $this->logMessage($logMessage, $errorLogPath);
                }
            }
    
            if ($responseFile) {
                fclose($fh);
            }
    
            if ($errorFile) {
                fclose($efh);
            }
    
            curl_multi_remove_handle($mh, $ch);
        }
    
        curl_multi_close($mh);
    
        return true;
    }
    
    public function getUuidLog($uuid, $returnCodeMessage = false) {
        $logPath = $this->successLogPath;
        if (!$returnCodeMessage) {
            $logPath = $this->errorLogPath;
        }
    
        if (file_exists($logPath)) {
            $fh = fopen($logPath, 'r');
            while ($line = fgets($fh)) {
                if (strpos($line, $uuid) === 0) {
                    $parts = explode(':', $line);
                    if (count($parts) > 3) {
                        $code = trim($parts[2]);
                        $message = trim($parts[3]);
                        if ($returnCodeMessage) {
                            return "{$code}:{$message}";
                        } else {
                            return $message;
                        }
                    }
                }
            }
            fclose($fh);
        }
    
        return false;
    }
    
    private function getResponseFilePath($uuid, $suffix) {
        $dirPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpcurl';
        if (!file_exists($dirPath)) {
            mkdir($dirPath);
        }
        return "{$dirPath}/{$uuid}.{$suffix}";
    }
    
    private function logMessage($message, $logPath) {
        $fh = fopen($logPath, 'a');
        fwrite($fh, $message . "\n");
        fclose($fh);
    }
    
    private function getNewUuid() {
        return uniqid('', true);
    }
}    
    