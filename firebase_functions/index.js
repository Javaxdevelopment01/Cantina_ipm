const functions = require("firebase-functions");
const admin = require("firebase-admin");
const fetch = require("node-fetch");
const crypto = require("crypto");

admin.initializeApp();
const db = admin.firestore();

const MCX_KEY = functions.config().mcx && functions.config().mcx.key;
const MCX_SECRET = functions.config().mcx && functions.config().mcx.secret;
const MCX_ENDPOINT = functions.config().mcx && functions.config().mcx.endpoint;

exports.createPayment = functions.https.onCall(async (data, ctx) => {
  const userId = ctx.auth ? ctx.auth.uid : data.userId || "guest";
  const amount = Number(data.amount);
  const currency = data.currency || "AOA";
  const method = data.method || "qr";

  if (!amount || amount <= 0) {
    throw new functions.https.HttpsError("invalid-argument", "Valor inválido");
  }

  const paymentRef = db.collection("payments").doc();
  const paymentDoc = {
    userId,
    amount,
    currency,
    method,
    status: "pending",
    createdAt: admin.firestore.FieldValue.serverTimestamp(),
  };
  await paymentRef.set(paymentDoc);

  const payload = {
    merchantKey: MCX_KEY,
    reference: paymentRef.id,
    amount,
    currency,
    method,
    callbackUrl: `https://${process.env.GCLOUD_PROJECT}.cloudfunctions.net/mcxWebhook`,
  };

  let mcxResp = { error: "no-response" };
  try {
    const res = await fetch(MCX_ENDPOINT + "/payments", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    mcxResp = await res.json();
  } catch (e) {
    mcxResp = { error: String(e) };
  }

  await paymentRef.update({
    mcxRequest: payload,
    mcxResponse: mcxResp,
    status: mcxResp && mcxResp.success ? "waiting" : "failed",
    updatedAt: admin.firestore.FieldValue.serverTimestamp(),
  });

  return { paymentId: paymentRef.id, mcx: mcxResp };
});

exports.mcxWebhook = functions.https.onRequest(async (req, res) => {
  const signature = req.get("X-MCX-Signature") || req.get("x-mcx-signature");
  const body = JSON.stringify(req.body || {});
  const expected = MCX_SECRET
    ? crypto.createHmac("sha256", MCX_SECRET).update(body).digest("hex")
    : null;
  if (expected && signature !== expected) {
    return res.status(401).send("Invalid signature");
  }

  const { reference, status, transactionId } = req.body || {};
  if (!reference) return res.status(400).send("Missing reference");

  const ref = db.collection("payments").doc(reference);
  const snap = await ref.get();
  if (!snap.exists) return res.status(404).send("Not found");

  await ref.update({
    status,
    transactionId,
    mcxWebhook: req.body,
    updatedAt: admin.firestore.FieldValue.serverTimestamp(),
  });

  res.status(200).send("ok");
});
