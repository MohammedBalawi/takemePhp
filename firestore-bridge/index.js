const path = require('path');
const express = require('express');
const cors = require('cors');
const admin = require('firebase-admin');
const https = require('https');

const app = express();
app.use(cors());
app.use(express.json({ limit: '2mb' }));

const serviceAccountPath =
  process.env.SERVICE_ACCOUNT_PATH ||
  path.join(__dirname, '../storage/app/firebase/serviceAccount.json');

admin.initializeApp({
  credential: admin.credential.cert(require(serviceAccountPath)),
});

app.get('/health', (_, res) => res.json({ ok: true }));

const FIREBASE_WEB_API_KEY = process.env.FIREBASE_WEB_API_KEY;

const callIdentityToolkit = (endpoint, payload) => {
  return new Promise((resolve, reject) => {
    if (!FIREBASE_WEB_API_KEY) {
      return reject(new Error('FIREBASE_WEB_API_KEY is not set'));
    }
    const url = new URL(`https://identitytoolkit.googleapis.com/v1/${endpoint}?key=${FIREBASE_WEB_API_KEY}`);
    const body = JSON.stringify(payload || {});
    const req = https.request(
      {
        method: 'POST',
        hostname: url.hostname,
        path: `${url.pathname}${url.search}`,
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(body),
        },
      },
      (res) => {
        let data = '';
        res.on('data', (chunk) => {
          data += chunk;
        });
        res.on('end', () => {
          let parsed = null;
          try {
            parsed = JSON.parse(data);
          } catch (err) {
            return reject(new Error('Invalid response from Identity Toolkit'));
          }
          if (res.statusCode >= 400) {
            const message =
              (parsed && parsed.error && parsed.error.message) ||
              `Identity Toolkit error (${res.statusCode})`;
            return reject(new Error(message));
          }
          return resolve(parsed);
        });
      }
    );
    req.on('error', (err) => reject(err));
    req.write(body);
    req.end();
  });
};

app.post('/auth/login', async (req, res) => {
  try {
    const { email, password } = req.body || {};
    if (!email || !password) {
      return res.status(400).json({ ok: false, error: 'email and password required' });
    }
    const data = await callIdentityToolkit('accounts:signInWithPassword', {
      email,
      password,
      returnSecureToken: true,
    });
    return res.json({
      ok: true,
      idToken: data.idToken,
      refreshToken: data.refreshToken,
      localId: data.localId,
      email: data.email,
      displayName: data.displayName || null,
    });
  } catch (e) {
    return res.status(401).json({ ok: false, error: String(e.message || e) });
  }
});

app.post('/auth/register', async (req, res) => {
  try {
    const { email, password, displayName } = req.body || {};
    if (!email || !password) {
      return res.status(400).json({ ok: false, error: 'email and password required' });
    }
    const data = await callIdentityToolkit('accounts:signUp', {
      email,
      password,
      returnSecureToken: true,
    });
    if (displayName) {
      await admin.auth().updateUser(data.localId, { displayName });
    }
    return res.json({
      ok: true,
      idToken: data.idToken,
      refreshToken: data.refreshToken,
      localId: data.localId,
      email: data.email,
    });
  } catch (e) {
    return res.status(400).json({ ok: false, error: String(e.message || e) });
  }
});

app.post('/auth/verify', async (req, res) => {
  try {
    const { idToken } = req.body || {};
    if (!idToken) {
      return res.status(400).json({ ok: false, error: 'idToken required' });
    }
    const decoded = await admin.auth().verifyIdToken(idToken);
    return res.json({ ok: true, decoded });
  } catch (e) {
    return res.status(401).json({ ok: false, error: String(e.message || e) });
  }
});

app.post('/firestore/set', async (req, res) => {
  try {
    const { collection, docId, data, merge = true } = req.body || {};
    if (!collection || !docId || typeof data !== 'object') {
      return res.status(400).json({ ok: false, error: 'collection, docId, data required' });
    }
    await admin.firestore().collection(collection).doc(String(docId)).set(data, { merge });
    return res.json({ ok: true });
  } catch (e) {
    return res.status(500).json({ ok: false, error: String(e) });
  }
});

app.get('/firestore/get', async (req, res) => {
  try {
    const { collection, docId } = req.query || {};
    if (!collection || !docId) {
      return res.status(400).json({ ok: false, error: 'collection and docId required' });
    }
    const snap = await admin.firestore().collection(String(collection)).doc(String(docId)).get();
    if (!snap.exists) return res.status(404).json({ ok: false, exists: false });
    return res.json({ ok: true, exists: true, data: snap.data() });
  } catch (e) {
    return res.status(500).json({ ok: false, error: String(e) });
  }
});

const port = process.env.PORT || 8787;
app.listen(port, () => console.log(`firestore-bridge on :${port}`));
