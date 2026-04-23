# API de Carteira Financeira (Laravel + Sanctum)

Projeto Laravel para carteira financeira com autenticacao, operacoes financeiras seguras, reversao de transacoes, testes automatizados, observabilidade, processamento assincrono com filas e frontend em HTML/CSS/JS puro.

## Stack

- PHP 8.3
- Laravel 13
- MySQL 8.4
- Redis 7
- Laravel Reverb (WebSockets)
- Laravel Sanctum (token Bearer)
- Docker (PHP-FPM + Nginx + MySQL + Redis)
- PHPUnit

## Arquitetura

- `app/Http/Controllers/Api/V1` - Controllers da API
- `app/Http/Requests` - Validacoes de entrada
- `app/Services` - Regras de negocio
- `app/Repositories/Contracts` - Contratos de acesso a dados
- `app/Repositories/Eloquent` - Implementacoes Eloquent
- `app/Exceptions/Finance` - Excecoes de dominio
- `app/Http/Middleware/RequestLoggingMiddleware.php` - Observabilidade de requests
- `app/Jobs` - Processamento assincrono de operacoes financeiras
- `resources/lang` - Traducoes de mensagens da API

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
- idempotency_key (nullable, unique)
- original_transaction_id (nullable, unique)
- created_at

## Regras de negocio

- Um usuario possui uma carteira (1:1).
- Transferencia valida saldo antes de debitar.
- Nao permite saldo negativo em transferencia.
- Deposito, transferencia e reversao sao processados de forma assincrona via filas.
- Operacoes retornam status `pending` imediatamente e sao processadas em background.
- Transferencia eh idempotente quando o mesmo `idempotency_key` e enviado.
- Reversao cria nova transacao `reversal`.
- Reversao atualiza transacao original para `reversed`.
- Reversao eh idempotente (repetir chamada nao duplica efeito).
- Operacoes financeiras sao executadas com `DB::transaction`.
- Mensagens da API sao traduzidas para portugues.

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

5. Inicie o worker de filas (em outro terminal):

```bash
docker compose exec app php artisan queue:work
```

6. Inicie o servidor WebSocket do Reverb (em outro terminal):

```bash
docker compose exec app php artisan reverb:start --host=0.0.0.0 --port=8081
```

7. Acesse:

- API: `http://localhost:8080/api/v1`
- Frontend: `http://localhost:8080`
- Health check: `http://localhost:8080/up`
- WebSocket Reverb: `ws://localhost:8081`

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

- `POST /api/v1/broadcasting/auth`
- `POST /api/v1/logout`
- `GET /api/v1/wallet`
- `POST /api/v1/deposit`
- `POST /api/v1/transfer`
- `POST /api/v1/reverse/{transactionId}`
- `GET /api/v1/transactions`
  - Query params opcionais:
    - `limit` (inteiro entre 1 e 100, padrao `20`)
    - `offset` (inteiro maior ou igual a 0, padrao `0`)

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
    "status": "pending"
  },
  "errors": null
}
```

> **Nota**: Todas as operacoes financeiras retornam status `pending` e sao processadas em background. O status muda para `completed` apos processamento pela fila.
>
> Quando os jobs concluem com sucesso, o backend emite eventos via Reverb para atualizar automaticamente o dashboard do usuario.

## Traducoes

As mensagens da API sao traduzidas para portugues. O idioma pode ser configurado via variavel `APP_LOCALE` no arquivo `.env` (padrao: `pt`).

Arquivos de traducao: `resources/lang/pt/messages.php` e `resources/lang/en/messages.php`.

### Transferencia

Request:

```json
{
  "receiver_user_id": 2,
  "amount": 50.00,
  "idempotency_key": "unique-transfer-key-123"
}
```

A propriedade `idempotency_key` e opcional, mas quando fornecida garante que repeticoes da mesma requisicao retornem a mesma transacao em vez de duplicar o efeito.

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
- Atualizacao automatica via WebSocket apos conclusao dos jobs
