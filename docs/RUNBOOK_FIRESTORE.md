Runbook: Firestore + Mock Modes

Mock only (no Firestore calls)
cd "/Users/bbaa/Downloads/Take Me" && \
/opt/homebrew/opt/php@8.2/bin/php artisan optimize:clear && \
MOCK_MODE=true FIRESTORE_ENABLED=true \
FF_ADMINS_FIRESTORE=true FF_RIDES_FIRESTORE=true FF_PAYMENTS_FIRESTORE=true \
FF_MAIL_TEMPLATES_FIRESTORE=true FF_SOS_FIRESTORE=true \
APP_ENV=local APP_DEBUG=true LOG_LEVEL=debug \
/opt/homebrew/opt/php@8.2/bin/php artisan serve --host=127.0.0.1 --port=8002

Firestore real only
cd "/Users/bbaa/Downloads/Take Me" && \
/opt/homebrew/opt/php@8.2/bin/php artisan optimize:clear && \
export FIREBASE_SERVICE_ACCOUNT_PATH="/Users/bbaa/Downloads/Take Me/storage/app/firebase/serviceAccount.json" && \
export GOOGLE_APPLICATION_CREDENTIALS="$FIREBASE_SERVICE_ACCOUNT_PATH" && \
export GOOGLE_CLOUD_PROJECT="takemeusers" && \
export FIREBASE_PROJECT_ID="takemeusers" && \
MOCK_MODE=false FIRESTORE_ENABLED=true \
FF_ADMINS_FIRESTORE=true FF_RIDES_FIRESTORE=true FF_PAYMENTS_FIRESTORE=true \
FF_MAIL_TEMPLATES_FIRESTORE=true FF_SOS_FIRESTORE=true \
APP_ENV=local APP_DEBUG=true LOG_LEVEL=debug \
/opt/homebrew/opt/php@8.2/bin/php artisan serve --host=127.0.0.1 --port=8002
