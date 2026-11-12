<?php

declare(strict_types=1);

namespace Teia\Cms;

defined('TEIA_ROOT') || exit;

use Teia\Database\Database;

/**
 * Uploads do painel.
 *
 * Imagens (JPEG, PNG, WebP):
 *  - limite de tamanho; tipo conferido por finfo E por getimagesize (devem concordar);
 *  - limite de dimensões (proteção contra "decompression bomb");
 *  - REPROCESSADAS com GD (remove metadados EXIF/GPS e qualquer carga escondida);
 *  - gravadas como WebP + fallback JPG (ou PNG se houver transparência);
 *  - nome aleatório; armazenadas fora de public/; servidas por rota própria.
 * PDFs:
 *  - limite de tamanho; finfo = application/pdf; assinatura %PDF-;
 *  - recusados se contiverem JavaScript, ações de execução ou arquivos embutidos;
 *  - servidos sempre como download (Content-Disposition: attachment) com CSP sandbox.
 * SVG é proibido em uploads.
 */
final class MediaService
{
    public const IMAGE_MIMES = ['image/jpeg' => IMAGETYPE_JPEG, 'image/png' => IMAGETYPE_PNG, 'image/webp' => IMAGETYPE_WEBP];

    public function __construct(
        private readonly Database $db,
        private readonly string $uploadDir,
        private readonly string $publicAssetsDir,
        private readonly int $maxImageBytes,
        private readonly int $maxPdfBytes,
        private readonly int $maxWidth,
        private readonly int $maxPixels,
    ) {
    }

    /**
     * Processa um arquivo de $_FILES. Retorna 'media:ID'.
     * @throws UploadException com mensagem segura para o usuário
     */
    public function store(array $file, string $kind, ?int $userId): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new UploadException(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'O arquivo é maior que o limite permitido.',
                UPLOAD_ERR_PARTIAL => 'O envio foi interrompido. Tente novamente.',
                default => 'Não foi possível receber o arquivo.',
            });
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp) || (PHP_SAPI !== 'cli' && !is_uploaded_file($tmp))) {
            throw new UploadException('Arquivo inválido.');
        }
        $name = $this->safeName((string) ($file['name'] ?? 'arquivo'));
        return $kind === 'file' ? $this->storePdf($tmp, $name, $userId) : $this->storeImage($tmp, $name, $userId);
    }

    public function storeImage(string $tmp, string $originalName, ?int $userId): string
    {
        $size = (int) filesize($tmp);
        if ($size <= 0 || $size > $this->maxImageBytes) {
            throw new UploadException('A imagem deve ter no máximo ' . $this->mb($this->maxImageBytes) . '.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        if (!isset(self::IMAGE_MIMES[$mime])) {
            throw new UploadException('Formato não permitido. Envie JPG, PNG ou WebP.');
        }
        $info = @getimagesize($tmp);
        if ($info === false || ($info[2] ?? null) !== self::IMAGE_MIMES[$mime]) {
            throw new UploadException('O arquivo não é uma imagem válida.');
        }
        [$width, $height] = $info;
        if ($width < 1 || $height < 1 || $width > 12000 || $height > 12000 || $width * $height > $this->maxPixels) {
            throw new UploadException('Dimensões da imagem fora do limite permitido.');
        }

        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png' => @imagecreatefrompng($tmp),
            'image/webp' => @imagecreatefromwebp($tmp),
        };
        if (!$src instanceof \GdImage) {
            throw new UploadException('Não foi possível processar a imagem.');
        }
        if ($mime === 'image/jpeg') {
            $src = $this->applyExifOrientation($src, $tmp);
            $width = imagesx($src);
            $height = imagesy($src);
        }

        $hasAlpha = $mime !== 'image/jpeg' && $this->hasTransparency($src);
        $targetW = min($width, $this->maxWidth);
        $targetH = (int) round($height * ($targetW / $width));
        $dst = imagecreatetruecolor($targetW, $targetH);
        if ($hasAlpha) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        } else {
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $width, $height);
        imagedestroy($src);

        $id = bin2hex(random_bytes(16));
        $this->ensureDir();
        $variants = [];
        if (function_exists('imagewebp')) {
            $webp = $id . '.webp';
            if (!imagewebp($dst, $this->uploadDir . '/' . $webp, 82)) {
                throw new UploadException('Falha ao gravar a imagem.');
            }
            $variants['webp'] = $webp;
        }
        if ($hasAlpha) {
            $fallback = $id . '.png';
            $ok = imagepng($dst, $this->uploadDir . '/' . $fallback, 6);
            $fallbackMime = 'image/png';
        } else {
            $fallback = $id . '.jpg';
            imageinterlace($dst, true);
            $ok = imagejpeg($dst, $this->uploadDir . '/' . $fallback, 85);
            $fallbackMime = 'image/jpeg';
        }
        imagedestroy($dst);
        if (!$ok) {
            throw new UploadException('Falha ao gravar a imagem.');
        }
        $variants['fallback'] = $fallback;

        $this->db->execute(
            'INSERT INTO media (id, kind, original_name, mime, size, width, height, variants, created_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$id, 'image', $originalName, $fallbackMime, (int) filesize($this->uploadDir . '/' . $fallback), $targetW, $targetH, json_encode($variants), gmdate('c'), $userId]
        );
        return 'media:' . $id;
    }

    public function storePdf(string $tmp, string $originalName, ?int $userId): string
    {
        $size = (int) filesize($tmp);
        if ($size <= 0 || $size > $this->maxPdfBytes) {
            throw new UploadException('O PDF deve ter no máximo ' . $this->mb($this->maxPdfBytes) . '.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        $head = (string) file_get_contents($tmp, false, null, 0, 1024);
        if ($mime !== 'application/pdf' || !str_contains($head, '%PDF-')) {
            throw new UploadException('Envie um arquivo PDF válido.');
        }
        $content = (string) file_get_contents($tmp);
        // Recusa PDFs ativos (heurística conservadora sobre os nomes de objeto do PDF).
        if (preg_match('#/(JavaScript|JS|Launch|EmbeddedFile|RichMedia|XFA|SubmitForm|ImportData)\b#', $content)) {
            throw new UploadException('O PDF contém conteúdo ativo (scripts, anexos ou formulários) e foi recusado. Exporte o arquivo novamente como PDF simples.');
        }
        $id = bin2hex(random_bytes(16));
        $this->ensureDir();
        $stored = $id . '.pdf';
        if (!copy($tmp, $this->uploadDir . '/' . $stored)) {
            throw new UploadException('Falha ao gravar o arquivo.');
        }
        if (!str_ends_with(strtolower($originalName), '.pdf')) {
            $originalName .= '.pdf';
        }
        $this->db->execute(
            'INSERT INTO media (id, kind, original_name, mime, size, width, height, variants, created_at, created_by) VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?)',
            [$id, 'pdf', $originalName, 'application/pdf', $size, json_encode(['file' => $stored]), gmdate('c'), $userId]
        );
        return 'media:' . $id;
    }

    public function find(string $id): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
            return null;
        }
        $row = $this->db->fetch('SELECT * FROM media WHERE id = ?', [$id]);
        if ($row === null) {
            return null;
        }
        $row['variants'] = json_decode((string) $row['variants'], true) ?: [];
        return $row;
    }

    /** Verifica se uma referência 'asset:..' ou 'media:..' existe. */
    public function refExists(string $ref): bool
    {
        if (preg_match('/^media:([a-f0-9]{32})$/', $ref, $m)) {
            return $this->find($m[1]) !== null;
        }
        if (preg_match('/^asset:([a-z0-9-]{1,60}\.pdf)$/', $ref, $m)) {
            return is_file($this->publicAssetsDir . '/docs/' . $m[1]);
        }
        if (preg_match('/^asset:([a-z0-9-]{1,60})$/', $ref, $m)) {
            return $this->assetFallback($m[1]) !== null;
        }
        return false;
    }

    /**
     * Fontes de uma imagem para <picture>.
     * @return array{webp:?string,fallback:string,width:?int,height:?int}|null caminhos relativos ao base path
     */
    public function imageSources(string $ref): ?array
    {
        if (preg_match('/^asset:([a-z0-9-]{1,60})$/', $ref, $m)) {
            $fallback = $this->assetFallback($m[1]);
            if ($fallback === null) {
                return null;
            }
            $webp = is_file($this->publicAssetsDir . '/images/' . $m[1] . '.webp') ? '/assets/images/' . $m[1] . '.webp' : null;
            $dims = @getimagesize($this->publicAssetsDir . '/images/' . $fallback);
            return ['webp' => $webp, 'fallback' => '/assets/images/' . $fallback, 'width' => $dims[0] ?? null, 'height' => $dims[1] ?? null];
        }
        if (preg_match('/^media:([a-f0-9]{32})$/', $ref, $m)) {
            $media = $this->find($m[1]);
            if ($media === null || $media['kind'] !== 'image') {
                return null;
            }
            $v = $media['variants'];
            return [
                'webp' => isset($v['webp']) ? '/midia/' . $v['webp'] : null,
                'fallback' => '/midia/' . $v['fallback'],
                'width' => $media['width'] !== null ? (int) $media['width'] : null,
                'height' => $media['height'] !== null ? (int) $media['height'] : null,
            ];
        }
        return null;
    }

    /** URL de download de um PDF (relativa ao base path). */
    public function fileUrl(string $ref): ?string
    {
        if (preg_match('/^asset:([a-z0-9-]{1,60}\.pdf)$/', $ref, $m)) {
            return is_file($this->publicAssetsDir . '/docs/' . $m[1]) ? '/assets/docs/' . $m[1] : null;
        }
        if (preg_match('/^media:([a-f0-9]{32})$/', $ref, $m)) {
            $media = $this->find($m[1]);
            return ($media !== null && $media['kind'] === 'pdf') ? '/midia/' . $media['variants']['file'] : null;
        }
        return null;
    }

    /** Caminho físico de um arquivo enviado, a partir do nome servido. */
    public function storedPath(string $fileName): ?string
    {
        if (!preg_match('/^[a-f0-9]{32}\.(webp|jpg|png|pdf)$/', $fileName)) {
            return null;
        }
        $path = $this->uploadDir . '/' . $fileName;
        return is_file($path) ? $path : null;
    }

    private function assetFallback(string $name): ?string
    {
        foreach (['jpg', 'png'] as $ext) {
            if (is_file($this->publicAssetsDir . '/images/' . $name . '.' . $ext)) {
                return $name . '.' . $ext;
            }
        }
        return null;
    }

    private function hasTransparency(\GdImage $img): bool
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $stepX = max(1, intdiv($w, 64));
        $stepY = max(1, intdiv($h, 64));
        for ($y = 0; $y < $h; $y += $stepY) {
            for ($x = 0; $x < $w; $x += $stepX) {
                if (((imagecolorat($img, $x, $y) >> 24) & 0x7F) > 0) {
                    return true;
                }
            }
        }
        return false;
    }

    private function applyExifOrientation(\GdImage $img, string $file): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($file);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($angle === 0) {
            return $img;
        }
        $rotated = imagerotate($img, $angle, 0);
        return $rotated instanceof \GdImage ? $rotated : $img;
    }

    private function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\p{L}\p{N}._ -]+/u', '', $name) ?? 'arquivo';
        $name = trim($name, ' .');
        return mb_substr($name !== '' ? $name : 'arquivo', 0, 120, 'UTF-8');
    }

    private function ensureDir(): void
    {
        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0750, true) && !is_dir($this->uploadDir)) {
            throw new UploadException('Diretório de uploads indisponível.');
        }
    }

    private function mb(int $bytes): string
    {
        return round($bytes / 1048576) . ' MB';
    }
}
