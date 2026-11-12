<?php

/*
 * Conteúdo da página do TEIA (textos copiados do Figma, frame "Teia" 212:2903).
 *
 * COMO EDITAR: altere os textos entre aspas, salve e publique (git push). O layout,
 * a ordem das seções e as cores ficam em app/views/site/teia.php e public/assets/css/site.css.
 *
 * - Imagens: nome do arquivo em public/assets/images, sem extensão. É preciso existir
 *   <nome>.jpg (ou .png) e, de preferência, <nome>.webp.
 * - PDFs: nome do arquivo em public/assets/docs (ex.: 'manual-de-conduta.pdf') ou URL https.
 *   Vazio = o botão "Baixar PDF" não aparece.
 * - Links: http(s)://, mailto:, tel: ou #ancora. Vazio = o item aparece sem link (TODO-LINK).
 * - Texto: linha em branco = novo parágrafo. HTML não é interpretado (é exibido como texto).
 */

defined('TEIA_ROOT') || exit;

$espaco = static fn (string $ancora, string $titulo, string $texto, string $foto, string $alt): array => [
    'ancora' => $ancora,
    'tag' => 'Uso 100% gratuito',
    'titulo' => $titulo,
    'texto' => $texto,
    'fotos' => [['imagem' => $foto, 'alt' => $alt]], // Com 2+ fotos aparecem os pontos do carrossel.
    'botao_mapa' => 'Mapa dos Teias',
    'botao_agendamento' => 'Faça seu agendamento',
];

return [
    'seo' => [
        'titulo' => 'Teia — Coworkings e estúdios públicos | ADE SAMPA',
        'descricao' => 'Um programa gratuito do Sampa Empreende, ligado à Prefeitura de São Paulo e executado pela Ade Sampa, que ajuda pequenos empreendedores e MEIs.',
    ],

    'geral' => [
        // Destino de todos os botões "Faça seu agendamento".
        'link_agendamento' => 'https://app.adesampa.com.br/welcome',
    ],

    'banner' => [
        'trilha' => [
            'inicio_texto' => 'Início',
            'inicio_link' => '', // TODO-LINK: Home institucional da ADE SAMPA
            'programas_texto' => 'Programas',
            'programas_link' => '', // TODO-LINK: página de Programas da ADE SAMPA
            'atual' => 'Teia',
        ],
        'tag' => 'Espaços públicos de trabalho',
        'titulo' => 'Teia',
        'texto' => 'Um programa gratuito do Sampa Empreende, ligado à Prefeitura de São Paulo e executado pela Ade Sampa, que ajuda pequenos empreendedores e MEIs.',
        'fundo' => 'banner-fundo',
    ],

    'espacos' => [
        $espaco('coworking', 'Coworkings públicos', 'Espaço compartilhado focado no networking e produtividade. Conta com mesas de trabalho, Wi-Fi de alta velocidade, ambiente climatizado e salas de reunião disponíveis para uso dos empreendedores.', 'foto-coworking', 'Coworking público do Teia com mesas compartilhadas e salas de reunião envidraçadas'),
        $espaco('podcast', 'Estúdios de podcast', 'Salas com tratamento acústico, microfones padrão broadcast, fones de retorno e mesas de som multipista. Ideal para gravação de áudio, videocasts e entrevistas.', 'foto-podcast', 'Estúdio de podcast do Teia com mesa, microfones e iluminação'),
        $espaco('fotografia', 'Estúdios de fotografia', 'Equipado com fundo infinito nas cores branca, preta e verde (chroma key), além de kits de iluminação softbox contínua e tripés para celular e câmera. Perfeito para catálogos de e-commerce e produtos.', 'foto-fotografia', 'Espaço do Teia com mural colorido e estações de trabalho'),
        $espaco('musica', 'Estúdios de música', 'Infraestrutura voltada a músicos locais e produtores independentes. Isolamento acústico reforçado, monitores de áudio de precisão, interface de gravação profissional e bateria residente em unidades selecionadas.', 'foto-musica', 'Estúdio de música do Teia com iluminação azul e tratamento acústico'),
    ],

    'mapa' => [
        'titulo' => 'Encontre o Teia mais próximo de você',
        'texto' => 'Espaços estruturados com Wi-Fi veloz, computadores, salas de reunião compartilhadas e suporte técnico nas regiões periféricas e centrais de São Paulo.',
        'botao' => 'Faça seu agendamento',
        // Opcional: link de incorporação de um mapa do Google My Maps (https://www.google.com/maps/d/embed?mid=...).
        // Vazio = o mapa mostra o endereço da unidade selecionada.
        'mapa_personalizado' => '',
        'rotulos' => [
            'unidade' => 'Unidade',
            'zona' => 'ZONA',
            'classificacao' => 'Classificação',
            'status' => 'Atendimento Funcionando?',
            'endereco' => 'Endereço',
        ],
        // Com mais de uma unidade, aparece um seletor para o visitante escolher.
        'unidades' => [
            [
                'nome' => 'TEIA Parque Novo Mundo',
                'zona' => 'Norte I',
                'classificacao' => 'TEIA - Espaço de Trabalho',
                'status' => 'Atendimento funcionando',
                // PENDÊNCIA P-04: CEP cortado na imagem do Figma ("02160-0…").
                'endereco' => 'Av. Ernesto Augusto Lopes, 100 - Parque Vila Maria, São Paulo - SP',
            ],
        ],
    ],

    'duvidas' => [
        'titulo' => 'Dúvidas frequentes',
        'subtitulo' => 'Encontre respostas rápidas para as principais dúvidas sobre o Teia.',
        // Perguntas com resposta vazia NÃO aparecem no site.
        // PENDÊNCIA P-01: respostas não fornecidas (a 1ª está em Lorem ipsum no Figma e não é publicada).
        'itens' => [
            ['pergunta' => 'O uso do Teia é totalmente gratuito?', 'resposta' => ''],
            ['pergunta' => 'Preciso ter um CNPJ para utilizar?', 'resposta' => ''],
            ['pergunta' => 'Como funciona a reserva de salas de reunião?', 'resposta' => ''],
            ['pergunta' => 'É possível fazer uma reserva para o dia todo?', 'resposta' => ''],
        ],
    ],

    'normas' => [
        'titulo' => 'Normas de uso dos espaços',
        'texto' => 'Para garantir um ambiente colaborativo produtivo e respeitoso para todos, solicitamos a leitura das regras vigentes antes de realizar seu agendamento.',
        // PENDÊNCIA P-02: PDFs não fornecidos. Coloque os arquivos em public/assets/docs e informe o nome.
        'documentos' => [
            ['titulo' => 'Manual de conduta', 'descricao' => 'Diretrizes de convivência, consumo de alimentos, uso das mesas compartilhadas e cuidados básicos.', 'arquivo' => '', 'botao' => 'Baixar PDF'],
            ['titulo' => 'Uso para gravações', 'descricao' => 'Termos de responsabilidade para uso dos estúdios (podcast, fotografia e música) e política de conservação dos equipamentos públicos.', 'arquivo' => '', 'botao' => 'Baixar PDF'],
        ],
    ],
];
