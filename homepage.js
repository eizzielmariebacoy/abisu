const FIELDS = {
  temp: { label: "Enter °C", key: "temp", type: "number" },
  date: { label: "Enter date", key: "date", type: "date" },
  memo: { label: "Enter memo", key: "memo", type: "text" }
};

const TYPES = {
  heat: {
    label: "Heat Advisory",
    fields: ["temp", "date"],
    levels: {
      caution: { label: "Caution", min: 27, max: 32, sample: 32 },
      extremeCaution: { label: "Extreme Caution", min: 33, max: 49, sample: 41 },
      danger: { label: "Danger", min: 41, max: 51, sample: 50 },
      extremeDanger: { label: "Extreme Danger", min: 52, max: 92, sample: 52 }
    },
    build: (lv, v, L) =>
      `AbiSU HEAT ADVISORY:\n${L.label.toUpperCase()} level on ${v.date}, heat index ${v.temp}degree Celsius. Avoid outdoor activity between 10AM - 3PM. Stay hydrated.`,
    sample: lv => ({ temp: lv.sample })
  },
  rain: {
    label: "Rainfall Advisory",
    fields: ["date"],
    levels: {
      yellow: { label: "Yellow", text: "moderate rain expected. Watch for slippery road. Bring umbrella." },
      orange: { label: "Orange", text: "heavy rain expected. Flooding possible in low-lying areas. Avoid unnecessary travel." },
      red: { label: "Red", text: "intense rainfall expected. Serious flooding likely. Stay indoors and be ready to evacuate." }
    },
    build: (lv, v, L) =>
      `AbiSU RAINFALL ADVISORY:\n${L.label.toUpperCase()} WARNING on ${v.date}, ${L.text}`,
    sample: () => ({})
  },
  suspend: {
    label: "Suspension Notice",
    fields: ["memo", "date"],
    levels: {
      f2f: { label: "Face-to-face classes", text: "FACE-TO-FACE classes SUSPENDED", online: true },
      all: { label: "All classes", text: "ALL CLASSES SUSPENDED", online: false },
      morning: { label: "Morning classes", text: "MORNING CLASSES SUSPENDED", online: true },
      afternoon: { label: "Afternoon classes", text: "AFTERNOON CLASSES SUSPENDED", online: true }
    },
    build: (lv, v, L) =>
      `AbiSU CLASS SUSPENSION NOTICE:\n${L.text} on ${v.date} per Memo${v.memo}.${L.online ? " Online classes proceed as scheduled." : ""}`,
    sample: () => ({})
  }
};

const PLACEHOLDER = { temp: "[32]", date: "[MM-DD-YYYY]", memo: "[000, s.0000]" };

// State
const state = { type: "heat", level: "caution", values: { temp: "", date: "", memo: "" } };
const $ = id => document.getElementById(id);
const sendTarget = document.getElementById("sendTarget");

// Helpers
function formatDate(iso) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(iso)) return "";
  const [y, m, d] = iso.split("-");
  return `${m}-${d}-${y}`;
}

function currentValues() {
  const v = state.values;
  const t = TYPES[state.type], L = t.levels[state.level];
  return {
    temp: v.temp.trim() ? Number(v.temp) : (t.sample(L).temp ?? PLACEHOLDER.temp).toString().replace(/^(\d+)$/, "[$1]"),
    date: formatDate(v.date) || PLACEHOLDER.date,
    memo: v.memo.trim() || PLACEHOLDER.memo
  };
}

function messageText() {
  const t = TYPES[state.type];
  return t.build(state.level, currentValues(), t.levels[state.level]);
}

// Rendering
function makeChip(text, pressed, onClick) {
  const b = document.createElement("button");
  b.type = "button"; b.className = "chip"; b.textContent = text;
  b.setAttribute("aria-pressed", pressed);
  b.addEventListener("click", onClick);
  return b;
}

function renderTypes() {
  if (!$("types")) return;
  $("types").replaceChildren(...Object.entries(TYPES).map(([k, t]) =>
    makeChip(t.label, k === state.type, () => {
      state.type = k;
      state.level = Object.keys(t.levels)[0];
      renderAll();
    })));
}

function renderLevels() {
  if (!$("levels")) return;
  const t = TYPES[state.type];
  $("levels").replaceChildren(...Object.entries(t.levels).map(([k, l]) =>
    makeChip(l.label, k === state.level, () => {
      state.level = k;
      renderLevels();
      renderPreview();
    })));
}

function renderForm() {
  const form = $("form");
  if (!form) return;
  form.replaceChildren(...TYPES[state.type].fields.map(name => {
    const f = FIELDS[name];
    const wrap = document.createElement("div"); wrap.className = "field";
    const input = document.createElement("input");
    input.id = "f-" + name; input.type = f.type; input.placeholder = f.label;
    input.setAttribute("aria-label", f.label);
    if (name === "temp") { input.step = "0.1"; input.inputMode = "decimal"; }
    input.value = state.values[name];
    input.addEventListener("input", () => {
      state.values[name] = input.value;
      input.classList.remove("bad");
      wrap.querySelector(".err").textContent = "";
      renderPreview();
    });
    const err = document.createElement("div"); err.className = "err";
    wrap.append(input, err);
    return wrap;
  }));
}

function renderPreview() {
  if (!$("preview") || !$("count")) return;
  const text = messageText();
  $("preview").textContent = text;
  $("count").textContent = text.length + " characters";
}

function renderAll() {
  renderTypes();
  renderLevels();
  renderForm();
  renderPreview();
}

// Validation
function validate() {
  let ok = true;
  const t = TYPES[state.type];
  const level = t.levels[state.level];

  for (const name of t.fields) {
    const val = state.values[name].trim();
    let msg = "";

    if (!val) {
      msg = "This field is required.";
    } else if (name === "temp") {
      const n = Number(val);

      if (isNaN(n) || n < 20 || n > 70) {
        msg = "Enter a temperature between 20 and 70 °C.";
      } else if (level.min !== undefined && level.max !== undefined && (n < level.min || n > level.max)) {
        msg = `For ${level.label}, enter ${level.min}–${level.max} °C.`;
      }
    }

    const input = $("f-" + name);
    if (input) {
      input.classList.toggle("bad", !!msg);
      const err = input.parentElement.querySelector(".err");
      if (err) err.textContent = msg;
    }

    if (msg) ok = false;
  }

  return ok;
}

// ---------- Sending ----------
function toast(text) {
  const t = $("toast");
  if (!t) return;
  t.textContent = text;
  t.classList.add("show");
  setTimeout(() => t.classList.remove("show"), 2800);
}

function openModal() {
  if (!$("summary") || !$("overlay") || !$("confirm")) return;
  $("summary").textContent = messageText();
  $("overlay").classList.add("open");
  $("confirm").focus();
}

function closeModal() {
  const overlay = $("overlay");
  const sendBtn = $("send");
  if (overlay) overlay.classList.remove("open");
  if (sendBtn) sendBtn.focus();
}

const settingsBtn = document.getElementById("settingsBtn");
const settingsPanel = document.getElementById("settingsPanel");

if (settingsBtn && settingsPanel) {
  settingsBtn.addEventListener("click", () => {
    settingsPanel.hidden = !settingsPanel.hidden;
  });
}

if (sendTarget) {
  sendTarget.addEventListener("change", (e) => {
    state.sendTarget = e.target.value;
  });
}

const sendBtn = document.getElementById("send");
if (sendBtn) {
  sendBtn.addEventListener("click", () => {
    if (validate()) openModal();
  });
}

const cancelBtn = document.getElementById("cancel");
if (cancelBtn) cancelBtn.addEventListener("click", closeModal);

const overlay = document.getElementById("overlay");
if (overlay) {
  overlay.addEventListener("click", e => {
    if (e.target.id === "overlay") closeModal();
  });
}

document.addEventListener("keydown", e => {
  if (e.key === "Escape") closeModal();
});

const confirmBtn = document.getElementById("confirm");
if (confirmBtn) {
  confirmBtn.addEventListener("click", async () => {
    const btn = confirmBtn;
    btn.disabled = true;
    btn.textContent = "Sending…";

    try {
      const msg = messageText();

      const formData = new FormData();
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      formData.append("message", msg);
      formData.append("send_target", sendTarget ? sendTarget.value : "all");
      formData.append("csrf_token", csrfMeta ? csrfMeta.content : "");

      const response = await fetch("api.php", {
        method: "POST",
        body: formData
      });
      let result;
      try {
        result = await response.json();
      } catch {
        throw new Error("The server returned an invalid response. Check the PHP error log.");
      }

      if (!response.ok || result.status !== "success") {
        const detail = typeof result.detail === "string" ? result.detail : "";
        const providerStatus = Number.isInteger(result.provider_http_code)
          ? `Provider HTTP ${result.provider_http_code}. `
          : "";
        if (detail) console.error("SMS provider response:", detail);
        throw new Error(
          providerStatus || detail
            ? `${result.message || "SMS could not be sent."} ${providerStatus}${detail ? `Provider details: ${detail}` : ""}`
            : result.message || "SMS could not be sent."
        );
      }

      toast(`SMS accepted by provider for ${result.sent_count} recipient${result.sent_count === 1 ? "" : "s"}.`);
      state.values = { temp: "", date: "", memo: "" };
      renderForm();
      renderPreview();
    } catch (err) {
      console.error(err);
      toast(err instanceof Error ? err.message : "Could not send SMS. Try again.");
    } finally {
      btn.disabled = false;
      btn.textContent = "Confirm";
      closeModal();
    }
  });
}

renderAll();
