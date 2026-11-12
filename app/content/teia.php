<?php

/*
 * Página do TEIA — definição dos campos editáveis.
 * Valores padrão copiados do Figma (frame "Teia", 212:2903).
 * Cada seção visual do Figma corresponde a uma seção aqui. A ordem e o layout
 * das seções são fixos no template (app/views/site/teia.php); o CMS altera só o conteúdo.
 */

defined('TEIA_ROOT') || exit;

$ancora = [
    'pattern' => '/^[a-z][a-z0-9-]{1,30}$/',
    'pattern_help' => 'use só letras minúsculas sem acento, números e hífen (ex.: coworking).',
];

$espaco = static fn (string $ancora, string $titulo, string $texto, string $foto, string $alt): array => [
    'ancora' => $ancora,
    'tag' => 'Uso 100% gratuito',
    'titulo' => $titulo,
    'texto' => $texto,
    'fotos' => [['imagem' => $foto, 'alt' => $alt]],
    'botao_mapa' => 'Mapa dos Teias',
    'botao_agendamento' => 'Faça seu agendamento',
];

return [
    'slug' => 'teia',
    'title' => 'Página do Teia',
    'description' => 'Conteúdo da página principal do Teia: banner, espaços, mapa, dúvidas e normas.',
    'order' => 1,
    'sections' => [

        'seo' => [
            'label' => 'Informações para buscadores',
            'help' => 'Aparecem na aba do navegador e nos resultados do Google. Não aparecem na página.',
            'fields' => [
                'titulo' => ['type' => 'text', 'label' => 'Título da aba', 'help' => 'Até 70 caracteres.', 'required' => true, 'max' => 70, 'default' => 'Teia — Coworkings e estúdios públicos | ADE SAMPA'],
                'descricao' => ['type' => 'textarea', 'label' => 'Descrição', 'help' => 'Resumo exibido pelo Google. Até 160 caracteres.', 'required' => true, 'max' => 160, 'rows' => 3, 'default' => 'Um programa gratuito do Sampa Empreende, ligado à Prefeitura de São Paulo e executado pela Ade Sampa, que ajuda pequenos empreendedores e MEIs.'],
            ],
        ],

        'geral' => [
            'label' => 'Links gerais',
            'help' => 'Destino usado por todos os botões “Faça seu agendamento” desta página.',
            'fields' => [
                'link_agendamento' => ['type' => 'link', 'label' => 'Link de agendamento', 'help' => 'Endereço completo do sistema de agendamento (https://...).', 'required' => true, 'default' => 'https://app.adesampa.com.br/welcome'],
            ],
        ],

        'banner' => [
            'label' => 'Banner (topo da página)',
            'help' => 'Faixa escura com o título “Teia”.',
            'fields' => [
                'trilha' => [
                    'type' => 'group',
                    'label' => 'Trilha de navegação (Início / Programas / Teia)',
                    'help' => 'Os links levam ao site institucional da ADE SAMPA. Deixe vazio enquanto o endereço não existir.',
                    'fields' => [
                        'inicio_texto' => ['type' => 'text', 'label' => 'Texto do 1º item', 'required' => true, 'max' => 30, 'default' => 'Início'],
                        'inicio_link' => ['type' => 'link', 'label' => 'Link do 1º item', 'default' => ''], // TODO-LINK: Home institucional da ADE SAMPA
                        'programas_texto' => ['type' => 'text', 'label' => 'Texto do 2º item', 'required' => true, 'max' => 30, 'default' => 'Programas'],
                        'programas_link' => ['type' => 'link', 'label' => 'Link do 2º item', 'default' => ''], // TODO-LINK: página de Programas da ADE SAMPA
                        'atual' => ['type' => 'text', 'label' => 'Página atual', 'required' => true, 'max' => 30, 'default' => 'Teia'],
                    ],
                ],
                'tag' => ['type' => 'text', 'label' => 'Etiqueta', 'help' => 'Texto curto exibido em maiúsculas acima do título.', 'required' => true, 'max' => 40, 'default' => 'Espaços públicos de trabalho'],
                'titulo' => ['type' => 'text', 'label' => 'Título', 'required' => true, 'max' => 30, 'default' => 'Teia'],
                'texto' => ['type' => 'textarea', 'label' => 'Texto', 'help' => 'Até 200 caracteres.', 'required' => true, 'max' => 200, 'rows' => 3, 'default' => 'Um programa gratuito do Sampa Empreende, ligado à Prefeitura de São Paulo e executado pela Ade Sampa, que ajuda pequenos empreendedores e MEIs.'],
                'fundo' => ['type' => 'image', 'label' => 'Imagem de fundo', 'help' => 'Imagem decorativa larga (recomendado 2880 × 1030 px).', 'required' => true, 'default' => 'asset:banner-fundo'],
            ],
        ],

        'espacos' => [
            'label' => 'Espaços (coworking e estúdios)',
            'help' => 'Cada item vira um bloco da página, alternando foto à direita e à esquerda automaticamente.',
            'fields' => [
                'itens' => [
                    'type' => 'list',
                    'label' => 'Espaços',
                    'help' => 'A ordem da lista é a ordem na página.',
                    'item_label' => 'titulo',
                    'min_items' => 1,
                    'max_items' => 8,
                    'fields' => [
                        'ancora' => ['type' => 'text', 'label' => 'Identificador do bloco', 'help' => 'Usado pelos links do submenu (ex.: “coworking” é acessado por #coworking).', 'required' => true, 'max' => 31] + $ancora,
                        'tag' => ['type' => 'text', 'label' => 'Etiqueta', 'required' => true, 'max' => 40, 'default' => 'Uso 100% gratuito'],
                        'titulo' => ['type' => 'text', 'label' => 'Título', 'required' => true, 'max' => 60],
                        'texto' => ['type' => 'textarea', 'label' => 'Descrição', 'help' => 'Aceita **negrito**, *itálico* e [link](https://...).', 'rich' => true, 'required' => true, 'max' => 400, 'rows' => 4],
                        'fotos' => [
                            'type' => 'list',
                            'label' => 'Fotos',
                            'help' => 'Com mais de uma foto, aparecem os pontos para trocar de foto.',
                            'item_label' => 'alt',
                            'min_items' => 1,
                            'max_items' => 6,
                            'fields' => [
                                'imagem' => ['type' => 'image', 'label' => 'Foto', 'help' => 'JPG, PNG ou WebP. Recomendado: 1086 × 784 px ou maior.', 'required' => true],
                                'alt' => ['type' => 'text', 'label' => 'Descrição da foto (acessibilidade)', 'help' => 'Descreva o que aparece na foto para pessoas que usam leitor de tela.', 'required' => true, 'max' => 160],
                            ],
                        ],
                        'botao_mapa' => ['type' => 'text', 'label' => 'Texto do botão do mapa', 'help' => 'Leva à seção do mapa.', 'required' => true, 'max' => 30, 'default' => 'Mapa dos Teias'],
                        'botao_agendamento' => ['type' => 'text', 'label' => 'Texto do botão de agendamento', 'required' => true, 'max' => 30, 'default' => 'Faça seu agendamento'],
                    ],
                    'default' => [
                        $espaco('coworking', 'Coworkings públicos', 'Espaço compartilhado focado no networking e produtividade. Conta com mesas de trabalho, Wi-Fi de alta velocidade, ambiente climatizado e salas de reunião disponíveis para uso dos empreendedores.', 'asset:foto-coworking', 'Coworking público do Teia com mesas compartilhadas e salas de reunião envidraçadas'),
                        $espaco('podcast', 'Estúdios de podcast', 'Salas com tratamento acústico, microfones padrão broadcast, fones de retorno e mesas de som multipista. Ideal para gravação de áudio, videocasts e entrevistas.', 'asset:foto-podcast', 'Estúdio de podcast do Teia com mesa, microfones e iluminação'),
                        $espaco('fotografia', 'Estúdios de fotografia', 'Equipado com fundo infinito nas cores branca, preta e verde (chroma key), além de kits de iluminação softbox contínua e tripés para celular e câmera. Perfeito para catálogos de e-commerce e produtos.', 'asset:foto-fotografia', 'Espaço do Teia com mural colorido e estações de trabalho'),
                        $espaco('musica', 'Estúdios de música', 'Infraestrutura voltada a músicos locais e produtores independentes. Isolamento acústico reforçado, monitores de áudio de precisão, interface de gravação profissional e bateria residente em unidades selecionadas.', 'asset:foto-musica', 'Estúdio de música do Teia com iluminação azul e tratamento acústico'),
                    ],
                ],
            ],
        ],

        'mapa' => [
            'label' => 'Mapa das unidades',
            'help' => 'Seção “Encontre o Teia mais próximo de você”.',
            'fields' => [
                'titulo' => ['type' => 'text', 'label' => 'Título', 'required' => true, 'max' => 60, 'default' => 'Encontre o Teia mais próximo de você'],
                'texto' => ['type' => 'textarea', 'label' => 'Texto', 'required' => true, 'max' => 300, 'rows' => 3, 'default' => 'Espaços estruturados com Wi-Fi veloz, computadores, salas de reunião compartilhadas e suporte técnico nas regiões periféricas e centrais de São Paulo.'],
                'botao' => ['type' => 'text', 'label' => 'Texto do botão', 'help' => 'O botão usa o “Link de agendamento”.', 'required' => true, 'max' => 30, 'default' => 'Faça seu agendamento'],
                'mapa_personalizado' => ['type' => 'link', 'embed' => 'google_maps', 'label' => 'Mapa personalizado do Google (opcional)', 'help' => 'Se vocês criarem um mapa no Google My Maps com todas as unidades, cole aqui o link de incorporação (https://www.google.com/maps/d/embed?mid=...). Vazio = o mapa mostra o endereço da unidade selecionada.', 'max' => 500, 'default' => ''],
                'rotulos' => [
                    'type' => 'group',
                    'label' => 'Nomes dos campos do painel de unidade',
                    'fields' => [
                        'unidade' => ['type' => 'text', 'label' => 'Rótulo “Unidade”', 'required' => true, 'max' => 40, 'default' => 'Unidade'],
                        'zona' => ['type' => 'text', 'label' => 'Rótulo “Zona”', 'required' => true, 'max' => 40, 'default' => 'ZONA'],
                        'classificacao' => ['type' => 'text', 'label' => 'Rótulo “Classificação”', 'required' => true, 'max' => 40, 'default' => 'Classificação'],
                        'status' => ['type' => 'text', 'label' => 'Rótulo “Atendimento”', 'required' => true, 'max' => 40, 'default' => 'Atendimento Funcionando?'],
                        'endereco' => ['type' => 'text', 'label' => 'Rótulo “Endereço”', 'required' => true, 'max' => 40, 'default' => 'Endereço'],
                    ],
                ],
                'unidades' => [
                    'type' => 'list',
                    'label' => 'Unidades do Teia',
                    'help' => 'Lista de endereços exibida ao lado do mapa. Com mais de uma unidade, o visitante escolhe qual ver no mapa.',
                    'item_label' => 'nome',
                    'min_items' => 1,
                    'max_items' => 60,
                    'fields' => [
                        'nome' => ['type' => 'text', 'label' => 'Nome da unidade', 'required' => true, 'max' => 80],
                        'zona' => ['type' => 'text', 'label' => 'Zona', 'max' => 40],
                        'classificacao' => ['type' => 'text', 'label' => 'Classificação', 'max' => 80],
                        'status' => ['type' => 'text', 'label' => 'Situação do atendimento', 'max' => 80],
                        'endereco' => ['type' => 'text', 'label' => 'Endereço completo', 'help' => 'Usado para posicionar o mapa. Inclua cidade e CEP.', 'required' => true, 'max' => 200],
                    ],
                    'default' => [
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
            ],
        ],

        'duvidas' => [
            'label' => 'Dúvidas frequentes',
            'help' => 'Perguntas sem resposta NÃO aparecem no site.',
            'fields' => [
                'titulo' => ['type' => 'text', 'label' => 'Título', 'required' => true, 'max' => 60, 'default' => 'Dúvidas frequentes'],
                'subtitulo' => ['type' => 'text', 'label' => 'Subtítulo', 'max' => 160, 'default' => 'Encontre respostas rápidas para as principais dúvidas sobre o Teia.'],
                'itens' => [
                    'type' => 'list',
                    'label' => 'Perguntas',
                    'help' => 'A numeração (1., 2., …) é feita automaticamente.',
                    'item_label' => 'pergunta',
                    'max_items' => 30,
                    'fields' => [
                        'pergunta' => ['type' => 'text', 'label' => 'Pergunta', 'required' => true, 'max' => 150],
                        'resposta' => ['type' => 'textarea', 'label' => 'Resposta', 'help' => 'Aceita **negrito**, *itálico* e [link](https://...). Linha em branco = novo parágrafo.', 'rich' => true, 'max' => 2000, 'rows' => 5],
                    ],
                    // PENDÊNCIA P-01: respostas não fornecidas (a 1ª está em Lorem ipsum no Figma e não é publicada).
                    'default' => [
                        ['pergunta' => 'O uso do Teia é totalmente gratuito?', 'resposta' => ''],
                        ['pergunta' => 'Preciso ter um CNPJ para utilizar?', 'resposta' => ''],
                        ['pergunta' => 'Como funciona a reserva de salas de reunião?', 'resposta' => ''],
                        ['pergunta' => 'É possível fazer uma reserva para o dia todo?', 'resposta' => ''],
                    ],
                ],
            ],
        ],

        'normas' => [
            'label' => 'Normas de uso dos espaços',
            'help' => 'Bloco escuro com os documentos para download.',
            'fields' => [
                'titulo' => ['type' => 'text', 'label' => 'Título', 'required' => true, 'max' => 60, 'default' => 'Normas de uso dos espaços'],
                'texto' => ['type' => 'textarea', 'label' => 'Texto', 'max' => 300, 'rows' => 3, 'default' => 'Para garantir um ambiente colaborativo produtivo e respeitoso para todos, solicitamos a leitura das regras vigentes antes de realizar seu agendamento.'],
                'documentos' => [
                    'type' => 'list',
                    'label' => 'Documentos',
                    'help' => 'O botão de download só aparece quando há um PDF enviado.',
                    'item_label' => 'titulo',
                    'max_items' => 6,
                    'fields' => [
                        'titulo' => ['type' => 'text', 'label' => 'Título', 'required' => true, 'max' => 60],
                        'descricao' => ['type' => 'textarea', 'label' => 'Descrição', 'max' => 250, 'rows' => 3],
                        'arquivo' => ['type' => 'file', 'label' => 'Arquivo PDF', 'help' => 'Somente PDF, até 15 MB.'],
                        'botao' => ['type' => 'text', 'label' => 'Texto do botão', 'required' => true, 'max' => 30, 'default' => 'Baixar PDF'],
                    ],
                    // PENDÊNCIA P-02: PDFs não fornecidos.
                    'default' => [
                        ['titulo' => 'Manual de conduta', 'descricao' => 'Diretrizes de convivência, consumo de alimentos, uso das mesas compartilhadas e cuidados básicos.', 'arquivo' => '', 'botao' => 'Baixar PDF'],
                        ['titulo' => 'Uso para gravações', 'descricao' => 'Termos de responsabilidade para uso dos estúdios (podcast, fotografia e música) e política de conservação dos equipamentos públicos.', 'arquivo' => '', 'botao' => 'Baixar PDF'],
                    ],
                ],
            ],
        ],
    ],
];
