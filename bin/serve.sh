#!/usr/bin/env bash
# Servidor local de desenvolvimento.
# Uso: bin/serve.sh [porta]   (padrão 8080) — acesse http://localhost:PORTA/
# Os dados ficam em ../teia-ade-sampa-data (ou em TEIA_DATA_DIR, se definida).
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${1:-8080}"
echo "TEIA em http://localhost:${PORT}/  (Ctrl+C para parar)"
exec "$ROOT/bin/php.sh" -S "127.0.0.1:${PORT}" -t "$ROOT/public" "$ROOT/public/index.php"
