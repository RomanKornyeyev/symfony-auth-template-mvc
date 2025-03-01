// ANIMACIÓN PARA FLASHES
document.addEventListener('DOMContentLoaded', function () {
  // Selecciona todas las alertas con la clase 'alert-slide-in'
  var alertas = document.querySelectorAll('.alert-slide-in');

  alertas.forEach(function (alerta) {
      // Añade la clase 'show' a cada alerta con un pequeño retraso
      setTimeout(function () {
          alerta.classList.add('show');
      }, 0); // Retraso de 100ms para activar la animación de aparición
  });
});

// Detecta el evento de cierre y aplica la animación de desvanecimiento
document.addEventListener('click', function (event) {
  if (event.target.matches('[data-bs-dismiss="alert"]')) {
      var alerta = event.target.closest('.alert');
      alerta.classList.remove('alert-slide-in');
      alerta.classList.add('alert-fade-out');

      setTimeout(function () {
          alerta.classList.remove('alert-fade-out');
      }, 500); // Tiempo que debe coincidir con la duración de la animación de desvanecimiento
  }
});