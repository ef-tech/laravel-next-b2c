APP_NAME=Laravel
APP_ENV=testing
APP_KEY=base64:test-key-for-postgresql-testing
APP_DEBUG=true
APP_URL=http://localhost

# PostgreSQL設定（本番同等テスト用）
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=13432
DB_DATABASE=testing
DB_USERNAME=sail
DB_PASSWORD=password

# PostgreSQLタイムアウト設定（テスト用に短縮）
DB_STATEMENT_TIMEOUT=5000        # 5秒（テスト高速化のため短縮）
DB_IDLE_TX_TIMEOUT=5000          # 5秒
DB_LOCK_TIMEOUT=0                # デッドロック即座検知
DB_CONNECT_TIMEOUT=5             # 5秒

# PostgreSQLアプリケーション名
DB_APP_NAME=laravel-next-b2c-api-test

# 最適化設定
BCRYPT_ROUNDS=4
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
LOG_LEVEL=emergency

# Redis設定（必要に応じて）
REDIS_HOST=127.0.0.1
REDIS_PORT=13379

# 無効化設定
PULSE_ENABLED=false
TELESCOPE_ENABLED=false
NIGHTWATCH_ENABLED=false