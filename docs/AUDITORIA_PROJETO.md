# Auditoria geral — Helpdesk API

**Revisão:** 22/09/2026  
**Classificação:** projeto de estudo / protótipo funcional  
**Não classificado como:** MVP pronto ou backend de produção

## 1. Visão geral

O projeto implementa uma API REST de chamados em Laravel. Ele demonstra corretamente vários conceitos importantes do framework, mas ainda não entrega uma experiência completa, autenticada, testada e segura para usuários reais.

### Avaliação honesta

| Área | Estado |
|---|---|
| Estrutura Laravel | Boa para estudo |
| Modelagem inicial | Adequada |
| Fluxo principal de chamados | Implementado |
| Histórico | Implementado, com correção local pendente de validação |
| Autenticação real | Não implementada nas rotas do domínio |
| Autorização/Policies | Não implementada |
| Gestão de usuários | Não implementada via API |
| Testes automatizados | Muito parcial e bloqueado pelo ambiente |
| Segurança para uso real | Insuficiente |
| Pronto para produção | Não |

O projeto ficou bom como exercício prático. O ponto importante é ter ciência de que “as rotas funcionam” não significa que o backend esteja pronto como produto.

## 2. Mapa da arquitetura atual

### Camadas utilizadas

```text
Rota
  → Controller
    → Form Request
      → Action, quando há regra de negócio
        → Model/Eloquent
          → Banco de dados
```

### Responsabilidades observadas

| Camada | Uso atual |
|---|---|
| Routes | Expõem CRUDs e ações específicas |
| Controllers | Recebem requisições e retornam JSON |
| Form Requests | Validam payloads básicos |
| Actions | Concentram regras de criação, atribuição, comentário, finalização e cancelamento |
| Models | Definem fillable e relacionamentos |
| HistoricoChamado | Registra mudanças relevantes |
| DB transactions | Mantêm alteração e histórico na mesma operação |

Essa separação é um dos melhores pontos do projeto.

## 3. Mapa de entidades e banco

### Entidades presentes

| Entidade | Model | Migration/tabela | Estado |
|---|---|---|---|
| Usuário | `User` | `users` | Presente |
| Categoria | `Categoria` | `categorias` | Presente |
| Chamado | `Chamado` | `chamados` | Presente |
| Comentário | `Comentario` | `comentarios` | Presente |
| Histórico | `HistoricoChamado` | `historico_chamados` | Presente |

### Chamados

A tabela possui:

- título e descrição;
- status e prioridade;
- solicitante (`usuario_id`);
- técnico opcional (`tecnico_id`);
- categoria;
- abertura e fechamento;
- timestamps.

### Comentários

A tabela possui:

- chamado;
- usuário autor;
- mensagem;
- timestamps.

### Histórico

A tabela possui:

- chamado;
- usuário responsável pela ação;
- ação;
- valor anterior e novo;
- timestamps.

### Tipos de usuário

O campo `users.tipo` existe e recebe `solicitante` por padrão. Entretanto, o banco não restringe os valores possíveis e não existe um fluxo centralizado de criação de usuários que garanta apenas:

- `solicitante`;
- `tecnico`;
- `admin`.

## 4. Relacionamentos Eloquent

| Model | Relacionamentos |
|---|---|
| `User` | chamados abertos, chamados assumidos, comentários e históricos |
| `Categoria` | chamados |
| `Chamado` | solicitante, técnico, categoria, comentários e históricos |
| `Comentario` | chamado e usuário |
| `HistoricoChamado` | chamado e usuário |

Os relacionamentos esperados estão presentes e coerentes com as chaves estrangeiras.

## 5. Mapa da API

O Laravel reconhece 15 rotas sob `/api`: 14 do domínio e `/api/user`.

### Categorias

| Método | Endpoint | Controller |
|---|---|---|
| GET | `/api/categorias` | `index` |
| POST | `/api/categorias` | `store` |
| GET | `/api/categorias/{categoria}` | `show` |
| PUT/PATCH | `/api/categorias/{categoria}` | `update` |
| DELETE | `/api/categorias/{categoria}` | `destroy` |

### Chamados

| Método | Endpoint | Controller |
|---|---|---|
| GET | `/api/chamados` | `index` |
| POST | `/api/chamados` | `store` |
| GET | `/api/chamados/{chamado}` | `show` |
| PUT/PATCH | `/api/chamados/{chamado}` | `update` |
| DELETE | `/api/chamados/{chamado}` | `destroy` |
| POST | `/api/chamados/{chamado}/assumir` | `assumir` |
| POST | `/api/chamados/{chamado}/comentarios` | `adicionarComentario` |
| POST | `/api/chamados/{chamado}/finalizar` | `finalizar` |
| POST | `/api/chamados/{chamado}/cancelar` | `cancelar` |

## 6. Análise dos fluxos

### Criar chamado

**Estado:** implementado.

O sistema:

- recebe os dados básicos;
- força status `aberto`;
- força técnico nulo;
- define a data de abertura;
- cria o histórico `Chamado criado`.

**Limitação:** qualquer cliente pode enviar o ID de qualquer usuário existente como solicitante, pois não há autenticação aplicada ao fluxo.

### Atualizar chamado

**Estado:** CRUD básico, sem regra de negócio dedicada.

O update permite alterar título, descrição, prioridade, usuário e categoria diretamente no Controller.

Pontos de atenção:

- pode trocar o solicitante sem histórico;
- não bloqueia atualização de chamado finalizado ou cancelado;
- usa o mesmo Request da criação, então `PATCH` exige todos os campos apesar de normalmente representar atualização parcial;
- não registra quais valores mudaram.

### Excluir chamado

**Estado:** exclusão física implementada.

O chamado é removido diretamente. Comentários e históricos relacionados podem ser apagados em cascata.

Para um sistema cujo histórico serve como auditoria, isso é uma decisão perigosa. Cancelamento, Soft Deletes ou restrição de exclusão seriam opções mais coerentes.

### Assumir chamado

**Estado:** implementado.

Valida:

- tipo `tecnico`;
- status `aberto`;
- ausência de técnico atual.

Depois define técnico, altera para `em_atendimento` e registra histórico.

**Limitação:** duas requisições simultâneas ainda podem disputar o mesmo chamado porque não há bloqueio pessimista (`lockForUpdate`) nem outra estratégia explícita de concorrência.

### Adicionar comentário

**Estado:** lógica corrigida localmente, pendente de teste e commit.

O código atual no working tree já usa:

```php
'chamado_id' => $chamado->id,
```

Isso corrige o problema anterior, no qual o histórico recebia o ID do comentário em vez do ID do chamado.

Ainda falta:

- executar um teste que prove o vínculo correto;
- corrigir a mensagem `Nâo possivel...`;
- decidir exatamente quem pode comentar;
- versionar a correção.

### Finalizar chamado

**Estado:** implementado.

Exige chamado em atendimento e o ID do técnico responsável. Depois finaliza, preenche a data de fechamento e registra histórico.

**Limitação:** a identidade do técnico continua vindo do body, não do usuário autenticado.

### Cancelar chamado

**Estado:** implementado e commitado em `3021f08`.

Impede cancelamento de chamado finalizado ou já cancelado. Permite o solicitante responsável ou administrador, preenche a data de fechamento e registra histórico.

**Limitação:** como o usuário é identificado pelo `usuario_id` do body, um cliente pode se apresentar como outro usuário existente.

### Aguardando usuário

**Estado:** status previsto, fluxo ausente.

O domínio lista `aguardando_usuario` como status possível, mas não existe Action ou endpoint para:

- técnico colocar o chamado em espera;
- solicitante responder e devolver para atendimento;
- registrar essas transições no histórico.

Ou o fluxo precisa ser implementado, ou o status deve ser removido do escopo atual para evitar uma regra incompleta.

## 7. Validações atuais

### Pontos positivos

- categoria e usuário são validados com `exists`;
- prioridade possui lista de valores aceitos;
- mensagem do comentário é obrigatória;
- Requests específicos existem para as ações;
- regras mais importantes estão fora dos Controllers.

### Limitações

- todos os Form Requests autorizam com `true`;
- não há validação central dos tipos de usuário;
- não há enum/cast para status e prioridade;
- não há Request separado para criação e atualização;
- não há limites de tamanho em alguns textos;
- não existe padronização própria para respostas de erro.

## 8. Autenticação e autorização

Sanctum está instalado, mas somente `/api/user` usa `auth:sanctum`.

As rotas de categorias e chamados estão públicas. Não existem Policies.

### Consequências

- qualquer cliente pode listar, criar, alterar e excluir categorias;
- qualquer cliente pode listar, alterar e excluir chamados;
- IDs enviados no body simulam a identidade de usuários;
- regras como “somente o técnico responsável” dependem de um ID informado pelo próprio cliente;
- não há isolamento dos chamados de cada solicitante.

Esse é o principal motivo pelo qual o projeto não é um MVP utilizável por pessoas reais.

## 9. Testes

### O que existe

- dois testes padrão do Laravel;
- cinco testes de cancelamento.

### Resultado atual

O comando `php artisan test` resulta em:

- 2 testes padrão passando;
- 5 testes de cancelamento sem iniciar a lógica, por erro `could not find driver`.

O `phpunit.xml` usa SQLite em memória, mas o PHP local não possui `pdo_sqlite` e `sqlite3` habilitados.

### O que não está coberto

- categorias;
- criação de chamado;
- update e exclusão;
- assumir chamado;
- comentários;
- finalização;
- autorização;
- ciclo completo.

## 10. Dados de desenvolvimento

O seeder cria apenas um usuário padrão, que assume o tipo `solicitante`.

Não há factories específicas para:

- categoria;
- chamado;
- comentário;
- histórico.

Também não existem estados prontos de usuário para solicitante, técnico e administrador.

Isso torna os testes manuais e automatizados mais trabalhosos.

## 11. Eventos, e-mails e recursos avançados

Não foram encontrados:

- Events;
- Listeners;
- Mailables;
- Policies;
- API Resources;
- enums de domínio.

Eles não são obrigatórios para aprender o fluxo básico, mas fazem parte dos objetivos citados para evolução do projeto.

## 12. Riscos técnicos adicionais

### Histórico pode ser apagado

As exclusões em cascata podem remover registros usados como auditoria.

### Campos sensíveis em mass assignment

`Chamado::$fillable` inclui status, técnico e datas. As Actions controlam esses campos hoje, mas outro uso de `create()` ou `update()` pode modificá-los diretamente.

### Datas sem cast

`data_abertura` e `data_fechamento` não possuem cast `datetime` no model.

### Listagens sem paginação

`index()` utiliza `get()` e pode carregar todos os chamados e relacionamentos de uma vez.

### Ausência de filtros

Não há filtros por status, prioridade, técnico, solicitante ou categoria.

### Respostas acopladas aos models

Os models são retornados diretamente, sem API Resources para estabilizar o contrato JSON.

## 13. Pontos fortes do projeto

- boa prática inicial com Actions;
- regras principais legíveis;
- uso de transações;
- relacionamentos corretos;
- histórico presente nos fluxos importantes;
- commits incrementais e semânticos;
- separação razoável entre validação, controle HTTP e domínio;
- cancelamento com cenários positivos e negativos já escritos em testes.

## 14. Conclusão

Este projeto **não é um MVP neste momento**, e isso não diminui o valor dele.

Ele é um **bom projeto de estudo e um protótipo funcional** para praticar Laravel, Eloquent, transações, regras de negócio e organização em Actions.

Para evoluir de protótipo para um backend utilizável, os maiores passos são:

1. Validar e versionar a correção de comentários.
2. Fazer a suíte de testes realmente executar.
3. Cobrir o ciclo completo com testes.
4. Implementar autenticação e Policies.
5. Parar de usar IDs do body como identidade do usuário.
6. Proteger histórico e regras de atualização/exclusão.
7. Definir ou remover o fluxo `aguardando_usuario`.

O plano detalhado está em [TASKS.md](TASKS.md).
