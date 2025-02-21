<?php

namespace PlexDNS\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class AnycastDNS implements DnsHostingProviderInterface {
    private $client;
    private $apiKey;

    public function __construct($config) {
        $this->apiKey = $config['apikey'] ?? null;

        if (empty($this->apiKey)) {
            throw new Exception("API key cannot be empty");
        }

        $this->client = new Client([
            'base_uri' => 'https://api.anycastdns.app/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ],
        ]);
    }

    private function request($method, $endpoint, $params = []) {
        try {
            $options = [];
            if ($method === 'GET') {
                $options['query'] = $params;
            } else {
                $options['json'] = $params;
            }

            $response = $this->client->request($method, $endpoint, $options);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $message = $response ? $response->getBody()->getContents() : $e->getMessage();
            throw new Exception("HTTP request failed: " . $message);
        }
    }

    public function createDomain($domainName) {
        if (empty($domainName)) {
            throw new Exception("Domain name cannot be empty");
        }

        $params = ['name' => $domainName];
        return $this->request('POST', 'zones', $params);
    }

    public function listDomains() {
        return $this->request('GET', 'zones');
    }

    public function getDomain($domainId) {
        if (empty($domainId)) {
            throw new Exception("Domain ID cannot be empty");
        }

        return $this->request('GET', "zones/{$domainId}");
    }

    public function deleteDomain($domainId) {
        if (empty($domainId)) {
            throw new Exception("Domain ID cannot be empty");
        }

        return $this->request('DELETE', "zones/{$domainId}");
    }

    public function createRecord($domainId, $recordType, $name, $content, $ttl = 3600) {
        if (empty($domainId) || empty($recordType) || empty($name) || empty($content)) {
            throw new Exception("Domain ID, record type, name, and content cannot be empty");
        }

        $params = [
            'type' => strtoupper($recordType),
            'name' => $name,
            'content' => $content,
            'ttl' => $ttl,
        ];

        return $this->request('POST', "zones/{$domainId}/records", $params);
    }

    public function listRecords($domainId) {
        if (empty($domainId)) {
            throw new Exception("Domain ID cannot be empty");
        }

        return $this->request('GET', "zones/{$domainId}/records");
    }

    public function updateRecord($domainId, $recordId, $recordType, $name, $content, $ttl = 3600) {
        if (empty($domainId) || empty($recordId) || empty($recordType) || empty($name) || empty($content)) {
            throw new Exception("Domain ID, record ID, record type, name, and content cannot be empty");
        }

        $params = [
            'type' => strtoupper($recordType),
            'name' => $name,
            'content' => $content,
            'ttl' => $ttl,
        ];

        return $this->request('PUT', "zones/{$domainId}/records/{$recordId}", $params);
    }

    public function deleteRecord($domainId, $recordId) {
        if (empty($domainId) || empty($recordId)) {
            throw new Exception("Domain ID and record ID cannot be empty");
        }

        return $this->request('DELETE', "zones/{$domainId}/records/{$recordId}");
    }
}