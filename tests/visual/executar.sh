#!/usr/bin/env bash
# Teste visual completo (DESENVOLVIMENTO): prepara conteúdo de teste temporário, sobe o servidor,
# roda a comparação com o Figma no Chrome headless e gera tests/visual/reports/relatorio.html.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
NODE="${NODE_BIN:-$(command -v node || echo "/c/Program Files/nodejs/node.exe")}"
DATA="$(mktemp -d)"
PORT="${PORT:-8090}"
cleanup() { [[ -n "${PID:-}" ]] && kill "$PID" 2>/dev/null || true; rm -rf "$DATA"; }
trap cleanup EXIT

if command -v cygpath >/dev/null 2>&1; then DATA_NATIVE="$(cygpath -w "$DATA")"; else DATA_NATIVE="$DATA"; fi
"$ROOT/bin/php.sh" "$ROOT/tests/visual/preparar.php" "$DATA_NATIVE"
TEIA_CONTENT_DIR="$DATA_NATIVE" "$ROOT/bin/php.sh" -S "127.0.0.1:$PORT" -t "$ROOT/public" "$ROOT/public/index.php" >/dev/null 2>&1 &
PID=$!
sleep 1.5
"$NODE" "$ROOT/tests/visual/comparar.mjs" "http://127.0.0.1:$PORT"
