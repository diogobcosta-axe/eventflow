# 🎟️ EventFlow — Sistema de Gestão de Eventos

> Aplicação web em PHP para criação, gestão e inscrição em eventos. Inspirada no [3cket.com](https://3cket.com/pt).

---

## 📋 Descrição do Projeto

O **EventFlow** é um sistema completo de gestão de eventos desenvolvido em PHP puro com base de dados SQLite. Permite:

- Criação e divulgação de eventos com imagem, data, local e vagas
- Inscrição de participantes com controlo de lotação em tempo real
- Gestão de lista de presenças pelos organizadores
- Exportação de inscritos em CSV
- Painel de administração completo
- 3 perfis de utilizador: **Admin**, **Organizador**, **Participante**

---

## 🚀 Instalação

### Pré-requisitos
- PHP >= 8.0 com extensões: `pdo`, `pdo_sqlite`, `fileinfo`, `session`
- Servidor web: Apache (com `mod_rewrite`) ou Nginx
- Permissões de escrita nas pastas `database/` e `assets/uploads/events/`

### Passo a passo

```bash
# 1. Clonar/extrair o projeto
git clone https://github.com/grupo/eventflow.git
cd eventflow

# 2. Criar pastas necessárias
mkdir -p database assets/uploads/events

# 3. Permissões (Linux/macOS)
chmod 755 database assets/uploads/events

# 4. Configurar servidor (Apache — DocumentRoot deve apontar para a pasta eventflow/)
# O ficheiro .htaccess já está configurado

# 5. Aceder no browser
# http://localhost/eventflow/
# A base de dados é criada automaticamente no primeiro acesso
```

### Com PHP Built-in Server (desenvolvimento)
```bash
cd eventflow
php -S localhost:8000
# Aceder: http://localhost:8000
```

---

## 👤 Contas Demo

| Papel        | Email                  | Password  |
|-------------|------------------------|-----------|
| Admin        | admin@eventflow.pt     | admin123  |
| Organizador  | org@eventflow.pt       | org123    |
| Participante | user@eventflow.pt      | user123   |

---

## 🗂️ Estrutura de Ficheiros

```
eventflow/
├── config/
│   ├── database.php       # Configuração PDO + constantes
│   └── init_db.php        # Criação das tabelas + seed de dados
├── includes/
│   ├── auth.php           # Funções de autenticação e sessões
│   ├── helpers.php        # Funções auxiliares globais
│   ├── header.php         # Navbar + cabeçalho HTML
│   └── footer.php         # Rodapé HTML
├── pages/
│   ├── login.php          # Autenticação
│   ├── registo.php        # Registo de utilizador
│   ├── logout.php         # Terminar sessão
│   ├── eventos.php        # Listagem com filtros e paginação
│   ├── evento.php         # Detalhe de evento
│   ├── criar_evento.php   # Formulário de criação (organizador)
│   ├── editar_evento.php  # Edição de evento (organizador)
│   ├── apagar_evento.php  # Apagar evento (POST handler)
│   ├── meus_eventos.php   # Painel do organizador
│   ├── minhas_inscricoes.php  # Inscrições do participante
│   ├── presencas.php      # Lista de presenças (organizador)
│   ├── exportar_csv.php   # Export CSV de inscritos
│   ├── perfil.php         # Editar perfil e password
│   ├── admin.php          # Painel de administração
│   ├── ajax_inscricao.php # AJAX: inscrever/cancelar
│   └── ajax_presenca.php  # AJAX: marcar presença
├── assets/
│   ├── css/style.css      # CSS completo com variáveis
│   ├── js/main.js         # JavaScript: AJAX, validação, UI
│   └── uploads/events/    # Imagens carregadas
├── errors/
│   ├── 404.php            # Página não encontrada
│   ├── 403.php            # Acesso negado
│   └── 500.php            # Erro interno
├── database/              # Base de dados SQLite (auto-gerada)
├── .htaccess              # Configuração Apache
├── index.php              # Página inicial
└── README.md              # Este ficheiro
```

---

## 🗄️ Diagrama ER

```
utilizadores
├── id (PK)
├── nome
├── email (UNIQUE)
├── password_hash
├── papel (admin|organizador|participante)
├── avatar
├── ativo
└── criado_em

categorias_evento
├── id (PK)
├── nome (UNIQUE)
├── icone
├── cor
└── criado_em

eventos
├── id (PK)
├── titulo
├── descricao
├── local
├── data_inicio
├── data_fim
├── vagas
├── imagem
├── organizador_id (FK → utilizadores.id)
├── categoria_id   (FK → categorias_evento.id)
├── estado (ativo|cancelado|encerrado)
└── criado_em

inscricoes
├── id (PK)
├── utilizador_id (FK → utilizadores.id)
├── evento_id     (FK → eventos.id)
├── estado (confirmada|cancelada|presenca)
└── criado_em

Relações:
  utilizadores 1─────< eventos       (um org. tem muitos eventos)
  utilizadores 1─────< inscricoes    (um user tem muitas inscrições)
  eventos      1─────< inscricoes    (um evento tem muitas inscrições)
  categorias   1─────< eventos       (uma categoria tem muitos eventos)
```

---

## ✅ Requisitos Implementados

### Obrigatórios
- [x] PHP + HTML5 + CSS3, sem frameworks back-end
- [x] SQLite com 4 tabelas relacionadas (utilizadores, eventos, inscricoes, categorias_evento)
- [x] Autenticação: registo, login, logout com sessões PHP
- [x] CRUD completo para Eventos
- [x] Validação de formulários (servidor PHP + cliente HTML5/JS)
- [x] Proteção SQL Injection com PDO + prepared statements
- [x] Layout responsivo com CSS Grid + Flexbox
- [x] Separação: `config/`, `includes/`, `pages/`
- [x] README com instruções de instalação

### Valorizados
- [x] Upload e gestão de imagens com validação de tipo e tamanho
- [x] Paginação de listagens
- [x] Pesquisa e filtros dinâmicos
- [x] 3 perfis: admin, organizador, participante
- [x] AJAX/Fetch para inscrição e marcação de presenças sem reload
- [x] CSS próprio com variáveis CSS e design consistente
- [x] Páginas de erro customizadas (404, 403, 500)
- [x] Exportação de inscritos em CSV

---

## 🛡️ Segurança

- **PDO + prepared statements** em todas as queries (sem SQL Injection)
- **CSRF tokens** em todos os formulários POST
- **password_hash/verify** com bcrypt para passwords
- **session_regenerate_id** no login
- **htmlspecialchars** em todo o output (sem XSS)
- **Validação de tipos MIME** para uploads
- **Proteção de ficheiros** via `.htaccess`

---

## 👥 Grupo

- Elemento 1: ...
- Elemento 2: ...

Disciplina: Programação Web · Ano letivo 2025/2026
