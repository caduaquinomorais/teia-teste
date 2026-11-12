-- 001: estrutura inicial do TEIA

CREATE TABLE settings (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL
);

CREATE TABLE users (
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    username             TEXT NOT NULL UNIQUE COLLATE NOCASE,
    name                 TEXT NOT NULL,
    email                TEXT,
    password_hash        TEXT NOT NULL,
    role                 TEXT NOT NULL CHECK (role IN ('admin', 'editor')),
    active               INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
    must_change_password INTEGER NOT NULL DEFAULT 1 CHECK (must_change_password IN (0, 1)),
    session_version      INTEGER NOT NULL DEFAULT 1,
    created_at           TEXT NOT NULL,
    updated_at           TEXT NOT NULL,
    last_login_at        TEXT
);

-- Permissão por página (negada por padrão: sem linha = sem acesso).
CREATE TABLE page_permissions (
    user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    page_slug  TEXT NOT NULL,
    granted_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    granted_at TEXT NOT NULL,
    PRIMARY KEY (user_id, page_slug)
);

-- Conteúdo publicado de cada página (JSON). Ausência de linha = valores padrão do Figma.
CREATE TABLE pages (
    slug       TEXT PRIMARY KEY,
    version    INTEGER NOT NULL,
    data       TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    updated_by INTEGER REFERENCES users (id) ON DELETE SET NULL
);

-- Histórico completo de revisões.
CREATE TABLE page_revisions (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    page_slug  TEXT NOT NULL,
    version    INTEGER NOT NULL,
    data       TEXT NOT NULL,
    created_at TEXT NOT NULL,
    created_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    note       TEXT,
    UNIQUE (page_slug, version)
);
CREATE INDEX idx_page_revisions_slug ON page_revisions (page_slug, version DESC);

-- Arquivos enviados pelo painel (imagens reprocessadas e PDFs).
CREATE TABLE media (
    id            TEXT PRIMARY KEY,
    kind          TEXT NOT NULL CHECK (kind IN ('image', 'pdf')),
    original_name TEXT NOT NULL,
    mime          TEXT NOT NULL,
    size          INTEGER NOT NULL,
    width         INTEGER,
    height        INTEGER,
    variants      TEXT NOT NULL,
    created_at    TEXT NOT NULL,
    created_by    INTEGER REFERENCES users (id) ON DELETE SET NULL
);

-- Limites de tentativas (chaves HMAC; nunca guardamos IP puro).
CREATE TABLE rate_limits (
    bucket       TEXT PRIMARY KEY,
    count        INTEGER NOT NULL,
    window_start INTEGER NOT NULL
);

-- Auditoria de operações administrativas.
CREATE TABLE audit_log (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER REFERENCES users (id) ON DELETE SET NULL,
    username   TEXT,
    action     TEXT NOT NULL,
    target     TEXT,
    details    TEXT,
    ip_hash    TEXT,
    created_at TEXT NOT NULL
);
CREATE INDEX idx_audit_created ON audit_log (created_at DESC);
