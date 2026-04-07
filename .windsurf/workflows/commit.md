---
description: Gerar commit humanizado em português e publicar no git
---

## Objetivo

Analisar as alterações pendentes no repositório, gerar uma mensagem de commit humanizada em português brasileiro seguindo convenções semânticas, fazer o commit e publicar (push) no repositório remoto.

---

## Passo 1 — Verificar alterações pendentes

// turbo
Execute o comando abaixo para ver o status atual do repositório:

```bash
git status
```

---

## Passo 2 — Ver diff resumido das alterações

// turbo
Execute para entender o que mudou:

```bash
git diff --stat HEAD
```

---

## Passo 3 — Analisar as alterações e gerar mensagem de commit

Com base nos arquivos alterados, crie uma mensagem de commit seguindo as regras abaixo:

### Regras da mensagem de commit

**Formato:**
```
<tipo>(<escopo>): <descrição curta no imperativo>

<corpo opcional — o quê e por quê, não o como>

<rodapé opcional — referências, breaking changes>
```

**Tipos permitidos:**
- `feat` — nova funcionalidade
- `fix` — correção de bug
- `refactor` — refatoração sem mudança de comportamento
- `perf` — melhoria de performance
- `style` — formatação, sem lógica alterada
- `docs` — documentação
- `chore` — tarefas de manutenção (deps, configs)
- `test` — adição ou correção de testes
- `ci` — integração contínua

**Exemplos humanizados em PT-BR:**

```
feat(faturas): adicionar geração automática de faturas mensais

Implementa o agendador que cria faturas para serviços com renovação
mensal, calculando valores e aplicando descontos ativos.
```

```
fix(whatsapp): corrigir timeout na fila de envios em massa

O worker estava encerrando conexões antes do Evolution API responder
em servidores com latência acima de 2s.
```

```
refactor(notificacoes): mover envios síncronos para jobs com fila

Substitui Mail::send e chamadas diretas à API WhatsApp por
SendEmailJob e SendWhatsAppJob com rate limiting e backoff.
```

---

## Passo 4 — Adicionar todos os arquivos alterados ao stage

// turbo
```bash
git add -A
```

---

## Passo 5 — Realizar o commit com a mensagem gerada

Execute o commit com a mensagem humanizada gerada no Passo 3:

```bash
git commit -m "<tipo>(<escopo>): <descrição curta>" -m "<corpo da mensagem se houver>"
```

> Substitua `<tipo>`, `<escopo>`, `<descrição curta>` e `<corpo>` pelos valores reais gerados na análise.

---

## Passo 6 — Publicar no repositório remoto

// turbo
```bash
git push
```

Se for a primeira vez ou branch nova:

```bash
git push -u origin HEAD
```

---

## Passo 7 — Atualizar o CHANGELOG.md

Após o commit, adicione uma entrada na seção `[Não Lançado]` do arquivo `CHANGELOG.md` descrevendo o que foi entregue neste commit.

O formato é:

```markdown
### Adicionado / Alterado / Corrigido / Removido
- Descrição clara da mudança em português
```

Se a seção `[Não Lançado]` não existir, crie-a no topo, logo abaixo do cabeçalho do arquivo.

---

## Dicas

- **Nunca use inglês** na mensagem de commit ou no CHANGELOG.
- **Use verbos no imperativo**: "adiciona", "corrige", "refatora", "remove", "melhora".
- **Seja específico no escopo**: use o nome do módulo, serviço ou tela afetada (ex: `faturas`, `whatsapp`, `servidores`, `auth`).
- **Agrupe commits relacionados** — prefira um commit coeso a vários commits fragmentados.
- **Breaking changes** devem aparecer no rodapé como `BREAKING CHANGE: descrição`.
</CodeContent>
</invoke>
