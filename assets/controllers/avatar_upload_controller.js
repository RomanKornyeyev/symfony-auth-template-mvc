import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['input', 'preview', 'remove'];
  static values = {
    initialUrl: String,   // foto guardada al entrar en la página
    fallbackUrl: String,  // default.jpg
  };

  connect() {
    // Si no pasas initialUrlValue, usa lo que haya en el <img>
    if (!this.hasInitialUrlValue) {
      this.initialUrlValue = this.previewTarget.getAttribute('src') || '';
    }
    if (!this.hasFallbackUrlValue) {
      this.fallbackUrlValue = '';
    }
  }

  pick() {
    this.inputTarget.click();
  }

  change() {
    const file = this.inputTarget.files && this.inputTarget.files[0];
    if (!file) return;

    // Si elige archivo nuevo, NO queremos borrar
    if (this.hasRemoveTarget) this.removeTarget.value = '0';

    const url = URL.createObjectURL(file);
    this.previewTarget.src = url;
    this.previewTarget.onload = () => URL.revokeObjectURL(url);
  }

  // Deshacer = volver a foto guardada (initial) y anular cualquier "remove"
  undo() {
    this.inputTarget.value = '';
    this.previewTarget.src = this.initialUrlValue || this.fallbackUrlValue || this.previewTarget.src;

    if (this.hasRemoveTarget) this.removeTarget.value = '0';
  }

  // Quitar = volver al default y marcar remove=1
  remove() {
    this.inputTarget.value = '';
    this.previewTarget.src = this.fallbackUrlValue || this.previewTarget.src;

    if (this.hasRemoveTarget) this.removeTarget.value = '1';
  }
}
