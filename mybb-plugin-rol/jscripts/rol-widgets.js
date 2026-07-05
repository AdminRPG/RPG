/**
 * Widgets de rol para MyBB
 * Se hidratan vía fetch() contra la API propia después de la carga del foro.
 * El fallo de la API nunca debe romper la carga del foro.
 */

const ROL_API_BASE = '/api/v1';

function getRolToken() {
  // Leer token de cookie 'rol_token' o de localStorage
  return document.cookie.replace(/(?:(?:^|.*;\s*)rol_token\s*=\s*([^;]*).*$)|^.*$/, '$1');
}

// === Widget de ficha en postbit ===
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.rol-ficha-widget').forEach(async (el) => {
    const userId = el.dataset.user;
    if (!userId) return;

    try {
      const res = await fetch(`${ROL_API_BASE}/personajes/activo/${userId}`, {
        headers: { 'Authorization': `Bearer ${getRolToken()}` }
      });

      if (!res.ok) {
        el.innerHTML = '';
        return;
      }

      const { success, data } = await res.json();
      if (!success || !data) {
        el.innerHTML = '';
        return;
      }

      el.innerHTML = `
        <strong>${data.nombre}</strong> — ${data.raza || ''}
        <div class="rol-mini-stats">
          ${(data.atributos || []).map(a => `<span>${a.clave}: ${a.valor}</span>`).join('')}
        </div>`;
    } catch {
      el.innerHTML = '';
    }
  });
});
