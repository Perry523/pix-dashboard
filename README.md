# Sistema PIX - Backend e Frontend

Sistema completo de pagamentos PIX com autenticação de usuários, geração de PIX fake usando tokens UUID, confirmação de pagamentos e dashboard com analytics.

## 🚀 Funcionalidades

- ✅ **Autenticação de usuários** com Laravel Sanctum
- ✅ **Geração de PIX** com tokens UUID e tempo de expiração configurável (padrão: 10 minutos)
- ✅ **Confirmação de pagamentos** via endpoint `/api/pix/{token}`
- ✅ **Dashboard protegido** com estatísticas e analytics dos PIX
- ✅ **Geração de QR Code** e links para entrega ao cliente
- ✅ **Expiração automática** server-side dos tokens PIX
- ✅ **Notificações push** com Pusher Beams
- ✅ **Interface responsiva** com Vue.js e Tailwind CSS

## 📋 Pré-requisitos

- PHP 8.1 ou superior
- Composer
- Node.js 18+ e npm/yarn
- MySQL (ou outro banco suportado pelo Laravel)

## 🛠️ Instalação

### 1. Clone o repositório

```bash
git clone <url-do-repositorio>
cd freela-test
```

### 2. Instale as dependências do PHP

```bash
composer install
```

### 3. Instale as dependências do Node.js

```bash
npm install
# ou
yarn install
```

### 4. Configure o ambiente

```bash
cp .env.example .env
```

Edite o arquivo `.env` com suas configurações:

```env
# Configurações do banco de dados
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pix_system
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Gere a chave da aplicação

```bash
php artisan key:generate
```

### 6. Execute as migrações

```bash
php artisan migrate
```

### 7. Execute os seeders

Para popular o banco com dados de teste:

```bash
php artisan db:seed
```

Isso criará:
- 1 usuário de teste (test@example.com / password)
- 1000 registros PIX (500 pagos + 500 expirados)

## 🚀 Executando o projeto

### 1. Inicie o servidor Laravel

```bash
php artisan serve
```

### 2. Inicie o frotend

Em outro terminal:

```bash
npm run dev
```

### 3. Execute o job de expiração dos tokens

Também em outro terminal:

```bash
php artisan schedule:work
```

## 📱 Acesso ao sistema

- **URL**: http://127.0.0.1:8000
- **Login da seed**: test@example.com
- **Senha**: password

### Testes

```bash
# Executar todos os testes
php artisan test

# Executar testes específicos
php artisan test --filter=PixTest
```

## 📊 API Endpoints

### Autenticação
- `POST /api/login` - Login do usuário
- `POST /api/logout` - Logout do usuário

### PIX
- `GET /api/pix` - Listar PIX do usuário
- `POST /api/pix` - Criar novo PIX
- `GET /api/pix/stats` - Estatísticas dos PIX
- `POST /api/pix/{token}` - Confirmar pagamento

### Notificações
- `GET /api/notifications` - Listar notificações
- `GET /api/notifications/unread-count` - Contador de não lidas
- `POST /api/notifications/{id}/read` - Marcar como lida
