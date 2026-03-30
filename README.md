# API de Carteira Financeira (Laravel)

Projeto Laravel (latest) configurado como API RESTful com arquitetura em camadas para um sistema de carteira financeira.

## Stack

- PHP 8.3 (via Docker)
- Laravel 13
- MySQL 8.4
- Nginx 1.27
- Docker Compose

## Arquitetura

Organizacao preparada para crescimento:

- `app/Http/Controllers/Api/V1`: camada HTTP (entrada/saida da API)
- `app/Http/Requests`: validacao de payloads
- `app/Services`: regras de negocio
- `app/Repositories/Contracts`: contratos de acesso a dados
- `app/Repositories/Eloquent`: implementacao concreta dos repositorios
- `app/Models`: entidades do dominio
- `database/migrations`: versionamento de schema
- `routes/api.php`: rotas REST versionadas (`/api/v1`)

## Modulos iniciais

- `Wallet` (carteiras):
  - `name`
  - `currency`
  - `balance`
- `Transaction` (transacoes):
  - `wallet_id`
  - `type` (`credit` ou `debit`)
  - `amount`
  - `description`
  - `transaction_date`

Regra de negocio implementada: saldo nunca pode ficar negativo.

## Como rodar com Docker

1. Copiar variaveis de ambiente (ja existe `.env` pronto neste workspace):

```bash
cp .env.example .env
```

2. Subir containers:

```bash
docker compose up -d --build
```

3. Instalar dependencias PHP dentro do container:

```bash
docker compose exec app composer install
```

4. Gerar chave da aplicacao:

```bash
docker compose exec app php artisan key:generate
```

5. Rodar migrations:

```bash
docker compose exec app php artisan migrate
```

6. Acessar API:

- Base URL: `http://localhost:8000`
- Health check Laravel: `http://localhost:8000/up`

## Endpoints iniciais

### Wallets

- `GET /api/v1/wallets`
- `POST /api/v1/wallets`
- `GET /api/v1/wallets/{id}`
- `PUT/PATCH /api/v1/wallets/{id}`
- `DELETE /api/v1/wallets/{id}`

### Transactions

- `GET /api/v1/wallets/{walletId}/transactions`
- `POST /api/v1/wallets/{walletId}/transactions`
- `GET /api/v1/transactions/{id}`
- `PUT/PATCH /api/v1/transactions/{id}`
- `DELETE /api/v1/transactions/{id}`

## Observacoes

- Configuracao de banco esta definida para MySQL no `.env.example`.
- Porta externa do MySQL: `33060` (mapeada para `3306` no container).
- Se desejar, ajuste credenciais no `.env` e `docker-compose.yml` de forma consistente.
