# Pendências e evolução técnica

Este documento concentra o estado técnico, as limitações conhecidas e as próximas etapas do Helpdesk API.

## Estado atual

| Área | Estado |
|---|---|
| CRUD de categorias e chamados | Implementado |
| Criar, assumir, comentar, finalizar e cancelar chamados | Implementado |
| Relacionamentos e histórico | Implementado |
| Transações nas Actions principais | Implementado |
| Cobertura dos fluxos implementados | 36 testes passando |
| Autenticação nas rotas do domínio | Pendente |
| Autorização com Policies | Pendente |
| Gestão de usuários via API | Pendente |
| Paginação, filtros e API Resources | Pendente |
| Pronto para produção | Não |

## Limitações conhecidas

1. As rotas de categorias e chamados são públicas.
2. A identidade é informada por IDs no corpo da requisição, permitindo personificação em um ambiente exposto.
3. `PUT` e `PATCH` usam a mesma validação e exigem todos os campos, sem atualização parcial adequada.
4. A exclusão é física e pode remover comentários e histórico em cascata.
5. O status planejado `aguardando_usuario` ainda não possui fluxo de entrada e saída.
6. As listagens não possuem paginação ou filtros.
7. Não há tratamento explícito para concorrência entre técnicos tentando assumir o mesmo chamado.
8. Não existe endpoint para gestão de usuários; perfis adicionais precisam ser criados via Tinker ou diretamente no ambiente local.

## Roadmap

- [x] Validar e cobrir a correção do histórico de comentários.
- [x] Cobrir os fluxos implementados com testes.
- [ ] Manter o ambiente local de testes com SQLite habilitado.
- [ ] Preparar factories e seeders para todos os perfis e cenários.
- [ ] Separar validações de criação e atualização.
- [ ] Definir uma estratégia segura de exclusão e preservação da auditoria.
- [ ] Implementar autenticação com Sanctum.
- [ ] Implementar Policies por perfil de usuário.
- [ ] Definir o fluxo `aguardando_usuario`.
- [ ] Adicionar paginação, filtros e respostas com API Resources.
- [ ] Evoluir notificações com Events, Listeners, Mail e filas.

## Critérios antes de produção

- Proteger as rotas com autenticação.
- Aplicar autorização por perfil e por vínculo com o chamado.
- Remover a identificação do usuário por IDs enviados livremente no corpo das requisições.
- Definir a estratégia de exclusão e retenção do histórico.
- Cobrir os novos fluxos com testes automatizados.
