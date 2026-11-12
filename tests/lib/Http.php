<?php

declare(strict_types=1);

namespace Teia\Tests;

/** Cliente HTTP de teste (cURL) com cookies próprios e acesso aos cabeçalhos brutos. */
final class Http
{
    /** @var array<string,string> */
    public array $cookies = [];

    public function __construct(public readonly string $base)
    {
    }

    public function get(string $path, array $headers = []): Res
    {
        return $this->request('GET', $path, null, $headers);
    }

    /** @param array<string,mixed> $fields  (use \CURLFile para arquivos) */
    public function post(string $path, array $fields = [], array $headers = [], bool $multipart = false): Res
    {
        return $this->request('POST', $path, $multipart ? $fields : http_build_query($fields), $headers);
    }

    public function request(string $method, string $path, mixed $body, array $headers = []): Res
    {
        $ch = curl_init($this->base . $path);
        $sendHeaders = [];
        foreach ($headers as $k => $v) {
            $sendHeaders[] = $k . ': ' . $v;
        }
        if ($this->cookies !== []) {
            $pairs = [];
            foreach ($this->cookies as $k => $v) {
                $pairs[] = $k . '=' . $v;
            }
            $sendHeaders[] = 'Cookie: ' . implode('; ', $pairs);
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => $sendHeaders,
            CURLOPT_TIMEOUT => 60,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = (string) curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        $rawHeaders = substr($raw, 0, $headerSize);
        $res = new Res($status, $rawHeaders, substr($raw, $headerSize));
        foreach ($res->setCookies() as $cookie) {
            [$pair] = explode(';', $cookie, 2);
            [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
            if ($value === '' || $value === 'deleted' || stripos($cookie, 'expires=Thu, 01 Jan 1970') !== false || preg_match('/expires=[^;]*19\d\d/i', $cookie)) {
                unset($this->cookies[trim($name)]);
            } else {
                $this->cookies[trim($name)] = trim($value);
            }
        }
        return $res;
    }
}

final class Res
{
    public function __construct(
        public readonly int $status,
        public readonly string $rawHeaders,
        public readonly string $body,
    ) {
    }

    public function header(string $name): ?string
    {
        if (preg_match('/^' . preg_quote($name, '/') . ':\s*(.*)$/mi', $this->rawHeaders, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /** @return string[] */
    public function setCookies(): array
    {
        preg_match_all('/^Set-Cookie:\s*(.*)$/mi', $this->rawHeaders, $m);
        return array_map('trim', $m[1]);
    }

    public function location(): string
    {
        return (string) $this->header('Location');
    }
}
