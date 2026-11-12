#!/usr/bin/env bash
# Executa o PHP de desenvolvimento com as extensões exigidas pelo projeto.
# Ordem de busca: variável PHP_BIN, "php" no PATH, instalação do winget (Windows).
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

find_php() {
  if [[ -n "${PHP_BIN:-}" && -x "${PHP_BIN}" ]]; then echo "$PHP_BIN"; return; fi
  if command -v php >/dev/null 2>&1; then command -v php; return; fi
  local d
  for d in "$HOME"/AppData/Local/Microsoft/WinGet/Packages/PHP.PHP*; do
    if [[ -x "$d/php.exe" ]]; then echo "$d/php.exe"; return; fi
  done
  echo "PHP não encontrado. Instale o PHP 8.1+ ou defina PHP_BIN." >&2
  exit 1
}

PHP="$(find_php)"
PHPDIR="$(dirname "$PHP")"
ARGS=()
# Em instalações sem php.ini (ex.: winget), carrega o ini de desenvolvimento do projeto.
if [[ -z "$("$PHP" -r 'echo php_ini_loaded_file();')" ]]; then
  EXTDIR="$PHPDIR/ext"
  if command -v cygpath >/dev/null 2>&1; then EXTDIR="$(cygpath -w "$EXTDIR")"; fi
  ARGS+=(-c "$ROOT/bin/php-dev.ini" -d "extension_dir=$EXTDIR")
fi
exec "$PHP" "${ARGS[@]}" "$@"
