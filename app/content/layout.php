<?php

/*
 * Conteúdo do cabeçalho, submenu e rodapé (componentes institucionais do Figma).
 *
 * IMPORTANTE (TODO-LINK): os links institucionais apontam para outros domínios/subdomínios
 * da ADE SAMPA que ainda não existem. Estão VAZIOS de propósito: o item aparece sem link.
 * Preencha aqui quando os endereços forem definidos. Busque "TODO-LINK" no projeto.
 *
 * As cores das barras das colunas do rodapé são identidade visual e ficam no template.
 */

defined('TEIA_ROOT') || exit;

$link = static fn (string $rotulo, string $url = ''): array => ['rotulo' => $rotulo, 'link' => $url];
$coluna = static fn (string $titulo, array $links): array => ['titulo' => $titulo, 'links' => $links];

return [
    'cabecalho' => [
        'logo_link' => '', // TODO-LINK: Home institucional
        // 'seta' = mostra a setinha de menu suspenso; 'ativo' = sublinhado azul (seção atual).
        'menu' => [
            ['rotulo' => 'Home', 'link' => '', 'seta' => false, 'ativo' => false],          // TODO-LINK
            ['rotulo' => 'Sobre', 'link' => '', 'seta' => false, 'ativo' => false],         // TODO-LINK
            ['rotulo' => 'Programas', 'link' => '', 'seta' => true, 'ativo' => true],       // TODO-LINK
            ['rotulo' => 'Comunicação', 'link' => '', 'seta' => true, 'ativo' => false],    // TODO-LINK
            ['rotulo' => 'Transparência', 'link' => '', 'seta' => false, 'ativo' => false], // TODO-LINK
        ],
        'botao_texto' => 'Agendamentos',
        'botao_link' => 'https://app.adesampa.com.br/welcome',
    ],

    // Links para os blocos da página (#identificador = campo 'ancora' dos espaços).
    'submenu' => [
        $link('Coworking', '#coworking'),
        $link('Podcast', '#podcast'),
        $link('Fotografia', '#fotografia'),
        $link('Música', '#musica'),
        $link('Mapa de unidades', '#mapa'),
        $link('Dúvidas', '#duvidas'),
        $link('Normas de uso', '#normas'),
    ],

    'rodape' => [
        'logo_link' => '', // TODO-LINK: Home institucional
        'texto' => 'Entidade vinculada à Prefeitura de São Paulo, por meio da Secretaria Municipal de Desenvolvimento Econômico e Trabalho (SMDET), dedicada à aceleração de negócios, inclusão produtiva e redução das desigualdades regionais.',
        'redes_titulo' => 'Redes sociais',
        'redes' => [
            'instagram' => '', // TODO-LINK
            'linkedin' => '',  // TODO-LINK
            'youtube' => '',   // TODO-LINK
            'facebook' => '',  // TODO-LINK
        ],
        // Colunas de links (todas TODO-LINK). Textos copiados exatamente do Figma.
        'colunas' => [
            'col_adesampa' => $coluna('ADE SAMPA', [$link('Início'), $link('Sobre nós'), $link('Transparência')]),
            'col_empreende' => $coluna('Sampa Empreende', [
                $link('Atendimenro ao empreendedor'), // Grafia exatamente como no Figma (pendência P-18).
                $link('Orientação empresarial'),
                $link('Curso fábrica de negócios'),
                $link('Curso de gestão de negócios'),
                $link('Linhas de crédito'),
                $link('Consultoria em gestão financeira'),
                $link('Educação financeira'),
            ]),
            'col_tech' => $coluna('Sampa Tech', [$link('Aceleração Vai Tec')]),
            'col_games' => $coluna('Sampa Games', [$link('Aceleração de estúdios'), $link('Curso de game dev'), $link('Hub de educação')]),
            'col_verde' => $coluna('Sampa Verde', [$link('Aceleração de negócios rurais'), $link('Aceleração de hortas'), $link('Aceleração de negócios verdes'), $link('Hub de inovação')]),
            'col_criativa' => $coluna('Sampa Criativa', [$link('Aceleração de audiovisual'), $link('Aceleração de moda')]),
            'col_outros' => $coluna('Outros programas', [$link('Teia: coworkings e estúdios públicos'), $link('Sampa Ensina'), $link('Gestão Sampa'), $link('Data Sampa'), $link('Praça Victor Civita')]),
            'col_comunicacao' => $coluna('Comunicação', [$link('Notícias'), $link('Publicações'), $link('Manual de marca'), $link('Manual de redação'), $link('Contato')]),
        ],
        'legais' => [$link('Termos de uso'), $link('Privacidade (LGPD)'), $link('Acessibilidade')], // TODO-LINK
        'empresa' => 'ADE SAMPA – Agência São Paulo de Desenvolvimento',
        'cnpj' => 'CNPJ: 21.154.061/0001-83',
        'endereco' => 'Sede Administrativa: Rua Líbero Badaró, 425 - 11º andar - Centro Histórico, São Paulo - SP, CEP 01009-000',
        'copyright' => '© 2026 ADE SAMPA • Prefeitura de São Paulo. Todos os direitos reservados.',
    ],
];
