/**
 * BooKi Smart Tracking & Attribution Intelligence Engine.
 * Captures ad parameters (gclid, fbclid, UTMs), click timestamps,
 * heatmap interactions, and lead extraction signals.
 */
(function () {
  'use strict';

  function getCookie(name) {
    const value = '; ' + document.cookie;
    const parts = value.split('; ' + name + '=');
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
  }

  function setCookie(name, val, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days || 30) * 24 * 60 * 60 * 1000);
    document.cookie = name + '=' + encodeURIComponent(val) + ';path=/;expires=' + d.toUTCString();
  }

  // Session ID
  let sessionId = getCookie('booki_session_id') || localStorage.getItem('booki_session_id');
  if (!sessionId) {
    sessionId = 'bks_' + Math.random().toString(36).substring(2, 15) + Date.now().toString(36);
    setCookie('booki_session_id', sessionId, 30);
    localStorage.setItem('booki_session_id', sessionId);
  }

  // Parse URL search parameters
  const urlParams = new URLSearchParams(window.location.search);
  const utmSource = urlParams.get('utm_source');
  const utmMedium = urlParams.get('utm_medium');
  const utmCampaign = urlParams.get('utm_campaign');
  const utmTerm = urlParams.get('utm_term');
  const utmContent = urlParams.get('utm_content');
  const gclid = urlParams.get('gclid');
  const fbclid = urlParams.get('fbclid');

  const hasAdParams = utmSource || gclid || fbclid || utmCampaign;

  // Click timestamp
  let clickTs = localStorage.getItem('booki_click_ts');
  if (hasAdParams || !clickTs) {
    const now = new Date();
    clickTs = now.getFullYear() + '-' +
      String(now.getMonth() + 1).padStart(2, '0') + '-' +
      String(now.getDate()).padStart(2, '0') + ' ' +
      String(now.getHours()).padStart(2, '0') + ':' +
      String(now.getMinutes()).padStart(2, '0') + ':' +
      String(now.getSeconds()).padStart(2, '0');
    localStorage.setItem('booki_click_ts', clickTs);
  }

  // Record initial visit if ad params present
  if (hasAdParams) {
    const visitData = {
      session_id: sessionId,
      utm_source: utmSource,
      utm_medium: utmMedium,
      utm_campaign: utmCampaign,
      utm_term: utmTerm,
      utm_content: utmContent,
      gclid: gclid,
      fbclid: fbclid,
      referrer: document.referrer,
      click_timestamp: clickTs,
      user_agent: navigator.userAgent
    };

    try {
      fetch('/marketing/track_visit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(visitData)
      }).catch(function () {});
    } catch (e) {}
  }

  // Heatmap & Interactions
  const clicks = [];
  let maxScrollDepth = 0;
  const startTime = Date.now();
  const formClues = {};

  document.addEventListener('click', function (e) {
    try {
      const target = e.target;
      const tag = target.tagName.toLowerCase();
      const text = (target.innerText || target.value || '').substring(0, 40).trim();
      const clickInfo = {
        tag: tag,
        id: target.id || null,
        class: target.className || null,
        text: text,
        x: Math.round(e.pageX),
        y: Math.round(e.pageY),
        t: Math.round((Date.now() - startTime) / 1000)
      };
      clicks.push(clickInfo);
      if (clicks.length > 30) clicks.shift();
    } catch (err) {}
  }, true);

  // Track scroll depth
  window.addEventListener('scroll', function () {
    const h = document.documentElement,
      b = document.body,
      st = 'scrollTop',
      sh = 'scrollHeight';
    const percent = Math.round(((h[st] || b[st]) / ((h[sh] || b[sh]) - h.clientHeight)) * 100);
    if (!isNaN(percent) && percent > maxScrollDepth) {
      maxScrollDepth = percent;
    }
  }, { passive: true });

  // Track form input clues for identity extraction (phone, email, name)
  document.addEventListener('input', function (e) {
    try {
      const input = e.target;
      const name = (input.name || input.id || '').toLowerCase();
      const val = (input.value || '').trim();

      if (name.includes('phone') || name.includes('tel') || input.type === 'tel') {
        if (val.length >= 7) formClues.phone = val;
      } else if (name.includes('email') || input.type === 'email') {
        if (val.includes('@') && val.includes('.')) formClues.email = val;
      } else if (name.includes('first_name') || name.includes('last_name') || name.includes('ad') || name.includes('name')) {
        if (val.length >= 2) formClues.name = val;
      }
    } catch (err) {}
  }, true);

  // Send telemetry
  function sendTelemetry() {
    if (clicks.length === 0 && Object.keys(formClues).length === 0) return;

    const payload = {
      session_id: sessionId,
      heatmap_data: {
        clicks: clicks,
        scroll_depth: maxScrollDepth,
        time_on_page: Math.round((Date.now() - startTime) / 1000)
      },
      clues: formClues
    };

    const dataStr = JSON.stringify(payload);

    if (navigator.sendBeacon) {
      navigator.sendBeacon('/marketing/track_interaction', dataStr);
    } else {
      try {
        fetch('/marketing/track_interaction', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: dataStr,
          keepalive: true
        }).catch(function () {});
      } catch (e) {}
    }
  }

  // Send on visibility change and beforeunload
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') {
      sendTelemetry();
    }
  });

  window.addEventListener('beforeunload', sendTelemetry);

  // Periodic heartbeat every 20 seconds
  setInterval(sendTelemetry, 20000);
})();
