#!/usr/bin/env bash
# PHPStan analiz — baseline aktif. Yeni hata varsa 1 ile çıkar.
# Kullanım:  ./tools/run-phpstan.sh
# Baseline yenilemek:  ./tools/run-phpstan.sh --generate-baseline phpstan-baseline.neon

set -euo pipefail
cd "$(dirname "$0")/.."
php -d memory_limit=1024M tools/phpstan.phar analyse --no-progress "$@"
