const botonEnviar = document.getElementById("btnEnviar");

if (botonEnviar) {
  botonEnviar.addEventListener("click", async function (evento) {
    evento.preventDefault();

    const formulario = botonEnviar.closest("form");

    // Verificamos los campos obligatorios
    if (formulario && !formulario.checkValidity()) {
      formulario.reportValidity();
      return;
    }

    // ==========================
    // RANGO DE AÑOS
    // ==========================

    const valorAnioDesde = document.getElementById("anioDesde").value;
    const valorAnioHasta = document.getElementById("anioHasta").value;

    const anioDesde = valorAnioDesde
      ? Number(valorAnioDesde)
      : null;

    const anioHasta = valorAnioHasta
      ? Number(valorAnioHasta)
      : null;


    // Validamos que el rango sea correcto
    if (
      anioDesde !== null &&
      anioHasta !== null &&
      anioHasta < anioDesde
    ) {
      mostrarNotificacion(
        "error",
        "Rango de años incorrecto",
        "El año hasta no puede ser menor que el año desde."
      );

      return;
    }


    // ==========================
    // ARMAMOS LA SOLICITUD
    // ==========================

    const solicitud = {

      // DATOS PERSONALES
      nombre: document.getElementById("nombre").value,
      dni: document.getElementById("dni").value,
      email: document.getElementById("email").value,
      telefono: document.getElementById("telefono").value,

      // VEHÍCULO
      marca: document.getElementById("marca").value,
      modelo: document.getElementById("modelo").value,

      anioDesde: anioDesde,
      anioHasta: anioHasta,

      transmision: document.getElementById("transmision").value,

      kilometrajeMaximo: document.getElementById("kilometraje").value
        ? Number(document.getElementById("kilometraje").value)
        : null,

      // Presupuesto
moneda: document.getElementById("moneda").value,

presupuestoMaximo: document.getElementById("presupuesto").value
  ? Number(document.getElementById("presupuesto").value)
  : null,

formaCompra: document.getElementById("formaCompra").value,

      // INFORMACIÓN ADICIONAL
      observaciones: document.getElementById("observaciones").value
    };

     // VALIDACIÓN DEL PRESUPUESTO SEGÚN MONEDA
if (solicitud.presupuestoMaximo !== null) {

  const limiteARS = 500000000;
  const limiteUSD = 500000;

  if (
    solicitud.moneda === "ARS" &&
    solicitud.presupuestoMaximo > limiteARS
  ) {
    mostrarNotificacion(
      "error",
      "Presupuesto fuera de rango",
      "El presupuesto máximo permitido en pesos argentinos es de $500.000.000."
    );
    return;
  }

  if (
    solicitud.moneda === "USD" &&
    solicitud.presupuestoMaximo > limiteUSD
  ) {
    mostrarNotificacion(
      "error",
      "Presupuesto fuera de rango",
      "El presupuesto máximo permitido en dólares es de USD 500.000."
    );
    return;
  }
}

    console.log("Enviando solicitud:", solicitud);


    const textoOriginal = botonEnviar.textContent;

    botonEnviar.disabled = true;
    botonEnviar.textContent = "Enviando...";


    try {

      const respuesta = await fetch(
        "http://localhost:5141/api/solicitudes",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json"
          },

          body: JSON.stringify(solicitud)
        }
      );


      const esJson = respuesta.headers
        .get("content-type")
        ?.includes("application/json");


      const resultado = esJson
        ? await respuesta.json()
        : null;


      if (!respuesta.ok) {

        throw new Error(
          resultado?.mensaje ||
          "La API no pudo guardar la solicitud."
        );

      }


      console.log("Respuesta de la API:", resultado);


      mostrarNotificacion(
        "exito",
        "Solicitud enviada correctamente",
        "Recibimos tus datos. Nuestro equipo revisará tu búsqueda y se pondrá en contacto con vos."
      );


      // Limpiamos el formulario después del envío exitoso
      if (formulario) {
        formulario.reset();
      }


    } catch (error) {

      console.error(
        "Error al enviar la solicitud:",
        error
      );


      mostrarNotificacion(
        "error",
        "No pudimos enviar la solicitud",
        "Verificá que la API esté funcionando e intentá nuevamente."
      );


    } finally {

      botonEnviar.disabled = false;
      botonEnviar.textContent = textoOriginal;

    }
  });
}



function mostrarNotificacion(tipo, titulo, mensaje) {

  const notificacionAnterior =
    document.querySelector(".notificacion-overlay");


  if (notificacionAnterior) {
    notificacionAnterior.remove();
  }


  const overlay = document.createElement("div");

  overlay.className = "notificacion-overlay";


  const icono =
    tipo === "exito"
      ? "✓"
      : "!";


  overlay.innerHTML = `

    <div
      class="notificacion ${tipo}"
      role="dialog"
      aria-modal="true"
      aria-labelledby="titulo-notificacion"
    >

      <div class="notificacion-icono">
        ${icono}
      </div>

      <h3 id="titulo-notificacion">
        ${titulo}
      </h3>

      <p>
        ${mensaje}
      </p>

      <button
        type="button"
        class="btn-notificacion"
      >
        Aceptar
      </button>

    </div>

  `;


  document.body.appendChild(overlay);


  const cerrarNotificacion = () => {
    overlay.remove();
  };


  overlay
    .querySelector(".btn-notificacion")
    .addEventListener(
      "click",
      cerrarNotificacion
    );


  overlay.addEventListener(
    "click",
    function (evento) {

      if (evento.target === overlay) {
        cerrarNotificacion();
      }

    }
  );
}