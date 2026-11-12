# Usuários e permissões

## Papéis

| Papel | Pode |
|---|---|
| **Administrador** | Editar todas as páginas; criar, editar, desativar e reativar usuários; redefinir senhas; conceder e revogar permissões; gerenciar backups; ver a auditoria. |
| **Editor** | Editar e publicar **somente as páginas liberadas** para ele; trocar a própria senha. |

## Permissões por página

- Todo acesso é **negado por padrão**: um editor sem nenhuma página liberada não edita nada.
- As páginas disponíveis vêm das definições em `app/content/`: `teia`, `layout` (cabeçalho e rodapé) e `erro-404`.
- Editar uma página significa publicá-la, porque a publicação é imediata. Não existe fluxo de aprovação, que não fazia parte do escopo.
- O backend valida a permissão em **todas** as rotas, inclusive em POST direto. Isso está coberto por testes (`tests/http/PermissoesTest.php`).

## Proteções da conta

| Regra | Como funciona |
|---|---|
| Senha temporária | Contas criadas ou com senha redefinida recebem uma senha temporária de 16 caracteres, exibida uma única vez. O primeiro login obriga a troca, e nenhuma outra ação é permitida antes disso. |
| Último administrador | A última conta de administrador ativa não pode ser desativada nem rebaixada a editor. Ninguém pode desativar a própria conta. |
| Encerramento de sessões | Cada usuário tem uma `session_version`. Ela é incrementada na troca ou redefinição de senha, na desativação, na mudança de papel e na **revogação de permissão**. Sessões com versão antiga são encerradas na próxima requisição. |
| Nome de acesso | De 3 a 40 caracteres: `a-z 0-9 . _ -`. Não pode ser alterado depois. |
| Senha | Mínimo de 12 caracteres, sem senhas comuns e sem o nome de usuário. Hash Argon2id (bcrypt se o servidor não tiver Argon2id). |

## Auditoria

Ficam registrados em `audit_log`, visível em **Auditoria**:

- logins (sucesso, falha e bloqueio) e saídas;
- publicações, restaurações e conflitos de edição;
- acessos negados;
- criação e alteração de usuários (com as permissões concedidas e revogadas), redefinição de senha, desativação e reativação;
- operações de backup;
- instalação e recuperação de acesso.

O IP é guardado só como HMAC truncado, o que permite correlacionar eventos sem identificar a pessoa (LGPD).

## Procedimentos

**Criar um editor:** Usuários → *Novo usuário* → papel **Editor** → marque as páginas → *Criar usuário* → envie o usuário e a senha temporária por um canal seguro.

**Retirar o acesso a uma página:** Usuários → *Gerenciar* → desmarque a página → *Salvar*. As sessões abertas dessa pessoa são encerradas na hora.

**Alguém saiu da equipe:** Usuários → *Gerenciar* → *Desativar conta*. O histórico continua mostrando o nome dessa pessoa nas publicações antigas.
