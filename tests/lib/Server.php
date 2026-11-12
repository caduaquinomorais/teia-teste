<?php

declare(strict_types=1);

namespace Teia\Tests;

/**
 * Servidor embutido do PHP para os testes HTTP, com diretório de dados temporário e isolado.
 */
final class Server
{
    /** @var resource|null */
    private $proc = null;
    public readonly string $dataDir;
    public readonly string $base;
    private int $port;

    public function __construct(?string $dataDir = null)
    {
        $this->dataDir = $dataDir ?? tmpdir('teia-dados-');
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0700, true);
        }
        $this->port = self::freePort();
        $this->base = 'http://127.0.0.1:' . $this->port;
        $this->start();
    }

    private function start(): void
    {
        $root = dirname(__DIR__, 2);
        $cmd = [PHP_BINARY];
        $ini = php_ini_loaded_file();
        if ($ini !== false) {
            $cmd[] = '-c';
            $cmd[] = $ini;
            $cmd[] = '-d';
            $cmd[] = 'extension_dir=' . ini_get('extension_dir');
        }
        array_push($cmd, '-d', 'display_errors=0', '-S', '127.0.0.1:' . $this->port, '-t', $root . '/public', $root . '/public/index.php');
        $env = getenv();
        $env['TEIA_DATA_DIR'] = $this->dataDir;
        $log = $this->dataDir . DIRECTORY_SEPARATOR . 'servidor-teste.log';
        $this->proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $root, $env);
        if (!is_resource($this->proc)) {
            throw new \RuntimeException('Falha ao iniciar o servidor de testes.');
        }
        $deadline = microtime(true) + 10;
        while (microtime(true) < $deadline) {
            $sock = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 0.2);
            if ($sock) {
                fclose($sock);
                return;
            }
            usleep(100_000);
        }
        throw new \RuntimeException('Servidor de testes não respondeu.');
    }

    public function stop(): void
    {
        if (is_resource($this->proc)) {
            proc_terminate($this->proc);
            proc_close($this->proc);
            $this->proc = null;
        }
    }

    public function __destruct()
    {
        $this->stop();
    }

    public function client(): Http
    {
        return new Http($this->base);
    }

    private static function freePort(): int
    {
        $sock = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $name = stream_socket_get_name($sock, false);
        fclose($sock);
        return (int) substr((string) strrchr((string) $name, ':'), 1);
    }
}
