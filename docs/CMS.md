# Manual do painel (CMS)

Este manual é para quem vai **editar os textos, imagens e documentos** do site do Teia. Não é preciso saber programar.

## Entrar no painel

1. Abra o endereço do site seguido de `/admin`, por exemplo `https://www.exemplo.com.br/teia/admin`.
2. Digite seu **usuário** e sua **senha**.
3. **Primeiro acesso:** você recebe uma senha temporária e precisa criar a sua. Use pelo menos 12 caracteres. Uma frase curta é fácil de lembrar e difícil de adivinhar, por exemplo: *café com pão de queijo às 9*.

> Se errar a senha 5 vezes seguidas, o acesso fica bloqueado por 15 minutos. Isso é proteção contra invasão.
>
> Depois de 30 minutos sem uso, o painel pede login de novo.

## Tela inicial

A tela inicial mostra só as partes do site que você tem permissão para editar:

| Cartão | O que você edita |
|---|---|
| **Página do Teia** | Banner, espaços (coworking e estúdios), mapa, dúvidas e normas |
| **Cabeçalho e rodapé** | Menu superior, submenu do Teia, rodapé, redes sociais e links institucionais |
| **Página "não encontrada" (404)** | Mensagem mostrada quando alguém acessa um endereço que não existe |

Clique em **Editar** no cartão desejado.

## Editar e publicar

1. Altere os campos que quiser. Cada campo tem uma explicação logo abaixo do nome. Campos com `*` são obrigatórios.
2. Clique em **Publicar alterações**, no fim da página.
3. As mudanças aparecem no site **na hora**.

Se algo estiver errado (um campo obrigatório vazio, um link inválido, um texto longo demais), **nada é publicado**. O painel mostra uma lista do que corrigir, e o que você já digitou continua no formulário.

### Formatação de texto

Alguns campos aceitam formatação simples. Isso está indicado abaixo do campo.

| Você digita | Aparece no site |
|---|---|
| `**texto**` | **texto** (negrito) |
| `*texto*` | *texto* (itálico) |
| `[clique aqui](https://www.exemplo.com.br)` | um link "clique aqui" |
| uma linha em branco | um novo parágrafo |

Não é possível colar HTML ou códigos. Se colar, eles aparecem como texto.

### Links

Os links aceitam três formatos:

- Endereços completos: `https://...`
- E-mail: `mailto:contato@...`
- Telefone: `tel:+5511...`

Para levar a uma parte da própria página, use `#` mais o identificador do bloco: `#coworking`, `#podcast`, `#fotografia`, `#musica`, `#mapa`, `#duvidas` ou `#normas`.

**Link vazio:** o item aparece no site, mas não leva a lugar nenhum. Use isso enquanto o endereço não existir. É o caso, por enquanto, dos links institucionais (Home, Sobre, Programas, redes sociais…).

### Imagens

- Formatos aceitos: **JPG, PNG ou WebP**, com até 8 MB.
- O site reduz e otimiza a imagem automaticamente e remove dados escondidos, como a localização GPS da foto.
- Fotos dos espaços: o ideal é pelo menos **1086 × 784 pixels**, na horizontal.
- Sempre preencha a **descrição da foto**. Ela é lida por pessoas cegas que usam leitor de tela. Descreva o que aparece, por exemplo: *Estúdio de podcast com mesa, quatro microfones e luzes de LED*.

### Documentos PDF (Normas de uso)

- Envie o arquivo no campo **Arquivo PDF** (até 15 MB). O botão "Baixar PDF" só aparece no site quando há um arquivo.
- PDFs com formulários, scripts ou anexos embutidos são recusados. Nesse caso, exporte o documento de novo como PDF simples (no Word: *Salvar como → PDF*).

### Listas: espaços, fotos, perguntas, unidades, links

Várias partes são listas. Em cada item há botões para:

- **↑ / ↓**: mudar a ordem.
- **Remover**: apagar o item. A remoção só vale depois de clicar em *Publicar alterações*.
- **+ Adicionar item**, no fim da lista: criar um novo item.

Exemplos práticos:

- **Mais fotos de um espaço:** em *Espaços → Fotos*, clique em *+ Adicionar item*. Com 2 fotos ou mais, aparecem no site os pontinhos para trocar de foto.
- **Responder uma dúvida:** preencha o campo *Resposta*. **Perguntas sem resposta não aparecem no site.**
- **Nova unidade no mapa:** em *Mapa das unidades → Unidades do Teia*, clique em *+ Adicionar item* e preencha nome, zona e endereço completo. Com mais de uma unidade, o visitante escolhe qual ver no mapa.

### Mapa personalizado (opcional)

Se a equipe criar um mapa no **Google My Maps** com todas as unidades:

1. No My Maps, abra *Compartilhar* e deixe o mapa público.
2. Depois abra *Incorporar no meu site* e copie só o endereço que começa com `https://www.google.com/maps/d/embed?mid=...`.
3. Cole esse endereço no campo **Mapa personalizado do Google**.

## Se outra pessoa editou ao mesmo tempo

Se alguém publicou a mesma página enquanto você editava, aparece o aviso **Conflito de edição**. Suas alterações **não foram perdidas**: elas continuam no formulário. Você pode:

- Abrir a página em outra aba, conferir o que mudou e refazer suas alterações; ou
- Clicar em **Publicar mesmo assim**, o que substitui a versão da outra pessoa. A versão dela continua no histórico.

## Histórico e voltar uma versão

1. Na página de edição, clique em **Histórico de versões**.
2. A lista mostra cada publicação, com data e autor.
3. Clique em **Restaurar** na versão desejada. Isso cria uma nova versão com aquele conteúdo; nada é apagado.

## Trocar a sua senha

Menu **Minha senha**. Ao trocar, suas outras sessões abertas (em outro computador, por exemplo) são encerradas.

## Somente administradores

- **Usuários:** criar contas, definir o papel (administrador ou editor), escolher quais páginas cada editor pode editar, redefinir senha, desativar e reativar contas. Ver [PERMISSOES.md](PERMISSOES.md).
- **Backups:** criar, baixar, restaurar e configurar. Ver [MANUTENCAO.md](MANUTENCAO.md).
- **Auditoria:** registro de quem fez o quê e quando.

## Dúvidas comuns

| Situação | O que fazer |
|---|---|
| "Sessão expirada ou formulário inválido" | Recarregue a página (F5) e tente de novo. Acontece depois de muito tempo parado. |
| A imagem não foi aceita | Confira o formato (JPG, PNG ou WebP) e o tamanho (até 8 MB). |
| Não vejo uma página no painel | Peça a um administrador que libere a permissão. |
| Esqueci a senha | Peça a um administrador para redefinir. Se você é o único administrador, veja *Recuperar acesso* em [INSTALACAO.md](INSTALACAO.md). |
