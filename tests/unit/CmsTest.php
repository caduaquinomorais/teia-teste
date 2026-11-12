<?php

declare(strict_types=1);

use Teia\Cms\ConflictException;
use Teia\Cms\ContentRepository;
use Teia\Cms\PageRegistry;
use Teia\Cms\Schema;
use Teia\Database\Database;
use Teia\Database\Migrator;
use Teia\Tests\T;

use function Teia\Tests\tmpdir;

$registry = static fn (): PageRegistry => new PageRegistry(TEIA_ROOT . '/app/content');
$exists = static fn (string $ref): bool => in_array($ref, ['asset:foto-coworking', 'asset:foto-podcast', 'asset:foto-fotografia', 'asset:foto-musica', 'asset:banner-fundo'], true);
$validInput = static function (array $page): array {
    // Simula o formulário enviado com os valores padrão (estrutura de itens com chaves).
    $defaults = Schema::defaults($page);
    $keyed = static function (mixed $v) use (&$keyed): mixed {
        if (!is_array($v)) {
            return is_bool($v) ? ($v ? '1' : '0') : $v;
        }
        $out = [];
        foreach ($v as $k => $item) {
            $out[is_int($k) ? 'i' . $k : $k] = $keyed($item);
        }
        return $out;
    };
    return $keyed($defaults);
};
$repo = static function (): ContentRepository {
    $db = new Database(tmpdir() . '/t.sqlite');
    (new Migrator($db, TEIA_ROOT . '/app/migrations'))->migrate();
    return new ContentRepository($db);
};

return [
    'definições: todas as páginas carregam e são válidas' => function () use ($registry): void {
        T::same(['teia', 'layout', 'erro-404'], $registry()->slugs());
    },
    'padrões vêm do Figma (textos exatos)' => function () use ($registry): void {
        $d = Schema::defaults($registry()->get('teia'));
        T::same('Teia', $d['banner']['titulo']);
        T::same('Espaços públicos de trabalho', $d['banner']['tag']);
        T::same('Coworkings públicos', $d['espacos']['itens'][0]['titulo']);
        T::same('Estúdios de música', $d['espacos']['itens'][3]['titulo']);
        T::same('Encontre o Teia mais próximo de você', $d['mapa']['titulo']);
        T::same('https://app.adesampa.com.br/welcome', $d['geral']['link_agendamento']);
        T::same('Normas de uso dos espaços', $d['normas']['titulo']);
        $l = Schema::defaults($registry()->get('layout'));
        T::same(['Home', 'Sobre', 'Programas', 'Comunicação', 'Transparência'], array_column($l['cabecalho']['menu'], 'rotulo'));
        T::same('Atendimenro ao empreendedor', $l['rodape']['col_empreende']['links'][0]['rotulo'], 'grafia exata do Figma');
    },
    'nenhum Lorem ipsum é publicado' => function () use ($registry): void {
        foreach ($registry()->all() as $page) {
            T::notContains('lorem', mb_strtolower((string) json_encode(Schema::defaults($page))));
        }
    },
    'valores do banco sobrescrevem padrões; campo novo usa padrão; chave desconhecida é ignorada' => function () use ($registry): void {
        $page = $registry()->get('teia');
        $stored = ['banner' => ['titulo' => 'Novo título', 'campo_inexistente' => 'x'], 'secao_fantasma' => ['a' => 1]];
        $r = Schema::resolve($page, $stored);
        T::same('Novo título', $r['banner']['titulo']);
        T::same('Espaços públicos de trabalho', $r['banner']['tag'], 'campo sem valor salvo usa padrão');
        T::false(isset($r['banner']['campo_inexistente']));
        T::false(isset($r['secao_fantasma']));
    },
    'validação: formulário com os padrões é aceito' => function () use ($registry, $exists, $validInput): void {
        $page = $registry()->get('teia');
        [$data, $errors] = Schema::normalize($page, $validInput($page), [], [], $exists);
        T::same([], $errors);
        T::same('Teia', $data['banner']['titulo']);
        T::same(4, count($data['espacos']['itens']));
    },
    'validação: lista branca descarta campos e seções não declarados' => function () use ($registry, $exists, $validInput): void {
        $page = $registry()->get('teia');
        $input = $validInput($page);
        $input['banner']['__proto__'] = 'x';
        $input['inventada'] = ['a' => 'b'];
        $input['espacos']['itens']['i0']['html'] = '<script>';
        [$data] = Schema::normalize($page, $input, [], [], $exists);
        T::false(isset($data['banner']['__proto__']));
        T::false(isset($data['inventada']));
        T::false(isset($data['espacos']['itens'][0]['html']));
    },
    'validação: obrigatório, limite de tamanho, link inválido e âncora inválida' => function () use ($registry, $exists, $validInput): void {
        $page = $registry()->get('teia');
        $input = $validInput($page);
        $input['banner']['titulo'] = '   ';
        $input['banner']['texto'] = str_repeat('a', 201);
        $input['geral']['link_agendamento'] = 'javascript:alert(1)';
        $input['espacos']['itens']['i0']['ancora'] = 'Coworking Público';
        [, $errors] = Schema::normalize($page, $input, [], [], $exists);
        T::true(isset($errors['banner.titulo']), 'obrigatório');
        T::true(isset($errors['banner.texto']), 'máximo');
        T::true(isset($errors['geral.link_agendamento']), 'link');
        T::true(isset($errors['espacos.itens.i0.ancora']), 'âncora');
    },
    'validação: listas repetíveis respeitam mínimo e máximo; ordem preservada' => function () use ($registry, $exists, $validInput): void {
        $page = $registry()->get('teia');
        $input = $validInput($page);
        $input['espacos']['itens'] = ['zz' => $input['espacos']['itens']['i3'], 'aa' => $input['espacos']['itens']['i0']];
        [$data, $errors] = Schema::normalize($page, $input, [], [], $exists);
        T::same([], $errors);
        T::same(['Estúdios de música', 'Coworkings públicos'], array_column($data['espacos']['itens'], 'titulo'));
        $input['espacos']['itens'] = [];
        [, $errors] = Schema::normalize($page, $input, [], [], $exists);
        T::true(isset($errors['espacos.itens']), 'mínimo 1');
        $input = $validInput($page);
        for ($i = 0; $i < 9; $i++) {
            $input['espacos']['itens']['x' . $i] = $input['espacos']['itens']['i0'];
        }
        [, $errors] = Schema::normalize($page, $input, [], [], $exists);
        T::true(isset($errors['espacos.itens']), 'máximo 8');
    },
    'validação: referência de imagem inexistente ou forjada é recusada' => function () use ($registry, $exists, $validInput): void {
        $page = $registry()->get('teia');
        $input = $validInput($page);
        $input['banner']['fundo'] = 'asset:../../app.key';
        $input['espacos']['itens']['i0']['fotos']['i0']['imagem'] = 'media:' . str_repeat('0', 32);
        [, $errors] = Schema::normalize($page, $input, [], [], $exists);
        T::true(isset($errors['banner.fundo']));
        T::true(isset($errors['espacos.itens.i0.fotos.i0.imagem']));
    },
    'validação: upload processado substitui o valor do campo' => function () use ($registry, $validInput): void {
        $page = $registry()->get('teia');
        $ref = 'media:' . str_repeat('a', 32);
        [$data, $errors] = Schema::normalize($page, $validInput($page), ['banner.fundo' => $ref], [], fn ($r) => true);
        T::same([], $errors);
        T::same($ref, $data['banner']['fundo']);
    },
    'fieldAt localiza campos aninhados em listas' => function () use ($registry): void {
        $page = $registry()->get('teia');
        T::same('image', Schema::fieldAt($page, 'espacos.itens.k1.fotos.k2.imagem')['type']);
        T::same(null, Schema::fieldAt($page, 'espacos.itens.k1.inexistente'));
        T::same(null, Schema::fieldAt($page, 'espacos.itens.K1/../x.fotos'));
    },
    'histórico: cada publicação cria revisão; conflito gera exceção (409)' => function () use ($repo): void {
        $r = $repo();
        T::same(1, $r->save('teia', ['a' => 1], 0, null));
        T::same(2, $r->save('teia', ['a' => 2], 1, null));
        $ex = T::throws(ConflictException::class, fn () => $r->save('teia', ['a' => 3], 1, null));
        T::same(2, $ex->currentVersion);
        T::same(['a' => 2], $r->current('teia')['data']);
        T::same([2, 1], array_map('intval', array_column($r->revisions('teia'), 'version')));
    },
    'histórico: restaurar cria nova versão com o conteúdo antigo' => function () use ($repo): void {
        $r = $repo();
        $r->save('teia', ['a' => 1], 0, null);
        $r->save('teia', ['a' => 2], 1, null);
        T::same(3, $r->restore('teia', 1, 2, null));
        T::same(['a' => 1], $r->current('teia')['data']);
        T::same('Restauração da versão 1', $r->revisions('teia')[0]['note']);
    },
];
