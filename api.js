// ============================================================
//  Rural WiFi BillFlow — api.js
//  Central API helper + Realtime (SSE + polling) module
// ============================================================

const API = {
  auth:      'api/auth.php',
  customers: 'api/customers.php',
  invoices:  'api/invoices.php',
  payments:  'api/payments.php',
  plans:     'api/plans.php',
  messages:  'api/messages.php',
  notifs:    'api/notifications.php',
  settings:  'api/settings.php',
};

async function api(url, body = null) {
  try {
    const opts = {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(body ?? {}),
    };
    const res  = await fetch(url, opts);
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch {
      console.error('[API] Non-JSON response from', url, ':', text.slice(0, 200));
      return { success: false, message: 'Server error (non-JSON response).' };
    }
  } catch (err) {
    console.error('[API] Fetch error:', url, err);
    return { success: false, message: 'Network error: ' + err.message };
  }
}

async function doLogout() {
  await api(API.auth, { action: 'logout' });
  window.location.href = document.title.toLowerCase().includes('admin')
    ? 'admin_login.php'
    : 'customer_login.php';
}

async function apiForm(url, formData) {
  try {
    const res  = await fetch(url, { method: 'POST', body: formData });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch {
      console.error('[API] Non-JSON response from', url, ':', text.slice(0, 200));
      return { success: false, message: 'Server error (non-JSON response).' };
    }
  } catch (err) {
    console.error('[API] Fetch error:', url, err);
    return { success: false, message: 'Network error: ' + err.message };
  }
}

async function checkSession(expectedRole) {
  try {
    const res = await api(API.auth, { action: 'me' });
    if (!res.success || !res.data) {
      window.location.href = expectedRole === 'admin'
        ? 'admin_login.php'
        : 'customer_login.php';
      return null;
    }
    if (res.data.role !== expectedRole) {
      window.location.href = expectedRole === 'admin'
        ? 'admin_login.php'
        : 'customer_login.php';
      return null;
    }
    return res.data;
  } catch {
    window.location.href = expectedRole === 'admin'
      ? 'admin_login.php'
      : 'customer_login.php';
    return null;
  }
}

const Realtime = (() => {
  const timers      = {};
  const cache       = {};
  const sseHandlers = {};
  let   sseSource   = null;

  function start(name, fn, intervalMs) {
    stop(name);
    fn();
    timers[name] = setInterval(fn, intervalMs);
  }

  function stop(name) {
    if (timers[name]) {
      clearInterval(timers[name]);
      delete timers[name];
    }
  }

  function stopExcept(...keep) {
    Object.keys(timers).forEach(name => {
      if (!keep.includes(name)) stop(name);
    });
  }

  function invalidate(key) { delete cache[key]; }
  function invalidateAll() { Object.keys(cache).forEach(k => delete cache[k]); }

  async function ifChanged(key, fetchFn, renderFn) {
    try {
      const data = await fetchFn();
      const hash = JSON.stringify(data);
      if (cache[key] === hash) return;
      cache[key] = hash;
      await renderFn(data);
    } catch (e) {
      console.warn('[Realtime] ifChanged error:', key, e);
    }
  }

  function onSSE(event, cb) {
    if (!sseHandlers[event]) sseHandlers[event] = [];
    sseHandlers[event].push(cb);
  }

  function connectSSE() {
    if (sseSource) { sseSource.close(); sseSource = null; }
    try {
      sseSource = new EventSource('api/sse.php');
      sseSource.addEventListener('reconnect', () => setTimeout(connectSSE, 2000));
      sseSource.onerror = () => {
        sseSource.close(); sseSource = null;
        setTimeout(connectSSE, 15000);
      };
      Object.keys(sseHandlers).forEach(event => {
        sseSource.addEventListener(event, (e) => {
          try {
            const data = JSON.parse(e.data);
            sseHandlers[event].forEach(cb => cb(data));
          } catch {}
        });
      });
    } catch (e) {
      console.warn('[SSE] Not supported or blocked:', e);
    }
  }

  return { start, stop, stopExcept, invalidate, invalidateAll, ifChanged, onSSE, connectSSE };
})();
