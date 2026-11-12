<?php

declare(strict_types=1);

use Teia\Core\Paths;
use Teia\Database\Database;
use Teia\Database\Migrator;
use Teia\Http\HttpException;
use Teia\Http\Request;
use Teia\Http\Router;
use Teia\Tests\T;

use function Teia\Tests\tmpdir;

return [
    'roteador: parâmetros com regex' => function (): void {
        $r = new Router();
        $r->get('/admin/paginas/{slug:[a-z0-9-]{1,40}}', fn () => 'ok');
        $r->get('/midia/{file:[a-f0-9]{32}\.(?:webp|jpg)}', fn () => 'm');
        $m = $r->match('GET', '/admin/paginas/teia');
        T::same(['slug' => 'teia'], $m['params']);
        $m = $r->match('GET', '/midia/' . str_repeat('a', 32) . '.webp');
        T::same(str_repeat('a', 32) . '.webp', $m['params']['file']);
    },
    'roteador: 404 para caminho inválido (regex não aceita ../)' => function (): void {
        $r = new Router();
        $r->get('/admin/paginas/{slug:[a-z0-9-]{1,40}}', fn () => 'ok');
        $ex = T::throws(HttpException::class, fn () => $r->match('GET', '/admin/paginas/../../etc'));
        T::same(404, $ex->status);
        $ex = T::throws(HttpException::class, fn () => $r->match('GET', '/admin/paginas/TEIA'));
        T::same(404, $ex->status);
    },
    'roteador: 405 com cabeçalho Allow' => function (): void {
        $r = new Router();
        $r->post('/admin/sair', fn () => 'x');
        $ex = T::throws(HttpException::class, fn () => $r->match('GET', '/admin/sair'));
        T::same(405, $ex->status);
        T::same('POST', $ex->headers['Allow']);
    },
    'request: detecção do base path (subpasta /teia)' => function (): void {
        T::same('', Request::detectBasePath('/index.php'));
        T::same('/teia', Request::detectBasePath('/teia/public/index.php'));
        T::same('/teia', Request::detectBasePath('/teia/index.php'));
        T::same('/', Request::stripBasePath('/teia', '/teia'));
        T::same('/admin', Request::stripBasePath('/teia/admin', '/teia'));
        T::same('/admin', Request::stripBasePath('/teia/public/admin', '/teia'));
        T::same('/teiax', Request::stripBasePath('/teiax', '/teia'));
    },
    'paths: dados dentro do document root são detectados' => function (): void {
        $root = tmpdir();
        mkdir("$root/www/teia/public", 0777, true);
        T::true(Paths::isInside("$root/www/teia/dados", "$root/www"));
        T::false(Paths::isInside("$root/dados", "$root/www"));
        T::false(Paths::isInside("$root/www2", "$root/www"), 'prefixo não é contenção');
    },
    'paths: projeto em www/teia usa dados fora do document root' => function (): void {
        $root = tmpdir();
        mkdir("$root/www/teia", 0777, true);
        $prev = getenv('TEIA_DATA_DIR');
        putenv('TEIA_DATA_DIR');
        try {
            $dir = Paths::resolveDataDir("$root/www/teia", "$root/www");
            T::same(Paths::normalize("$root/teia-ade-sampa-data"), Paths::normalize($dir));
            $dir = Paths::resolveDataDir("$root/apps/teia", "$root/www");
            T::same(Paths::normalize("$root/apps/teia-ade-sampa-data"), Paths::normalize($dir));
        } finally {
            if ($prev !== false) {
                putenv('TEIA_DATA_DIR=' . $prev);
            }
        }
    },
    'banco: foreign_keys ativo e transações aninhadas com savepoint' => function (): void {
        $db = new Database(tmpdir() . '/t.sqlite');
        T::same(1, (int) $db->fetchValue('PRAGMA foreign_keys'));
        $db->execute('CREATE TABLE x (v INTEGER)');
        $db->transaction(function (Database $db): void {
            $db->execute('INSERT INTO x VALUES (1)');
            try {
                $db->transaction(function (Database $db): void {
                    $db->execute('INSERT INTO x VALUES (2)');
                    T::same(2, $db->transactionDepth());
                    throw new RuntimeException('falha interna');
                });
            } catch (RuntimeException) {
            }
            $db->execute('INSERT INTO x VALUES (3)');
        });
        T::same([1, 3], array_map('intval', array_column($db->fetchAll('SELECT v FROM x ORDER BY v'), 'v')));
        T::same(0, $db->transactionDepth());
    },
    'banco: erro na transação externa desfaz tudo' => function (): void {
        $db = new Database(tmpdir() . '/t.sqlite');
        $db->execute('CREATE TABLE x (v INTEGER)');
        T::throws(RuntimeException::class, fn () => $db->transaction(function (Database $db): void {
            $db->execute('INSERT INTO x VALUES (1)');
            throw new RuntimeException('x');
        }));
        T::same(0, (int) $db->fetchValue('SELECT COUNT(*) FROM x'));
    },
    'migrações: aplicam em ordem, gravam user_version e chamam backup antes' => function (): void {
        $dir = tmpdir();
        mkdir("$dir/m");
        file_put_contents("$dir/m/001_a.sql", 'CREATE TABLE a (id INTEGER);');
        $db = new Database("$dir/t.sqlite");
        $mig = new Migrator($db, "$dir/m");
        $called = false;
        T::same([1], $mig->migrate(function () use (&$called): void { $called = true; }));
        T::false($called, 'banco novo não precisa de backup');
        file_put_contents("$dir/m/002_b.sql", 'CREATE TABLE b (id INTEGER);');
        T::same([2], $mig->migrate(function (int $from, int $to) use (&$called): void { $called = [$from, $to]; }));
        T::same([1, 2], $called);
        T::same(2, $db->userVersion());
        T::same([], $mig->migrate());
    },
    'migração real 001 cria as tabelas esperadas' => function (): void {
        $db = new Database(tmpdir() . '/t.sqlite');
        (new Migrator($db, TEIA_ROOT . '/app/migrations'))->migrate();
        $tables = array_column($db->fetchAll("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"), 'name');
        T::same(['audit_log', 'media', 'page_permissions', 'page_revisions', 'pages', 'rate_limits', 'settings', 'users'], $tables);
    },
];
