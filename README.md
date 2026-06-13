# GreenBuddy

> Projeto de Aptidão Profissional (PAP) — Sistema de monitorização de humidade de vasos com interface web, base de dados MySQL e suporte a exposição pública via ngrok.

---

## Índice

- [Descrição do Projeto](#-descrição-do-projeto)
- [Tecnologias Utilizadas](#-tecnologias-utilizadas)
- [Pré-requisitos](#-pré-requisitos)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [Instalação e Configuração](#-instalação-e-configuração)
- [Execução](#-execução)
- [Base de Dados](#-base-de-dados)
- [Acesso à Aplicação](#-acesso-à-aplicação)
- [Resolução de Erros Comuns](#-resolução-de-erros-comuns)
- [Créditos](#-créditos)

---

## Descrição do Projeto

O **GreenBuddy** é uma aplicação web desenvolvida em PHP que permite monitorizar em tempo real a humidade de vasos de plantas. Os dados de humidade são registados na base de dados e visualizados através de uma interface web intuitiva, com suporte a autenticação de utilizadores, recuperação de password por e-mail e exposição pública do serviço via ngrok.

---

## Tecnologias Utilizadas

| Tecnologia | Versão | Função |
|---|---|---|
| PHP | 8.4 | Linguagem principal do servidor |
| MySQL | 8.0 | Base de dados relacional |
| phpMyAdmin | mais recente | Interface gráfica para a BD |
| Docker + Docker Compose | mais recente | Contentor para MySQL e phpMyAdmin |
| PHPMailer | ^7.1 | Envio de e-mails (recuperação de password) |
| Composer | mais recente | Gestão de dependências PHP |
| ngrok | mais recente | Exposição do servidor local à internet |

---

## Pré-requisitos

Antes de iniciar, certifica-te de que tens instalado na tua máquina:

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (inclui Docker Compose)
- [PHP 8.4](https://www.php.net/downloads) com extensão `php8.4-mysql`
- [Composer](https://getcomposer.org/)
- [Node.js + npm](https://nodejs.org/) (para instalar o ngrok)
- [ngrok](https://ngrok.com/) *(opcional — apenas para exposição pública)*

---

## Estrutura do Projeto

```
greenbuddy-main/
├── img/                  # Imagens e logótipo
├── vendor/               # Dependências PHP (Composer)
├── index.php             # Página inicial
├── login.php             # Autenticação de utilizadores
├── logout.php            # Terminar sessão
├── admin.php             # Painel de administração
├── recebe.php            # Receção de dados do sensor
├── ativacao.php          # Ativação de conta
├── recuperar.php         # Recuperação de password
├── nova_senha.php        # Definição de nova password
├── inexistente.php       # Página 404 personalizada
├── db.php                # Ligação à base de dados
├── bd.sql                # Script SQL para criar a base de dados
├── style.css             # Folha de estilos
├── sw.js                 # Service Worker (PWA)
├── manifest.json         # Manifesto da aplicação web
├── composer.json         # Dependências PHP
├── docker-compose.yml    # Configuração dos contentores Docker
└── README.md             # Este ficheiro
```

---

## Instalação e Configuração

### 1. Clonar ou extrair o projeto

Extrai o ficheiro `.zip` ou clona o repositório para uma pasta local:

```bash
git clone https://github.com/teu-utilizador/greenbuddy.git
cd greenbuddy-main
```

### 2. Instalar dependências PHP

Dentro da pasta do projeto, executa:

```bash
composer install
```

> Isto irá instalar o **PHPMailer** e outras dependências definidas no `composer.json`.

---

## Execução

### Passo 1 — Iniciar os contentores Docker (MySQL + phpMyAdmin)

```bash
docker compose up -d
```

> Isto inicia em segundo plano:
> - **MySQL** na porta `3306`
> - **phpMyAdmin** na porta `8080`

Para verificar se os contentores estão a correr:

```bash
docker ps
```

### Passo 2 — Importar a base de dados

1. Acede ao phpMyAdmin em: [http://localhost:8080](http://localhost:8080)
2. Inicia sessão com:
   - **Utilizador:** `green`
   - **Password:** `buddy`
3. Seleciona a base de dados `greenbuddydb`
4. Vai a **Importar** e seleciona o ficheiro `bd.sql`
5. Clica em **Executar**

> Em alternativa, podes importar pelo terminal:
> ```bash
> docker exec -i mysql_db mysql -u green -pbuddy greenbuddydb < bd.sql
> ```

### Passo 3 — Iniciar o servidor PHP

```bash
php -S localhost:7878
```

> A aplicação fica disponível em: [http://localhost:7878](http://localhost:7878)

---

## Acesso à Aplicação

| Serviço | URL |
|---|---|
| Aplicação Web | http://localhost:7878 |
| phpMyAdmin | http://localhost:8080 |

---

## Exposição Pública com ngrok *(opcional)*

Para tornar a aplicação acessível a partir da internet (por exemplo, para ligar um sensor físico remotamente):

### Instalar o ngrok

```bash
npm install -g ngrok
```

### Criar um domínio público

```bash
ngrok http 7878
```

> O ngrok irá gerar um URL público (ex: `https://abc123.ngrok.io`) que redireciona para o teu servidor local na porta `7878`.

---

## Base de Dados

A base de dados `greenbuddydb` contém as seguintes tabelas:

**`vaso`** — Registo dos vasos monitorizados

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | INT (PK, AUTO) | Identificador único |
| `descricao` | VARCHAR(250) | Descrição do vaso |
| `tamanho` | VARCHAR(200) | Tamanho do vaso |
| `localizacao` | VARCHAR(200) | Localização física |

**`vaso_humidade`** — Leituras de humidade registadas

| Campo | Tipo | Descrição |
|---|---|---|
| `id_humidade` | INT (PK, AUTO) | Identificador único |
| `data` | VARCHAR(100) | Data da leitura |
| `hora` | VARCHAR(50) | Hora da leitura |
| `percentagem` | VARCHAR(50) | Percentagem de humidade |

### Credenciais da Base de Dados

| Parâmetro | Valor |
|---|---|
| Host | `mysql` (Docker) / `127.0.0.1` (local) |
| Base de dados | `greenbuddydb` |
| Utilizador | `green` |
| Password | `buddy` |

---

## Resolução de Erros Comuns

### Erro: `could not find driver`

Ocorre quando a extensão PHP para MySQL não está instalada. Executa:

```bash
sudo apt update
sudo apt install php8.4-mysql -y
```

Depois reinicia o servidor PHP.

---

### Erro: `docker compose` não reconhecido

Certifica-te de que tens o **Docker Desktop** atualizado. Em versões antigas usa:

```bash
docker-compose up -d
```

---

### Erro de ligação à base de dados

Verifica se os contentores Docker estão a correr:

```bash
docker ps
```

Confirma também que o ficheiro `db.php` tem o host correto:
- Para usar **dentro do Docker**: `$host = "mysql";`
- Para usar **diretamente no sistema**: `$host = "127.0.0.1";`

---

### Contentores a parar inesperadamente

Consulta os logs do contentor MySQL:

```bash
docker logs mysql_db
```

---

## Créditos

Projeto desenvolvido no âmbito da **Prova de Aptidão Profissional (PAP)**.

- **Autor:** *Afonso Silva*
- **Curso:** *Técnico de Gestão e Programação de Sistemas Informáticos*