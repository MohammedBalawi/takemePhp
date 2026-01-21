Runbook: Firestore Only

Firestore only (no mock mode)
cd "/Users/bbaa/Downloads/Take Me" && \
/opt/homebrew/opt/php@8.2/bin/php artisan optimize:clear && \
FIRESTORE_ENABLED=true \
APP_ENV=local APP_DEBUG=true LOG_LEVEL=debug \
/opt/homebrew/opt/php@8.2/bin/php artisan serve --host=127.0.0.1 --port=8002

Firestore real only (with service account)
cd "/Users/bbaa/Downloads/Take Me" && \
/opt/homebrew/opt/php@8.2/bin/php artisan optimize:clear && \
export FIREBASE_SERVICE_ACCOUNT_PATH="/Users/bbaa/Downloads/Take Me/storage/app/firebase/serviceAccount.json" && \
export GOOGLE_APPLICATION_CREDENTIALS="$FIREBASE_SERVICE_ACCOUNT_PATH" && \
export GOOGLE_CLOUD_PROJECT="takemeusers" && \
export FIREBASE_PROJECT_ID="takemeusers" && \
FIRESTORE_ENABLED=true \
APP_ENV=local APP_DEBUG=true LOG_LEVEL=debug \
/opt/homebrew/opt/php@8.2/bin/php artisan serve --host=127.0.0.1 --port=8002
