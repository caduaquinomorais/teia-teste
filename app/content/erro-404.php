<?php

/*
 * Página de erro 404 (endereço não encontrado).
 * Não existe no Figma: layout definido em docs/DECISOES_DE_DESIGN.md (D-10).
 */

defined('TEIA_ROOT') || exit;

return [
    'slug' => 'erro-404',
    'title' => 'Página “não encontrada” (404)',
    'description' => 'Mensagem exibida quando alguém acessa um endereço que não existe.',
    'order' => 3,
    'sections' => [
        'mensagem' => [
            'label' => 'Mensagem',
            'fields' => [
                'titulo' => ['type' => 'text', 'label' => 'Título', 'required' => true, 'max' => 60, 'default' => 'Página não encontrada'],
                'texto' => ['type' => 'textarea', 'label' => 'Texto', 'required' => true, 'max' => 300, 'rows' => 3, 'default' => 'O endereço acessado não existe ou foi alterado.'],
                'botao' => ['type' => 'text', 'label' => 'Texto do botão', 'required' => true, 'max' => 30, 'default' => 'Voltar ao início'],
            ],
        ],
    ],
];
