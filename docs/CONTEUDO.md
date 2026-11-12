# Como editar o conteúdo

O site não tem painel administrativo. Todo o conteúdo fica em arquivos PHP em `app/content/`, com os textos do Figma como ponto de partida. Para alterar:

1. Edite o arquivo no seu editor (VS Code, por exemplo).
2. Confira localmente com `bin/serve.sh` e `bin/php.sh tests/run.php`.
3. Faça commit e `git push`. O Git Webhook da KingHost publica a alteração.

## Regras gerais

- Os textos ficam **entre aspas simples**: `'texto'`. Para usar um apóstrofo dentro do texto, escreva `\'` (ex.: `'d\'água'`).
- Não apague as vírgulas no fim das linhas nem os colchetes `[ ]`.
- **HTML não funciona**: tudo é exibido como texto (proteção contra XSS). Uma linha em branco dentro do texto cria um novo parágrafo.
- **Links** aceitam `https://…`, `http://…`, `mailto:…`, `tel:…` ou `#ancora`. Link vazio (`''`) = o item aparece sem levar a lugar nenhum.
- Se o arquivo ficar com erro de sintaxe, o site mostra "Erro interno". Rode `bin/php.sh -l app/content/teia.php` para encontrar a linha com problema.

## `app/content/teia.php`

| Bloco | O que editar |
|---|---|
| `seo` | Título da aba do navegador e descrição para o Google |
| `geral.link_agendamento` | Destino de todos os botões "Faça seu agendamento" |
| `banner` | Trilha (Início / Programas / Teia, com links `TODO-LINK`), etiqueta, título, texto e imagem de fundo |
| `espacos` | Os 4 blocos (coworking e estúdios). A ordem da lista é a ordem na página, e a foto alterna de lado automaticamente. |
| `mapa` | Título, texto, rótulos do painel e **lista de unidades** |
| `duvidas` | Perguntas e respostas |
| `normas` | Título, texto e documentos PDF |

### Responder uma dúvida

```php
['pergunta' => 'O uso do Teia é totalmente gratuito?', 'resposta' => 'Sim. Todos os espaços são gratuitos para empreendedores e MEIs.'],
```

**Pergunta com resposta vazia não aparece no site.** A numeração (1., 2., …) é automática.

### Adicionar uma unidade ao mapa

Copie um item da lista `unidades` e altere os dados:

```php
[
    'nome' => 'TEIA Exemplo',
    'zona' => 'Sul',
    'classificacao' => 'TEIA - Espaço de Trabalho',
    'status' => 'Atendimento funcionando',
    'endereco' => 'Rua Exemplo, 123 - Bairro, São Paulo - SP, 01000-000',
],
```

Com mais de uma unidade, aparece um seletor ao lado do mapa. O `endereco` é usado para posicionar o Google Maps, então inclua a cidade e o CEP.

**Mapa personalizado:** se a equipe criar um mapa no Google My Maps com todas as unidades, cole o link de incorporação (`https://www.google.com/maps/d/embed?mid=…`) em `mapa_personalizado`. Nesse caso ele substitui o painel e o mapa.

### Fotos dos espaços

1. Coloque a foto em `public/assets/images/` em dois formatos: `nome.jpg` e `nome.webp`. O ideal é 1086 × 784 px.
   - Para gerar os dois formatos a partir dos originais do Figma: `bin/php.sh bin/preparar-assets.php`.
   - Para outras imagens, use qualquer editor que exporte WebP (ex.: squoosh.app).
2. No espaço, adicione um item à lista `fotos`:
   ```php
   'fotos' => [
       ['imagem' => 'foto-coworking', 'alt' => 'Coworking público do Teia com mesas compartilhadas'],
       ['imagem' => 'coworking-2', 'alt' => 'Sala de reunião do coworking'],
   ],
   ```
   Use o nome do arquivo sem extensão. Com 2 fotos ou mais, aparecem os pontinhos para trocar de foto.
3. Sempre escreva o `alt`: é a descrição da foto para pessoas que usam leitor de tela.

### PDFs das normas

1. Coloque o PDF em `public/assets/docs/` (crie a pasta se não existir), com nome simples, sem espaços nem acentos. Ex.: `manual-de-conduta.pdf`.
2. Informe o nome no documento:
   ```php
   ['titulo' => 'Manual de conduta', 'descricao' => '…', 'arquivo' => 'manual-de-conduta.pdf', 'botao' => 'Baixar PDF'],
   ```
   Também é aceito um endereço `https://…` de um PDF hospedado em outro lugar.

Se o arquivo não existir, o botão "Baixar PDF" não aparece.

## `app/content/layout.php`

- `cabecalho.menu`: itens do menu superior.
  - `seta`: mostra a setinha de menu suspenso.
  - `ativo`: sublinhado azul de seção atual.
- `cabecalho.botao_link`: botão azul "Agendamentos".
- `submenu`: faixa cinza com o logo do Teia. Use `#identificador` para levar a um bloco da página: `#coworking`, `#podcast`, `#fotografia`, `#musica`, `#mapa`, `#duvidas` e `#normas`. Esses identificadores vêm do campo `ancora` dos espaços.
- `rodape`: texto institucional, redes sociais, colunas de links, links legais e dados da ADE SAMPA.

Todos os links institucionais estão marcados com `// TODO-LINK`.

## O que não muda pelos arquivos de conteúdo

O layout, a ordem das seções, as cores e as fontes ficam em:

- `app/views/site/*.php` (estrutura);
- `public/assets/css/site.css` (visual, com os tokens do Figma).
