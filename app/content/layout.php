<?php

/*
 * Cabeçalho, submenu e rodapé — definição dos campos editáveis.
 * Reproduzem os componentes institucionais do Figma ("menu header", "sub-menus" TEIA e "Rodapé").
 *
 * IMPORTANTE (TODO-LINK): os links institucionais apontam para outros domínios/subdomínios
 * da ADE SAMPA que ainda não existem. Estão VAZIOS de propósito e devem ser preenchidos
 * pelo painel (Cabeçalho e rodapé) quando os endereços forem definidos.
 * Busque "TODO-LINK" no código para ver todos.
 */

defined('TEIA_ROOT') || exit;

$link = static fn (string $rotulo, string $url = ''): array => ['rotulo' => $rotulo, 'link' => $url];

$linkFields = [
    'rotulo' => ['type' => 'text', 'label' => 'Texto', 'required' => true, 'max' => 60],
    'link' => ['type' => 'link', 'label' => 'Link', 'help' => 'Vazio = item aparece sem link.'],
];

// As cores das barras de cada coluna são fixas no template (identidade visual), não editáveis.
$coluna = static fn (string $label, string $titulo, array $links) => [
    'type' => 'group',
    'label' => $label,
    'fields' => [
        'titulo' => ['type' => 'text', 'label' => 'Título da coluna', 'required' => true, 'max' => 40, 'default' => $titulo],
        'links' => ['type' => 'list', 'label' => 'Links', 'item_label' => 'rotulo', 'max_items' => 12, 'fields' => $linkFields, 'default' => $links],
    ],
];

return [
    'slug' => 'layout',
    'title' => 'Cabeçalho e rodapé',
    'description' => 'Menu superior, submenu do Teia e rodapé institucional.',
    'order' => 2,
    'sections' => [

        'cabecalho' => [
            'label' => 'Cabeçalho (menu superior)',
            'help' => 'Menu institucional da ADE SAMPA.',
            'fields' => [
                'logo_link' => ['type' => 'link', 'label' => 'Link do logo ADE SAMPA', 'help' => 'Normalmente a Home institucional.', 'default' => ''], // TODO-LINK: Home institucional
                'menu' => [
                    'type' => 'list',
                    'label' => 'Itens do menu',
                    'item_label' => 'rotulo',
                    'max_items' => 8,
                    'fields' => [
                        'rotulo' => ['type' => 'text', 'label' => 'Texto', 'required' => true, 'max' => 30],
                        'link' => ['type' => 'link', 'label' => 'Link', 'help' => 'Vazio = item sem link.'],
                        'seta' => ['type' => 'boolean', 'label' => 'Mostrar seta (menu com subitens)'],
                        'ativo' => ['type' => 'boolean', 'label' => 'Destacar como seção atual (sublinhado azul)'],
                    ],
                    'default' => [
                        ['rotulo' => 'Home', 'link' => '', 'seta' => false, 'ativo' => false],          // TODO-LINK
                        ['rotulo' => 'Sobre', 'link' => '', 'seta' => false, 'ativo' => false],         // TODO-LINK
                        ['rotulo' => 'Programas', 'link' => '', 'seta' => true, 'ativo' => true],       // TODO-LINK
                        ['rotulo' => 'Comunicação', 'link' => '', 'seta' => true, 'ativo' => false],    // TODO-LINK
                        ['rotulo' => 'Transparência', 'link' => '', 'seta' => false, 'ativo' => false], // TODO-LINK
                    ],
                ],
                'botao_texto' => ['type' => 'text', 'label' => 'Texto do botão azul', 'required' => true, 'max' => 30, 'default' => 'Agendamentos'],
                'botao_link' => ['type' => 'link', 'label' => 'Link do botão azul', 'required' => true, 'default' => 'https://app.adesampa.com.br/welcome'],
            ],
        ],

        'submenu' => [
            'label' => 'Submenu do Teia',
            'help' => 'Faixa cinza abaixo do menu, com o logo do Teia. Use #identificador para levar a um bloco da página.',
            'fields' => [
                'itens' => [
                    'type' => 'list',
                    'label' => 'Itens do submenu',
                    'item_label' => 'rotulo',
                    'max_items' => 10,
                    'fields' => $linkFields,
                    'default' => [
                        $link('Coworking', '#coworking'),
                        $link('Podcast', '#podcast'),
                        $link('Fotografia', '#fotografia'),
                        $link('Música', '#musica'),
                        $link('Mapa de unidades', '#mapa'),
                        $link('Dúvidas', '#duvidas'),
                        $link('Normas de uso', '#normas'),
                    ],
                ],
            ],
        ],

        'rodape' => [
            'label' => 'Rodapé',
            'help' => 'Rodapé institucional da ADE SAMPA.',
            'fields' => [
                'logo_link' => ['type' => 'link', 'label' => 'Link do logo ADE SAMPA', 'default' => ''], // TODO-LINK: Home institucional
                'texto' => ['type' => 'textarea', 'label' => 'Texto institucional', 'required' => true, 'max' => 300, 'rows' => 3, 'default' => 'Entidade vinculada à Prefeitura de São Paulo, por meio da Secretaria Municipal de Desenvolvimento Econômico e Trabalho (SMDET), dedicada à aceleração de negócios, inclusão produtiva e redução das desigualdades regionais.'],
                'redes_titulo' => ['type' => 'text', 'label' => 'Título das redes sociais', 'required' => true, 'max' => 30, 'default' => 'Redes sociais'],
                'redes' => [
                    'type' => 'group',
                    'label' => 'Redes sociais',
                    'help' => 'Ícone sem link aparece desabilitado até o endereço ser preenchido.',
                    'fields' => [
                        'instagram' => ['type' => 'link', 'label' => 'Instagram', 'default' => ''], // TODO-LINK
                        'linkedin' => ['type' => 'link', 'label' => 'LinkedIn', 'default' => ''],   // TODO-LINK
                        'youtube' => ['type' => 'link', 'label' => 'YouTube', 'default' => ''],     // TODO-LINK
                        'facebook' => ['type' => 'link', 'label' => 'Facebook', 'default' => ''],   // TODO-LINK
                    ],
                ],
                // Colunas de links (todas TODO-LINK). Textos copiados exatamente do Figma.
                'col_adesampa' => $coluna('Coluna “ADE SAMPA”', 'ADE SAMPA', [$link('Início'), $link('Sobre nós'), $link('Transparência')]),
                'col_empreende' => $coluna('Coluna “Sampa Empreende”', 'Sampa Empreende', [
                    $link('Atendimenro ao empreendedor'), // Grafia exatamente como no Figma (pendência P-18).
                    $link('Orientação empresarial'),
                    $link('Curso fábrica de negócios'),
                    $link('Curso de gestão de negócios'),
                    $link('Linhas de crédito'),
                    $link('Consultoria em gestão financeira'),
                    $link('Educação financeira'),
                ]),
                'col_tech' => $coluna('Coluna “Sampa Tech”', 'Sampa Tech', [$link('Aceleração Vai Tec')]),
                'col_games' => $coluna('Coluna “Sampa Games”', 'Sampa Games', [$link('Aceleração de estúdios'), $link('Curso de game dev'), $link('Hub de educação')]),
                'col_verde' => $coluna('Coluna “Sampa Verde”', 'Sampa Verde', [$link('Aceleração de negócios rurais'), $link('Aceleração de hortas'), $link('Aceleração de negócios verdes'), $link('Hub de inovação')]),
                'col_criativa' => $coluna('Coluna “Sampa Criativa”', 'Sampa Criativa', [$link('Aceleração de audiovisual'), $link('Aceleração de moda')]),
                'col_outros' => $coluna('Coluna “Outros programas”', 'Outros programas', [$link('Teia: coworkings e estúdios públicos'), $link('Sampa Ensina'), $link('Gestão Sampa'), $link('Data Sampa'), $link('Praça Victor Civita')]),
                'col_comunicacao' => $coluna('Coluna “Comunicação”', 'Comunicação', [$link('Notícias'), $link('Publicações'), $link('Manual de marca'), $link('Manual de redação'), $link('Contato')]),
                'legais' => [
                    'type' => 'list',
                    'label' => 'Links legais (linha inferior)',
                    'item_label' => 'rotulo',
                    'max_items' => 6,
                    'fields' => $linkFields,
                    'default' => [$link('Termos de uso'), $link('Privacidade (LGPD)'), $link('Acessibilidade')], // TODO-LINK
                ],
                'empresa' => ['type' => 'text', 'label' => 'Razão social (negrito)', 'required' => true, 'max' => 120, 'default' => 'ADE SAMPA – Agência São Paulo de Desenvolvimento'],
                'cnpj' => ['type' => 'text', 'label' => 'CNPJ', 'required' => true, 'max' => 60, 'default' => 'CNPJ: 21.154.061/0001-83'],
                'endereco' => ['type' => 'text', 'label' => 'Endereço da sede', 'required' => true, 'max' => 200, 'default' => 'Sede Administrativa: Rua Líbero Badaró, 425 - 11º andar - Centro Histórico, São Paulo - SP, CEP 01009-000'],
                'copyright' => ['type' => 'text', 'label' => 'Direitos reservados', 'required' => true, 'max' => 150, 'default' => '© 2026 ADE SAMPA • Prefeitura de São Paulo. Todos os direitos reservados.'],
            ],
        ],
    ],
];
