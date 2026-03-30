# API de Carteira Financeira (Laravel + Sanctum)

Projeto Laravel para carteira financeira com autenticacao, operacoes financeiras seguras, reversao de transacoes, testes automatizados, observabilidade e frontend em HTML/CSS/JS puro.

## Stack

- PHP 8.3
- Laravel 13
- MySQL 8.4
- Laravel Sanctum (token Bearer)
- Docker (PHP-FPM + Nginx + MySQL)
- PHPUnit

## Arquitetura

- `app/Http/Controllers/Api/V1` - Controllers da API
- `app/Http/Requests` - Validacoes de entrada
- `app/Services` - Regras de negocio
- `app/Repositories/Contracts` - Contratos de acesso a dados
- `app/Repositories/Eloquent` - Implementacoes Eloquent
- `app/Exceptions/Finance` - Excecoes de dominio
- `app/Http/Middleware/RequestLoggingMiddleware.php` - Observabilidade de requests

## Modelagem de dados

### users

- id
- name
- email (unique)
- password
- timestamps

### wallets

- id
- user_id (unique, FK para users)
- balance
- timestamps

### transactions

- id
- type (`deposit`, `transfer`, `reversal`)
- amount
- sender_wallet_id (nullable)
- receiver_wallet_id (nullable)
- status (`pending`, `completed`, `reversed`)
- original_transaction_id (nullable, unique)
- created_at

## Regras de negocio

- Um usuario possui uma carteira (1:1).
- Transferencia valida saldo antes de debitar.
- Nao permite saldo negativo em transferencia.
- Deposito aumenta saldo imediatamente.
- Reversao cria nova transacao `reversal`.
- Reversao atualiza transacao original para `reversed`.
- Reversao eh idempotente (repetir chamada nao duplica efeito).
- Operacoes financeiras sao executadas com `DB::transaction`.

## Como rodar com Docker

1. Copie o arquivo de ambiente:

```bash
cp .env.example .env
```

2. Suba os containers:

```bash
docker compose up -d --build
```

3. Instale dependencias:

```bash
docker compose exec app composer install
```

4. Gere chave e rode migrations:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

5. Acesse:

- API: `http://localhost:8080/api/v1`
- Frontend: `http://localhost:8080`
- Health check: `http://localhost:8080/up`

## Fluxo de autenticacao

1. `POST /api/v1/register` cria usuario e retorna token.
2. `POST /api/v1/login` autentica e retorna token.
3. Envie header `Authorization: Bearer {token}` nas rotas protegidas.
4. `POST /api/v1/logout` invalida token atual.

## Endpoints

### Publicos

- `POST /api/v1/register`
- `POST /api/v1/login`

### Protegidos (`auth:sanctum`)

- `POST /api/v1/logout`
- `GET /api/v1/wallet`
- `POST /api/v1/deposit`
- `POST /api/v1/transfer`
- `POST /api/v1/reverse/{transactionId}`
- `GET /api/v1/transactions`

## Exemplos de request/response

### Registro

Request:

```json
{
  "name": "Joao",
  "email": "joao@example.com",
  "password": "12345678",
  "password_confirmation": "12345678"
}
```

Response `201`:

```json
{
  "success": true,
  "message": "Usuario registrado com sucesso.",
  "data": {
    "user": {
      "id": 1,
      "name": "Joao",
      "email": "joao@example.com"
    },
    "token": "1|..."
  },
  "errors": null
}
```

### Deposito

Request:

```json
{
  "amount": 100.50
}
```

Response `200`:

```json
{
  "success": true,
  "message": "Deposito realizado com sucesso.",
  "data": {
    "id": 10,
    "type": "deposit",
    "amount": "100.50",
    "status": "completed"
  },
  "errors": null
}
```

### Transferencia

Request:

```json
{
  "receiver_user_id": 2,
  "amount": 50.00
}
```

### Reversao

Request:

```http
POST /api/v1/reverse/15
Authorization: Bearer 1|...
```

## Tratamento de erros

Padrao de erro JSON:

```json
{
  "success": false,
  "message": "Mensagem de erro",
  "data": null,
  "errors": {
    "campo": ["detalhe"]
  }
}
```

## Testes

Executar:

```bash
docker compose exec app php artisan test
```

Cobertura implementada:

- Deposito
- Transferencia
- Saldo insuficiente
- Reversao
- Idempotencia da reversao

## Observabilidade

- Logs estruturados em operacoes de deposito/transferencia/reversao.
- Logs de erro com contexto de usuario e transacao.
- Middleware com log de request e `duration_ms`.
- Headers de diagnostico:
  - `X-Request-Id`
  - `X-Response-Time-Ms`

## Frontend

Frontend simples em:

- `resources/views/wallet-app.blade.php`

Funcionalidades:

- Login
- Registro
- Dashboard com saldo
- Deposito
- Transferencia
- Historico
- Feedback visual e loader
