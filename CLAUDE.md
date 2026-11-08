# Vidra

Sistema de laboratório (LIS) em nuvem. Vai do pedido ao laudo entregue no mesmo dia. Começa pelos laboratórios veterinários (**Vidra Pet**) e depois chega aos clínicos humanos (**Vidra Clínica**, fase 3, com TISS, convênios e a RDC 786/2023 da Anvisa). Inspirado no HMSLab (2010).

- Definição de marca e produto: https://claude.ai/artifact/Ne9j4VGSZtwgLGWfi2uJHu
- Design system (tokens, logos, voz): https://claude.ai/artifact/9qpRWRM4XzZpeVwWxdgjua
- Protótipo das telas: https://claude.ai/artifact/NDu5un37GD1A2EvtrsRRdG

Antes de criar uma tela, abra o board correspondente no protótipo e siga o que está nele.

## Repositório

Monorepo simples, sem Turborepo/Nx.

```
vidra-app/
├── platform/   Laravel 12 + Inertia 2 + React 19 (o produto inteiro)
├── agent/      fase 2: agente em Go para equipamentos (ASTM/HL7). Ainda não existe.
├── infra/      docker-compose de desenvolvimento (Postgres, Redis, Gotenberg, Mailpit)
└── docs/       decisões de arquitetura (ADRs) e material de apoio
```

## Comandos (rodar dentro de `platform/`)

```bash
composer install && npm install
docker compose -f ../infra/docker-compose.yml up -d   # Postgres, Redis, Gotenberg, Mailpit
composer dev            # servidor, fila, logs e Vite juntos
php artisan test        # Pest: Unit, Feature e Arch (os de Postgres aparecem como pulados)
composer test:postgres  # suíte inteira no Postgres do compose + testes de RLS (banco vidra_testes)
composer lint           # Pint (checagem) + PHPStan nível 8
composer fix            # Pint corrigindo
npm run types           # tsc --noEmit
npm run lint            # ESLint corrigindo
npm run format          # Prettier
```

Antes de dar uma tarefa por concluída: `php artisan test`, `composer lint`, `npm run types` e `npm run lint:check` passando.

O `.env` local usa SQLite para subir sem Docker. O `.env.example` aponta para o Postgres do compose. Produção é sempre Postgres 17: RLS e alguns índices dependem dele, então rode `composer test:postgres` em tudo que tocar em tenancy.

No Postgres há dois usuários (criados por `infra/postgres/01-vidra-app.sql`): `vidra_app`, com que a aplicação entra e que obedece ao RLS, e `vidra`, dono das tabelas, usado nas migrations (`php artisan migrate --database=pgsql_sistema`) e pela conexão de sistema. Se a porta 5432 estiver ocupada, suba o compose com `VIDRA_PG_PORT=5435` e use `DB_PORT=5435`.

## Arquitetura: clean architecture

O código de negócio fica em `platform/src` (namespace `Vidra\`), separado por **módulo** (bounded context) e, dentro de cada módulo, por **camada**. `platform/app` (namespace `App\`) é só a borda do Laravel: HTTP, console, providers globais.

```
src/
├── Shared/Domain/               value objects comuns (Email, Telefone, Uf, Uuid) e DomainException
├── Veterinaria/                 linha Vidra Pet: tutor, animal, veterinário (CRMV), fatura da clínica
│   ├── Domain/                  entidades, value objects, interfaces de repositório, exceções de negócio
│   ├── Application/             casos de uso (um por classe) e seus DTOs de entrada
│   └── Infrastructure/          Eloquent, filas, APIs externas, ServiceProvider do módulo
└── Humana/                      linha Vidra Clínica (fase 3): paciente, médico (CRM), convênio, TISS
app/Http/Controllers/Lab/        adaptadores HTTP do portal do laboratório
app/Http/Controllers/Vet/        adaptadores HTTP do acesso do veterinário (a criar)
app/Http/Requests/               validação de formato da entrada HTTP
resources/js/pages/{lab,vet,laudo}/   telas Inertia de cada superfície
resources/js/components/vidra/        componentes do design system (genéricos)
resources/js/components/vidra/pet/    componentes só da linha Pet
```

### Um código, duas linhas de produto

O mesmo repositório e a mesma aplicação atendem a **Vidra Pet** e a **Vidra Clínica**. A linha é um **dado do laboratório** (`linha`: `pet` ou `clinica`, definido no cadastro dele), não um fork nem outro deploy. Ela decide quais telas aparecem, os textos, a cor da linha (`pet-500` ou `clinic-500`) e as regras de referência.

- **Núcleo neutro:** `Pedidos`, `Amostras`, `Bancada`, `Laudos`, `Catalogo` (exames e referências), `Financeiro` e `Acesso` (usuários, laboratórios, vínculos, tenancy) falam de **paciente** e **solicitante** por interfaces no domínio. Nunca de animal, tutor, CRMV, CPF ou convênio.
- **Módulos de linha:** `Veterinaria` (tutor, animal, espécie, veterinário/CRMV, clínica que paga) e `Humana` (paciente, médico/CRM, convênio, TISS) implementam essas interfaces.
- **Direção:** a linha pode usar o núcleo; o núcleo nunca usa uma linha; uma linha nunca usa a outra. Está em `tests/Arch`. Ao criar um módulo do núcleo, inclua-o na regra "o núcleo não conhece nenhuma linha de produto".
- **Front:** componentes genéricos em `components/vidra/`; os de uma linha em `components/vidra/pet/` ou `components/vidra/clinica/`. Texto que muda por linha ("tutor" ou "paciente") vem do back, não de `if` espalhado na tela.

| | Vidra Pet | Vidra Clínica |
| --- | --- | --- |
| Paciente | Animal, ligado a um tutor | Pessoa, com CPF e CNS |
| Solicitante | Veterinário (CRMV) | Médico (CRM) e outros conselhos |
| Referência muda por | Espécie, raça, idade | Sexo, idade, gestação |
| Quem paga | Clínica (fatura mensal), veterinário ou tutor | Particular ou convênio (TISS) |
| Regulação | Mais leve | RDC 786/2023, CNES, dado sensível na LGPD |

Crie um módulo quando o primeiro caso de uso dele for escrito, não antes.

### Regra de dependência

As dependências apontam só para dentro: `Infrastructure → Application → Domain`. `app/` pode usar `Application` e `Domain`.

- **Domain** não usa `Illuminate`, `Inertia`, `App` nem outras camadas. PHP puro.
- **Application** não usa `Illuminate`, `Inertia`, `App` nem `Infrastructure`. Depende de interfaces do domínio.
- **Controllers** não usam Eloquent nem `Infrastructure`: chamam um caso de uso.
- Um módulo não usa o `Domain` interno de outro. Se precisar, exponha um caso de uso ou um evento.

Isso é verificado em `tests/Arch/ArchitectureTest.php`. Se uma regra falhar, mova o código; não afrouxe a regra.

### Como escrever cada peça

O cadastro de veterinário é o exemplo de referência de ponta a ponta. Copie o formato dele:

| Peça | Exemplo | Regra |
| --- | --- | --- |
| Value object | `Crmv`, `Email`, `Telefone` | `final readonly`, valida e normaliza no construtor, lança `InvalidValue` |
| Entidade | `Veterinario` | construtor privado; `registrar()` aplica as regras de criação, `restaurar()` reconstrói da persistência; sem setters públicos, só métodos com nome do negócio |
| Repositório | `VeterinarioRepository` | interface no domínio, com `nextIdentity()`; implementação `Eloquent*` em `Infrastructure/Persistence` |
| Caso de uso | `RegistrarVeterinario` | `final readonly`, um `__invoke`, recebe um `*Input` de primitivos, devolve id ou DTO, nunca um Model |
| Exceção de negócio | `CrmvJaCadastrado` | estende `Vidra\Shared\Domain\DomainException`, construtor nomeado (`::para()`), mensagem pronta para o usuário |
| Model Eloquent | `VeterinarioModel` | só persistência, `final`, sem regra de negócio, nunca sai da `Infrastructure` |
| FormRequest | `RegistrarVeterinarioRequest` | valida formato e tem `toInput()` |
| Controller | `VeterinarioController` | fino: request → caso de uso → resposta; traduz `DomainException` em `ValidationException` |
| Binding | `VeterinariaServiceProvider` | um provider por módulo, registrado em `bootstrap/providers.php` |
| Dado de laboratório | `VinculoModel` | tabela com `laboratorio_id` + `Rls::isolarPorLaboratorio()` na migration; model com `use PertenceAoLaboratorio`; caso de uso recebe `LaboratorioAtual` |

### Clean code

- `declare(strict_types=1)` em todo arquivo PHP (o Pint aplica).
- Classes `final` por padrão. `readonly` sempre que der.
- Nomes de domínio em português, como o laboratório fala: `Pedido`, `Amostra`, `Laudo`, `Tutor`, `liberar()`, `pedirRecoleta()`. Termos técnicos de código em inglês: `Repository`, `save`, `findBy`, `Input`.
- Uma classe, uma responsabilidade. Um caso de uso por arquivo. Métodos curtos, sem flag booleana como parâmetro.
- Sem `dd`, `dump` ou `var_dump` no código (há teste de arquitetura para isso).
- Comentário explica o porquê, não o quê.
- Datas gravadas em UTC (`APP_TIMEZONE=UTC`) e exibidas em `America/Sao_Paulo`.

### Testes

- **Unit** (`tests/Unit/<Módulo>`): domínio e casos de uso, sem banco, com repositório em memória (`tests/Support/InMemory*Repository`).
- **Feature** (`tests/Feature/<Módulo>`): HTTP + banco, um teste por fluxo e por erro de negócio.
- **Arch** (`tests/Arch`): regras de dependência.
- Playwright para fluxos críticos de ponta a ponta (pedido → laudo) quando as telas existirem.
- Nome do teste em português, descrevendo o comportamento: `it('não deixa repetir o CRMV na mesma UF')`.

### Front-end

- Inertia + React 19 + TypeScript + Tailwind 4 + shadcn/ui (starter kit oficial do Laravel).
- Uma pasta de páginas por superfície: `pages/lab`, `pages/vet`, `pages/laudo`.
- **Design system em `resources/js/components/vidra`**, importado de `@/components/vidra` (e `@/components/vidra/pet` para a linha Pet). Use estes antes de criar algo novo; os de `components/ui` (shadcn) são base interna.
  - Ações: `Button` (primary, secondary, ghost, danger; sm, md, lg; `loading`, `asChild`), `IconButton` (exige `label`).
  - Formulário: `FormField` + `TextInput` (`mono`, `icon`, `suffix`), `Select`, `Checkbox`, `Switch`, `RadioCardGroup`, `ChipToggleGroup`, `SegmentedControl`, `ScanField` (leitor de código de barras), `FormSection`, `FormActions`.
  - Status: `StatusBadge` (`tone`: ok, warn, crit, info, neutral), `ProductBadge`, `AiBadge`, `CountPill`, `Alert`, `Toast` + `useFlashToast` (lê `flash.success`), `EmptyState`.
  - Dados: `MetricCard`, `DataTable`, `ResultTable` (alto, baixo, crítico, valor anterior), `DetailList`, `ProgressBar`.
  - Estrutura: `PageHeader`, `Card`, `Tabs` + `TabPanel`, `StatusStepper`, `ConfirmDialog`, `LabSwitcher`, `InitialsAvatar`, `LabMark`, `PersonRow`.
  - Pet: `AnimalCard`, `SampleLabel`.
  - Vitrine com todos os componentes: `/dev/componentes` (só em local e testing). Ao criar ou mudar um componente, atualize a vitrine.
  - Referência visual: página "Componentes" do protótipo.
- Tokens da Vidra em `resources/css/app.css` como `--vidra-*`, ligados aos papéis do shadcn (`--primary`, `--border` etc.). Use as classes do Tailwind (`bg-primary`, `text-muted-foreground`, `bg-crit-100`, `text-ok-600`, `font-display`, `font-mono`), nunca cor em hex no componente.
- Campos com 44px de altura no mínimo (`h-11`). Labels reais com `htmlFor`.

## Delegar tarefas ao Codex CLI

Para economizar tokens do Claude, tarefas de implementação bem delimitadas (um componente, uma tela, um lote de arquivos) podem ir para o Codex CLI (`codex`, já instalado). O Claude escreve a instrução, roda o Codex, revisa o resultado e testa.

- **Modelo:** use `-m gpt-6.1-sol` (decidido em 08/11/2026: quase o desempenho do `gpt-6-astra` gastando menos da cota). A conta é do ChatGPT e recusa alguns modelos, inclusive o padrão do `~/.codex/config.toml` (`gpt-5.4`), com o erro "model is not supported when using Codex with a ChatGPT account". Para ver o catálogo: `codex debug models`.
- **Cota:** a conta tem limite de uso por janela de horas. Rode um lote de cada vez no Codex e peça as checagens completas só no fim do lote. Se o limite estourar, o plano B (abaixo) assume.
- **Comando** (da raiz do repositório, em segundo plano):

  ```bash
  codex exec -m gpt-6.1-sol -s workspace-write -C platform -o <saida>.md - < <instrucao>.md > <log>.txt 2>&1
  ```

  `-s workspace-write` deixa escrever só dentro de `platform/`; `-o` grava a resposta final; a instrução vai pela entrada padrão.
- **A instrução deve trazer:** ler este `CLAUDE.md` antes de tudo; os arquivos exatos que pode criar ou editar (e que não mexa em outros); um arquivo já pronto como padrão a seguir (ex.: `components/vidra/button.tsx`); as regras de token de cor, acessibilidade e textos em português; não fazer commit; rodar `npx tsc --noEmit`, `npx eslint .`, `npx prettier --write resources/js`, `npm run build` e `php artisan test` até passar; responder com a lista de arquivos e as decisões diferentes do pedido.
- **Vários lotes em paralelo** só quando os arquivos não se cruzam. Arquivos compartilhados (como `components/vidra/index.ts`) ficam para o Claude no fim.
- **Depois do Codex, o Claude sempre:** lê o diff, corrige o que destoar do padrão, roda as checagens de novo e testa a tela no navegador (o Codex não testa interação). Só então faz o commit.
- Se o Codex falhar por conta ou modelo, o plano B são subagentes do Claude num modelo mais barato (Sonnet), com a mesma instrução.

## Decisões de produto

- **Segmento:** laboratórios veterinários primeiro (Vidra Pet); clínicos humanos na fase 3 (Vidra Clínica), no mesmo código.
- **Diferenciais:** nuvem com preço acessível, laudo entregue por e-mail e link, IA no fluxo.
- **Superfícies (todas na mesma aplicação, separadas por subdomínio):**
  - `app.vidralab.com.br`: portal do laboratório.
  - `vet.vidralab.com.br`: acesso do veterinário solicitante.
  - `laudo.vidralab.com.br`: link do tutor (código de acesso / QR).
- **Sem portal da clínica no MVP** (decidido em 06/11/2026). A clínica é cadastro e recebe a fatura mensal por e-mail, mas não tem login.
- **Veterinário:** uma pessoa só no Vidra, identificada pelo e-mail e pelo CRMV (único por UF). Cada laboratório cria um **vínculo** (`laboratorio_veterinario`) com dados próprios: quem paga, telefone para valor crítico, se vê os pedidos dos colegas da clínica. Com mais de um laboratório, ele escolhe em qual entrar depois do login e troca pelo cabeçalho. Nunca mostra laudos de laboratórios misturados.
- **Tutor e animal:** o tutor autoriza ou não receber laudo por e-mail (LGPD, registrado com data, hora e atendente). O animal define a faixa de referência (espécie, idade, sexo).
- **Fluxo:** pedido → amostra/triagem → bancada → liberação (só o RT assina) → laudo PDF → entrega → fatura mensal.
- **RT:** responsável técnico, veterinário com CRMV. É o único papel que libera e assina laudos.
- **Entrega do laudo:** e-mail (Amazon SES), acesso do veterinário, link com código de acesso para o tutor, botão manual de compartilhar no WhatsApp (link `wa.me`, sem API). WhatsApp automático fica para a fase 2.
- **Etiquetas:** Code 128 (`picqer/php-barcode-generator`). O leitor USB funciona como teclado: campo com foco que envia no Enter. No MVP, a impressão é um PDF no tamanho da etiqueta; QZ Tray ou o agente Go depois.
- **IA:** Claude Sonnet 5.5 (`claude-sonnet-5-5`) lê a foto do pedido; Claude Haiku 5.5 (`claude-haiku-5-5`) para textos curtos (resumo para o tutor). Regras de referência, delta check e valor crítico são motor próprio, não IA. A IA aponta; quem libera é o RT.
- **Cobrança do Vidra:** por pedido (atendimento), não por exame. Bancada R$ 290 (600 pedidos, 3 usuários), Laboratório R$ 590 (2.000, 10 usuários), Rede R$ 1.190 (6.000, ilimitado, 5 unidades). Excedente R$ 0,25 por pedido, sem taxa de implantação, 14 dias de teste, anual com 2 meses grátis. Clínicas, veterinários e tutores não pagam o Vidra.

## Decisões técnicas

- PHP 8.4, Laravel 12, Inertia 2, React 19, TypeScript, Tailwind 4, shadcn/ui.
- PostgreSQL 17. **Multi-tenant por `laboratorio_id`**, em defesa dupla:
  - **Laboratório atual:** o middleware `laboratorio` (`DefinirLaboratorioAtual`) lê o escolhido da sessão, confere o vínculo em `laboratorio_usuario` a cada requisição e define `ContextoLaboratorio`. O domínio enxerga só a interface `Vidra\Shared\Domain\Tenancy\LaboratorioAtual`. Jobs e comandos usam `ContextoLaboratorio::executarComo()`.
  - **Eloquent:** o trait `PertenceAoLaboratorio` filtra toda consulta e preenche o `laboratorio_id`. Sem laboratório definido, lança `LaboratorioNaoDefinido` em vez de devolver tudo.
  - **Postgres:** `Rls::isolarPorLaboratorio()` liga RLS com `FORCE` e uma policy que compara com `app.laboratorio_id` (definido pelo `ContextoLaboratorio`). Um teste falha se alguma tabela com `laboratorio_id` ficar sem RLS.
  - **Global, sem RLS:** `users`, `laboratorios`, `veterinarios` (a pessoa) e `laboratorio_usuario` (o mapa de acessos). O que cruza laboratórios (lista de acessos, link do tutor, fatura mensal) usa só a conexão `config('vidra.conexao_sistema')`.
  - Tutor e animal são cadastros de cada laboratório.
- IDs UUID v7 gerados pelo repositório (`nextIdentity()`).
- Redis + Horizon para filas. Gotenberg para gerar o PDF do laudo.
- Assinatura do laudo com certificado ICP-Brasil A1, padrão PAdES.
- S3 (região sa-east-1) para arquivos. SES com SPF e DKIM para e-mail.
- `spatie/laravel-permission` para papéis e `spatie/laravel-activitylog` para trilha de auditoria (a instalar quando o primeiro caso de uso pedir).
- Hospedagem: Laravel Cloud ou AWS (a decidir).

## Marca

- Nome em minúsculas no logo: `vidra`, com o "i" desenhado como tubo de ensaio. Linha Pet usa `pet-500` (coral) na tampa e no líquido.
- Cores principais: `brand-900` #0E4F5C (menu e botão principal), `brand-500` #237784 (links e foco), `accent-400` #F2B33D (destaque, no máximo um ou dois por tela), `glass-50` #F4F7F6 (fundo).
- Status: `ok`, `warn` e `crit`, sempre com palavra ou ícone junto, nunca só a cor.
- Fontes: Bricolage Grotesque (títulos), Plus Jakarta Sans (interface), JetBrains Mono (resultados, protocolos, CRMV, telefones).
- Voz: direta e calma, usa o nome do animal e do tutor. Botões dizem a ação ("Liberar laudo", "Salvar veterinário"), nunca "OK" ou "Enviar".
- Formatos: datas 06/11/2026, horas 14:32, vírgula decimal, unidades como o laboratório escreve (mg/dL, mEq/L).

## Pendências conhecidas

- O PHPStan tem uma baseline (`platform/phpstan-baseline.neon`) só com erros do código de autenticação do starter kit. Ela some quando a autenticação multi-laboratório for reescrita. Não adicione nada novo à baseline.
- As telas de auth e settings ainda estão em inglês (starter kit).
- Roteamento por subdomínio: hoje `routes/lab.php` está sem `Route::domain()`.
