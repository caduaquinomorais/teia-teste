<?php

declare(strict_types=1);

use Teia\Backup\BackupException;
use Teia\Backup\BackupService;
use Teia\Cms\MediaService;
use Teia\Cms\UploadException;
use Teia\Database\Database;
use Teia\Database\Migrator;
use Teia\Security\PasswordPolicy;
use Teia\Tests\T;

use function Teia\Tests\tmpdir;

/** Ambiente isolado: banco migrado + pastas de dados. */
$env = static function (bool $withAdmin = true): array {
    $dir = tmpdir();
    foreach (['database', 'uploads', 'backups', 'tmp'] as $d) {
        mkdir("$dir/$d");
    }
    $db = new Database("$dir/database/teia.sqlite");
    (new Migrator($db, TEIA_ROOT . '/app/migrations'))->migrate();
    if ($withAdmin) {
        $db->execute("INSERT INTO users (username, name, password_hash, role, created_at, updated_at) VALUES ('adm', 'Adm', ?, 'admin', 'x', 'x')", [PasswordPolicy::hash('frase longa de teste')]);
    }
    $media = new MediaService($db, "$dir/uploads", TEIA_ROOT . '/public/assets', 8 << 20, 15 << 20, 2400, 40_000_000);
    $backup = new BackupService($db, "$dir/database/teia.sqlite", "$dir/uploads", "$dir/backups", "$dir/tmp", 1);
    return compact('dir', 'db', 'media', 'backup');
};

/** JPEG real com metadado EXIF (comentário) injetado. */
$jpegWithExif = static function (string $path): void {
    $im = imagecreatetruecolor(40, 30);
    imagefill($im, 0, 0, imagecolorallocate($im, 200, 50, 50));
    imagejpeg($im, $path, 90);
    $data = (string) file_get_contents($path);
    $exif = "Exif\0\0" . 'SEGREDO-GPS-LATITUDE-23.55';
    $app1 = "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif;
    file_put_contents($path, substr($data, 0, 2) . $app1 . substr($data, 2));
};

return [
    'upload: JPEG é reprocessado (metadados removidos) e gera WebP + JPG com nome aleatório' => function () use ($env, $jpegWithExif): void {
        ['dir' => $dir, 'media' => $media] = $env();
        $src = "$dir/foto.jpg";
        $jpegWithExif($src);
        T::contains('SEGREDO-GPS', (string) file_get_contents($src));
        $ref = $media->storeImage($src, 'minha foto.jpg', null);
        T::matches('/^media:[a-f0-9]{32}$/', $ref);
        $id = substr($ref, 6);
        T::true(is_file("$dir/uploads/$id.jpg"));
        T::true(is_file("$dir/uploads/$id.webp"));
        T::notContains('SEGREDO-GPS', (string) file_get_contents("$dir/uploads/$id.jpg"), 'EXIF removido');
        T::same(null, $media->imageSources('media:' . str_repeat('f', 32)));
        T::same('/midia/' . $id . '.webp', $media->imageSources($ref)['webp']);
    },
    'upload: arquivo PHP disfarçado de JPG é recusado' => function () use ($env): void {
        ['dir' => $dir, 'media' => $media] = $env();
        file_put_contents("$dir/shell.jpg", "<?php system(\$_GET['c']); ?>");
        T::throws(UploadException::class, fn () => $media->storeImage("$dir/shell.jpg", 'shell.jpg', null));
    },
    'upload: poliglota (GIF com PHP) é recusado (GIF não permitido)' => function () use ($env): void {
        ['dir' => $dir, 'media' => $media] = $env();
        file_put_contents("$dir/poly.jpg", "GIF89a\x01\x00\x01\x00\x00\x00\x00;<?php echo 1; ?>");
        T::throws(UploadException::class, fn () => $media->storeImage("$dir/poly.jpg", 'poly.jpg', null));
    },
    'upload: SVG é proibido (mesmo com extensão de imagem)' => function () use ($env): void {
        ['dir' => $dir, 'media' => $media] = $env();
        file_put_contents("$dir/x.png", '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>');
        T::throws(UploadException::class, fn () => $media->storeImage("$dir/x.png", 'x.png', null));
    },
    'upload: imagem acima do limite de tamanho é recusada' => function () use ($env): void {
        ['dir' => $dir, 'db' => $db] = $env();
        $small = new MediaService($db, "$dir/uploads", TEIA_ROOT . '/public/assets', 1000, 1000, 2400, 40_000_000);
        T::throws(UploadException::class, fn () => $small->storeImage(TEIA_ROOT . '/public/assets/images/foto-coworking.jpg', 'f.jpg', null));
    },
    'upload: dimensões exageradas (decompression bomb) são recusadas' => function () use ($env): void {
        ['dir' => $dir, 'db' => $db] = $env();
        $tiny = new MediaService($db, "$dir/uploads", TEIA_ROOT . '/public/assets', 8 << 20, 1, 2400, 1000);
        T::throws(UploadException::class, fn () => $tiny->storeImage(TEIA_ROOT . '/public/assets/images/foto-musica.jpg', 'f.jpg', null));
    },
    'upload: PDF válido é aceito; PDF com JavaScript é recusado; texto com .pdf é recusado' => function () use ($env): void {
        ['dir' => $dir, 'media' => $media] = $env();
        $pdf = "%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n2 0 obj << /Type /Pages /Kids [] /Count 0 >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n";
        file_put_contents("$dir/ok.pdf", $pdf);
        T::matches('/^media:[a-f0-9]{32}$/', $media->storePdf("$dir/ok.pdf", 'Manual.pdf', null));
        file_put_contents("$dir/js.pdf", str_replace('/Type /Catalog', '/Type /Catalog /OpenAction << /S /JavaScript /JS (app.alert(1)) >>', $pdf));
        T::throws(UploadException::class, fn () => $media->storePdf("$dir/js.pdf", 'js.pdf', null));
        file_put_contents("$dir/txt.pdf", 'apenas texto');
        T::throws(UploadException::class, fn () => $media->storePdf("$dir/txt.pdf", 'txt.pdf', null));
    },
    'upload: nome servido não aceita caminho (path traversal)' => function () use ($env): void {
        ['media' => $media] = $env();
        T::same(null, $media->storedPath('../app.key'));
        T::same(null, $media->storedPath(str_repeat('a', 32) . '.php'));
    },
    'backup: cria ZIP com banco, uploads e manifesto com hashes' => function () use ($env, $jpegWithExif): void {
        ['dir' => $dir, 'media' => $media, 'backup' => $backup] = $env();
        $jpegWithExif("$dir/f.jpg");
        $media->storeImage("$dir/f.jpg", 'f.jpg', null);
        $name = $backup->create('manual');
        $zip = new ZipArchive();
        $zip->open("$dir/backups/$name");
        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        T::same('teia-ade-sampa', $manifest['app']);
        T::same(1, $manifest['schema_version']);
        T::same(3, count($manifest['files']), 'banco + webp + jpg');
        $zip->close();
    },
    'backup: alteração + restauração devolve o estado anterior e cria backup de segurança' => function () use ($env): void {
        ['dir' => $dir, 'db' => $db, 'backup' => $backup] = $env();
        $db->execute("INSERT INTO settings (key, value) VALUES ('marca', 'antes')");
        $name = $backup->create('manual');
        $db->execute("UPDATE settings SET value = 'depois' WHERE key = 'marca'");
        $reopened = null;
        $safety = $backup->restore("$dir/backups/$name", function () use (&$db): void { $db = null; gc_collect_cycles(); }, function () use (&$reopened, $dir): void {
            $reopened = new Database("$dir/database/teia.sqlite");
        });
        T::same('antes', $reopened->fetchValue("SELECT value FROM settings WHERE key = 'marca'"));
        T::matches('/-seguranca-/', $safety);
        T::true(is_file("$dir/backups/$safety"));
    },
    'restauração: Zip Slip (../) é recusado' => function () use ($env): void {
        ['dir' => $dir, 'backup' => $backup] = $env();
        $zip = new ZipArchive();
        $zip->open("$dir/mal.zip", ZipArchive::CREATE);
        $zip->addFromString('manifest.json', '{}');
        $zip->addFromString('../../public/shell.php', '<?php echo 1;');
        $zip->close();
        $ex = T::throws(BackupException::class, fn () => $backup->validate("$dir/mal.zip"));
        T::contains('não permitido', $ex->getMessage());
        T::false(is_file(dirname($dir) . '/public/shell.php'));
    },
    'restauração: arquivo adulterado (hash diferente) é recusado' => function () use ($env): void {
        ['dir' => $dir, 'backup' => $backup] = $env();
        $name = $backup->create('manual');
        $zip = new ZipArchive();
        $zip->open("$dir/backups/$name");
        $zip->addFromString('database/teia.sqlite', 'conteudo trocado');
        $zip->close();
        $ex = T::throws(BackupException::class, fn () => $backup->validate("$dir/backups/$name"));
        T::contains('integridade', $ex->getMessage());
    },
    'restauração: versão de esquema mais nova é recusada' => function () use ($env): void {
        ['dir' => $dir, 'db' => $db, 'backup' => $backup] = $env();
        $db->setUserVersion(9);
        $name = $backup->create('manual');
        $db->setUserVersion(1);
        $ex = T::throws(BackupException::class, fn () => $backup->validate("$dir/backups/$name"));
        T::contains('incompatível', $ex->getMessage());
    },
    'restauração: backup sem administrador ativo é recusado' => function () use ($env): void {
        ['dir' => $dir, 'backup' => $backup] = $env(false);
        $name = $backup->create('manual');
        $ex = T::throws(BackupException::class, fn () => $backup->validate("$dir/backups/$name"));
        T::contains('administrador ativo', $ex->getMessage());
    },
    'restauração: ZIP que não é do Teia é recusado' => function () use ($env): void {
        ['dir' => $dir, 'backup' => $backup] = $env();
        file_put_contents("$dir/x.zip", 'não é zip');
        T::throws(BackupException::class, fn () => $backup->validate("$dir/x.zip"));
    },
    'retenção: mantém só os N backups automáticos mais recentes' => function () use ($env): void {
        ['backup' => $backup] = $env();
        for ($i = 0; $i < 4; $i++) {
            $backup->create('auto');
            usleep(1_100_000);
        }
        $backup->create('manual');
        T::same(2, $backup->prune(2));
        $types = array_column($backup->list(), 'type');
        T::same(2, count(array_filter($types, fn ($t) => $t === 'auto')));
        T::same(1, count(array_filter($types, fn ($t) => $t === 'manual')));
    },
];
