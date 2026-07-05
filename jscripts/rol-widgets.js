/**
 * rol-widgets.js
 * Widgets de rol que se hidratan via fetch() contra la API propia.
 * Inyectado por el plugin rolbridge.php en todas las paginas.
 *
 * Dependencias: ROL_API_URL (definida por el plugin)
 */

(function () {
  'use strict';

  // ─── Util: token desde cookie ───
  function getRolToken() {
    const match = document.cookie.match(/(?:^|;\s*)rol_token=([^;]*)/);
    return match ? match[1] : null;
  }

  // ─── Util: personaje activo desde cookie ───
  function getRolCharId() {
    const match = document.cookie.match(/(?:^|;\s*)rol_char_id=([^;]*)/);
    return match ? match[1] : null;
  }

  // ─── Headers comunes para fetch ───
  function apiHeaders() {
    const headers = { 'Content-Type': 'application/json' };
    const token = getRolToken();
    if (token) headers['Authorization'] = 'Bearer ' + token;
    return headers;
  }

  // ─── 1. Widget: ficha resumida en el postbit ───
  async function hydratePostbitWidgets() {
    const widgets = document.querySelectorAll('.rol-ficha-widget');
    if (!widgets.length) return;

    for (const el of widgets) {
      const userId = el.dataset.user;
      if (!userId) continue;

      // Loading state
      el.innerHTML = '<span class="rol-loading" style="opacity:0.5;font-size:0.78rem;">cargando...</span>';

      try {
        const res = await fetch(ROL_API_URL + '/personajes/activo/' + userId, {
          headers: apiHeaders(),
          signal: AbortSignal.timeout(3000),
        });
        const json = await res.json();

        if (json.success && json.data) {
          const pj = json.data;
          let html = '<div class="rol-postbit-char">';
          html += '<strong>' + escapeHtml(pj.nombre) + '</strong>';
          html += '<br><span class="rol-race">' + escapeHtml(pj.raza || '—') + '</span>';
          html += ' &middot; <span class="rol-class">' + escapeHtml(pj.clase || '—') + '</span>';

          if (pj.atributos && pj.atributos.length) {
            html += '<div class="rol-mini-stats">';
            for (const attr of pj.atributos) {
              var v = attr.valor;
              if (v === null || v === undefined || v === '?' || v === '') v = '—';
              html += '<span>' + escapeHtml(attr.clave) + ': ' + escapeHtml(v) + '</span> ';
            }
            html += '</div>';
          }

          html += '</div>';
          el.innerHTML = html;
        } else {
          el.innerHTML = '';
        }
      } catch (e) {
        el.innerHTML = '';
      }
    }
  }

  // ─── 2. Widget: selector de personaje al postear ───
  async function hydrateCharSelector() {
    const select = document.getElementById('rol-active-char');
    if (!select) return;

    try {
      const res = await fetch(ROL_API_URL + '/cuenta/mi-cuenta', {
        headers: apiHeaders(),
        signal: AbortSignal.timeout(3000),
      });
      const json = await res.json();

      if (json.success && json.data) {
        select.innerHTML = '';
        const chars = json.data.personajes || [];
        const activeCharId = getRolCharId();

        let hasActive = false;
        for (const ch of chars) {
          if (ch.estado !== 'aprobado') continue;
          const opt = document.createElement('option');
          opt.value = ch.id;
          opt.textContent = ch.nombre + (ch.activo ? ' (activo)' : '');
          if (ch.activo || String(ch.id) === activeCharId) {
            opt.selected = true;
            hasActive = true;
          }
          select.appendChild(opt);
        }

        if (!chars.length) {
          const opt = document.createElement('option');
          opt.textContent = 'No tienes personajes aprobados';
          opt.disabled = true;
          select.appendChild(opt);
        }

        if (!hasActive && chars.length) {
          // Seleccionar el primero aprobado por defecto
          const firstAprobado = chars.find(function (c) { return c.estado === 'aprobado'; });
          if (firstAprobado) select.value = firstAprobado.id;
        }
      }
    } catch (e) {
      select.innerHTML = '<option value="">Error al cargar personajes</option>';
    }
  }

  // ─── 3. Widget: perfil del personaje en member_profile ───
  async function hydrateProfileWidget() {
    const el = document.getElementById('rol-perfil-personajes');
    if (!el) return;

    const userId = el.dataset.user;
    if (!userId) return;

    try {
      const res = await fetch(ROL_API_URL + '/personajes/activo/' + userId, {
        headers: apiHeaders(),
        signal: AbortSignal.timeout(3000),
      });
      const json = await res.json();

      if (json.success && json.data) {
        const pj = json.data;
        let html = '<div class="rol-profile-char">';
        html += '<h3>Personaje activo</h3>';
        html += '<div class="rol-profile-name"><strong>' + escapeHtml(pj.nombre) + '</strong></div>';

        if (pj.avatar_url) {
          html += '<img src="' + escapeHtml(pj.avatar_url) + '" class="rol-profile-avatar">';
        }

        html += '<div class="rol-profile-details">';
        if (pj.raza) html += '<p><strong>Raza:</strong> ' + escapeHtml(pj.raza) + '</p>';
        if (pj.clase) html += '<p><strong>Clase:</strong> ' + escapeHtml(pj.clase) + '</p>';
        if (pj.edad) html += '<p><strong>Edad:</strong> ' + pj.edad + '</p>';
        html += '</div>';

        if (pj.atributos && pj.atributos.length) {
          html += '<div class="rol-profile-stats"><h4>Atributos</h4>';
          for (const attr of pj.atributos) {
            html += '<span class="rol-stat">' + escapeHtml(attr.clave) + ': ' + escapeHtml(attr.valor) + '</span> ';
          }
          html += '</div>';
        }

        html += '</div>';
        el.innerHTML = html;
      }
    } catch (e) {
      // Fallback silencioso
    }
  }

  // ─── Util: escape HTML basico ───
  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // ─── Init ───
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.ROL_API_URL) return;

    hydratePostbitWidgets();
    hydrateCharSelector();
    hydrateProfileWidget();
  });

})();
