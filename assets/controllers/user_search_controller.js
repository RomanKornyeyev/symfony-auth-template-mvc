import { Controller } from '@hotwired/stimulus';

/**
 * Controller de búsqueda de usuarios en tiempo real.
 *
 * Uso:
 *   <div data-controller="user-search"
 *        data-user-search-url-value="/amigos/buscar">
 *     <input data-user-search-target="input"
 *            data-action="input->user-search#search">
 *     <div data-user-search-target="results"></div>
 *   </div>
 *
 * Espera 350ms tras cada pulsación (debounce) antes de lanzar la petición.
 * El servidor devuelve HTML puro que se inyecta en el target "results".
 * Stimulus conecta automáticamente los data-controller="friendship" del nuevo HTML.
 */
export default class extends Controller {
  static targets = ['input', 'results'];
  static values  = { url: String };

  #timer = null;

  search() {
    clearTimeout(this.#timer);

    const q = this.inputTarget.value.trim();

    if (q.length < 2) {
      this.resultsTarget.innerHTML = '';
      return;
    }

    this.#timer = setTimeout(() => this.#fetch(q), 350);
  }

  async #fetch(q) {
    const url = new URL(this.urlValue, window.location.origin);
    url.searchParams.set('q', q);

    try {
      const response = await fetch(url.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });

      if (response.ok) {
        this.resultsTarget.innerHTML = await response.text();
      }
    } catch {
      // Fallo silencioso — la búsqueda se puede reintentar al escribir
    }
  }
}
