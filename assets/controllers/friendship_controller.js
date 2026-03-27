import { Controller } from '@hotwired/stimulus';

/**
 * Controller de amistad.
 *
 * Montado sobre el div wrapper de cada usuario:
 *   <div data-controller="friendship"> ... </div>
 *
 * Intercepta cualquier <form data-action="submit->friendship#submit">
 * que viva dentro. Si el form tiene `data-confirm`, muestra primero
 * el modal de confirmación (#confirm-modal) antes de continuar.
 *
 * Tras el fetch POST recibe {html, sections?} y:
 *   - Reemplaza su propio innerHTML con html
 *   - Actualiza los <div id="X"> de la página con sections[X]
 *     (Stimulus reconecta data-controller vía MutationObserver)
 */
export default class extends Controller {
  async submit(event) {
    event.preventDefault();

    const form = event.target;

    // ── Confirmación opcional ────────────────────────────────────────────────
    if (form.dataset.confirm) {
      const confirmed = await this.#showConfirm(form.dataset.confirm);
      if (!confirmed) return;
    }

    // ── Spinner en el botón submit ───────────────────────────────────────────
    const button = form.querySelector('[type="submit"]');
    if (button) {
      button.disabled = true;
      button.dataset.originalHtml = button.innerHTML;
      button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    }

    try {
      const response = await fetch(form.action, {
        method:  'POST',
        body:    new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();

      // Actualiza los botones del elemento actual
      this.element.innerHTML = data.html;

      // Actualiza las secciones de la página (solo si existen en el DOM)
      if (data.sections) {
        for (const [id, html] of Object.entries(data.sections)) {
          const el = document.getElementById(id);
          if (el) el.innerHTML = html;
        }
      }

      // Actualiza el indicador de solicitudes pendientes en el header
      if (data.pendingCount !== undefined) {
        this.#updatePendingBadge(data.pendingCount);
      }

    } catch {
      // Restauramos el botón para que el usuario pueda reintentar
      if (button) {
        button.disabled = false;
        button.innerHTML = button.dataset.originalHtml ?? button.innerHTML;
      }
    }
  }

  // ── Badge de solicitudes pendientes en el header ──────────────────────────
  #updatePendingBadge(count) {
    document.querySelectorAll('[data-pending-badge]').forEach(el => {
      el.classList.toggle('d-none', count === 0);
    });
  }

  // ── Promise-based Bootstrap modal ─────────────────────────────────────────
  #showConfirm(message) {
    return new Promise((resolve) => {
      const el = document.getElementById('confirm-modal');

      // Sin modal en el DOM: confirmamos directamente (fallback)
      if (!el) { resolve(true); return; }

      el.querySelector('[data-modal-body]').textContent = message;

      const bsModal    = window.bootstrap.Modal.getOrCreateInstance(el);
      const confirmBtn = el.querySelector('[data-modal-confirm]');

      let resolved = false;

      const onConfirm = () => {
        resolved = true;
        bsModal.hide();
        resolve(true);
      };

      // Se dispara al cerrar el modal por cualquier vía
      const onHide = () => {
        confirmBtn.removeEventListener('click', onConfirm);
        if (!resolved) resolve(false);
      };

      confirmBtn.addEventListener('click', onConfirm, { once: true });
      el.addEventListener('hide.bs.modal', onHide, { once: true });

      bsModal.show();
    });
  }
}
