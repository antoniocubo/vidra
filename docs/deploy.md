# Deploy do Vidra em produção

Um servidor só na Hetzner, com Docker Compose. Arquivos em `infra/producao/`; variáveis em `platform/.env` (modelo: `platform/.env.production.example`).

```
internet ──443──> caddy (TLS) ──> app (FrankenPHP)      horizon    scheduler
                                    │                      │            │
                                    └──── postgres 17 ─ redis ─ gotenberg   (rede interna)
                                  backup ──> S3 (bucket de backup)       laudos ──> S3 sa-east-1
```

## 1. Servidor na Hetzner

- **Tipo:** CPX31 (4 vCPU, 8 GB) para começar; sobe de tipo sem reinstalar. Imagem Ubuntu 24.04.
- **Localização:** Ashburn (US-East) é a mais próxima do Brasil (cerca de 120 ms). Ela fica nos EUA: os PDFs ficam no S3 de São Paulo, mas o banco ficará nos EUA. Avise isso ao jurídico (LGPD, transferência internacional) antes de ter dados reais de tutor.
- **Firewall (painel da Hetzner):** entrada só 22, 80 e 443 (80 e 443/udp para o Caddy). Se possível, restrinja o 22 ao seu IP.
- **Acesso:** no primeiro boot, adicione sua chave SSH pelo painel. Depois:

```bash
adduser deploy && usermod -aG sudo deploy
mkdir -p /home/deploy/.ssh && cp ~/.ssh/authorized_keys /home/deploy/.ssh/ && chown -R deploy:deploy /home/deploy/.ssh
# /etc/ssh/sshd_config: PermitRootLogin no, PasswordAuthentication no  -> systemctl restart ssh
apt update && apt install -y unattended-upgrades git && dpkg-reconfigure -plow unattended-upgrades
curl -fsSL https://get.docker.com | sh && usermod -aG docker deploy
```

- Ative os backups automáticos da Hetzner (20% do valor do servidor) além do backup do banco abaixo.

## 2. DNS

Registros `A` (e `AAAA`, se usar IPv6) para o IP do servidor, todos iguais:

| Host | Uso |
| --- | --- |
| `vidralab.com.br` | site |
| `app.vidralab.com.br` | portal do laboratório |
| `vet.vidralab.com.br` | veterinário |
| `laudo.vidralab.com.br` | link do tutor |

O Caddy emite os certificados sozinho na primeira subida (precisa do DNS já apontando e das portas 80/443 abertas).

`SESSION_DOMAIN` fica vazio: cada host guarda o seu cookie. Uma sessão no `app.` não vale no `vet.` nem no `laudo.`, que é o desejado.

## 3. Amazon SES

1. SES na região escolhida (`AWS_SES_REGION`, `sa-east-1` ou `us-east-1`) > Identities > Create identity > Domain `vidralab.com.br`, com Easy DKIM (RSA 2048).
2. Publique no DNS os 3 CNAMEs de DKIM que o SES mostra.
3. **SPF:** TXT em `vidralab.com.br`: `v=spf1 include:amazonses.com ~all`. Se quiser, defina um MAIL FROM próprio (`mail.vidralab.com.br`) com MX e TXT SPF, para o SPF alinhar com o domínio.
4. **DMARC:** TXT em `_dmarc.vidralab.com.br`: `v=DMARC1; p=none; rua=mailto:dmarc@vidralab.com.br`. Depois de uma ou duas semanas sem falhas, suba para `p=quarantine`.
5. **Sair do sandbox:** SES > Account dashboard > Request production access (tipo transacional, descreva: laudos e convites enviados a quem pediu o exame). Até aprovar, só envia para endereços verificados.
6. Crie bounce/complaint topics no SNS se quiser monitorar rejeições.

## 4. S3 (laudos e backup)

Dois buckets, ambos com *Block all public access* ligado e criptografia SSE-S3 (ou KMS):

- **laudos:** região `sa-east-1`, ex.: `vidra-laudos-prod`. Versionamento ligado.
- **backup:** ex.: `vidra-backup-prod`, de preferência noutra conta ou região, com regra de ciclo de vida (apagar após 90 dias).

Usuário IAM da aplicação (laudos + SES), política mínima:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    { "Effect": "Allow", "Action": ["s3:GetObject", "s3:PutObject", "s3:DeleteObject"], "Resource": "arn:aws:s3:::vidra-laudos-prod/*" },
    { "Effect": "Allow", "Action": ["s3:ListBucket"], "Resource": "arn:aws:s3:::vidra-laudos-prod" },
    { "Effect": "Allow", "Action": ["ses:SendRawEmail", "ses:SendEmail"], "Resource": "*" }
  ]
}
```

Usuário IAM do backup (só escrita, não lê nem apaga):

```json
{ "Version": "2012-10-17", "Statement": [
  { "Effect": "Allow", "Action": ["s3:PutObject"], "Resource": "arn:aws:s3:::vidra-backup-prod/*" } ] }
```

## 5. Primeiro deploy

```bash
# como o usuário deploy
git clone <repo> vidra-app && cd vidra-app
cp platform/.env.production.example platform/.env
nano platform/.env     # preencha tudo; senhas: openssl rand -base64 32
infra/producao/deploy.sh
```

Na primeira vez, falta o `APP_KEY`: gere antes com
`docker compose -f infra/producao/docker-compose.yml --env-file platform/.env run --rm app php artisan key:generate --show` e cole no `.env`.

O Postgres cria os usuários `vidra` (dono, usado nas migrations) e `vidra_app` (a aplicação) na primeira subida do volume, a partir de `infra/postgres/01-vidra-app.sql`, com a senha do `DB_PASSWORD`. Se trocar a senha depois, faça `alter role vidra_app password '...'` no banco.

**Seed inicial** (laboratório e usuário administrador; confira o seeder em `platform/database/seeders` antes):

```bash
DC="docker compose -f infra/producao/docker-compose.yml --env-file platform/.env"
$DC run --rm app php artisan db:seed --database=pgsql_sistema --force
```

Faça o seed com a conexão de sistema: o `vidra_app` não passa pelo RLS de uma tabela sem laboratório definido. Não rode `migrate:fresh` nem `db:wipe` em produção.

## 6. Backup e restauração

O container `backup` roda todo dia às 03:00 (Brasília): `pg_dump -Fc`, guarda 14 dias em `/backups` (volume `backups`) e envia ao `BACKUP_S3_BUCKET`. Teste agora: `$DC exec backup /backup.sh`.

Restaurar (num banco vazio; para ensaiar, crie `vidra_restore`):

```bash
# 1. pegue o arquivo (do volume ou do S3)
aws s3 cp s3://vidra-backup-prod/vidra-AAAAMMDD-HHMMSS.dump ./restore.dump
# 2. restaure como o dono (vidra), no banco novo
$DC exec postgres createdb -U vidra vidra_restore
$DC cp restore.dump postgres:/tmp/restore.dump
$DC exec postgres pg_restore -U vidra -d vidra_restore --no-owner --role=vidra /tmp/restore.dump
# 3. confira
$DC exec postgres psql -U vidra -d vidra_restore -c "select count(*) from laboratorios"
```

Para restaurar de verdade: `$DC stop app horizon scheduler`, restaure em `vidra` (apague e recrie o banco antes, repetindo o grant de `vidra_app` do `01-vidra-app.sql`), suba tudo de novo. Ensaie a restauração antes de ter dados reais e a cada trimestre.

## 7. Onde ver o que está acontecendo

- **Logs:** `$DC logs -f app horizon` (Laravel escreve em `storage/logs`, volume `storage`, e em `stderr` do container conforme `LOG_STACK`). Caddy: `$DC logs caddy`.
- **Filas:** `https://app.vidralab.com.br/horizon`. Só abre para quem estiver em `HORIZON_EMAILS`.
- **Erros:** Sentry (`SENTRY_LARAVEL_DSN`). `send_default_pii` fica desligado: não vão IP, cookie nem usuário. Não coloque nome de tutor ou animal em mensagens de exceção.
- **Saúde:** `https://app.vidralab.com.br/up`. Configure um monitor externo (UptimeRobot, Better Stack).

## 8. Checklist de segurança do RLS

Rode depois de cada deploy que mexa em migrations:

```bash
$DC exec postgres psql -U vidra -d vidra -c "
select rolname, rolsuper, rolbypassrls from pg_roles where rolname in ('vidra','vidra_app');
select tablename, tableowner from pg_tables where schemaname='public' and tableowner='vidra_app';
select c.relname, c.relrowsecurity, c.relforcerowsecurity
  from pg_class c join pg_attribute a on a.attrelid=c.oid and a.attname='laboratorio_id'
  where c.relkind='r' and not (c.relrowsecurity and c.relforcerowsecurity);"
```

Esperado:

- `vidra_app`: `rolsuper = f` e `rolbypassrls = f`.
- `vidra` (dono das tabelas e conexão de sistema): `rolsuper = f` e `rolbypassrls = t`. Superusuário só o `postgres`, cuja senha (`POSTGRES_SUPERUSER_PASSWORD`) a aplicação não usa: uma injeção de SQL numa consulta de sistema não pode virar acesso ao servidor.
- A segunda consulta devolve **zero linhas** (o `vidra_app` não é dono de nenhuma tabela; dono ignora RLS sem `FORCE`).
- A terceira devolve **zero linhas** (toda tabela com `laboratorio_id` tem RLS ligado e forçado).
- `platform/.env` usa `DB_USERNAME=vidra_app`. Se aparecer `vidra` ali, o isolamento entre laboratórios está desligado.
- Postgres, Redis e Gotenberg não publicam portas (confira com `ss -tlnp`: só 22, 80, 443).

## 9. Atualizar

`infra/producao/deploy.sh` (pull, build, migrations com o `vidra`, troca dos containers, caches, reinício do Horizon). O Caddy não é reiniciado.
