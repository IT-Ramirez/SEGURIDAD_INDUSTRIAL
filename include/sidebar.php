
      <!------------------ Sidebar ------------------->
      <ul class="sidebar navbar-nav">
       <li class="nav-item">
          <a class="nav-link" href="/staff/dashboard/index.php">
            <i class="fas fa-fw fa-tachometer-alt" style="color: #2dfb31;"></i>
          <span>Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php">
           <i class="fas fa-fw fa-plus-circle" style="color:rgb(45, 251, 90);"></i>
          <span>Crear Reserva</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="mis_reservas.php">
          <i class="fas fa-eye" style="color: #2dfb31;"></i>
            <span>Ver Mis Reservas</span></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="reporte.php">
          <i class="fas fa-clipboard" style="color: #2dfb31;"></i>
            <span>Crear Reporte</span></a>
        </li>
         <li class="nav-item">
          <a class="nav-link" href="mis_reportes.php">
          <i class="fas fa-clipboard" style="color: #2dfb31;"></i>
            <span>Mis Reportes</span></a>
        </li>
   
       
<!--Estilos del menu de expansion en configuraciones -->
        <style>
        .dropdown-menu {
            background-color: #00188f; /* Negro */
            color: #fff; /* Blanco para el texto */
        }
        .dropdown-item:hover {
            background-color: #555; /* Color de fondo al pasar el mouse, opcional */
        }
    </style>

      
      </ul>

      <div id="content-wrapper">

        <div class="container-fluid">
		<script>
(() => {
  // === Ajustes rápidos ===
  const SHOW_ALERT = false;           // pon en true si quieres alertas
  const ENABLE_DEVTOOLS_DETECT = true; // desactívalo si no lo quieres

  const stop = (e) => {
    e.preventDefault();
    e.stopPropagation?.();
    return false;
  };

  // 1) Bloquear clic derecho
  document.addEventListener("contextmenu", stop, true);

  // 2) Bloquear atajos comunes (F12, Ctrl+Shift+I/J/C, Ctrl+U/S)
  document.addEventListener("keydown", (e) => {
    const k = (e.key || "").toLowerCase();
    const forbidden =
      k === "f12" ||
      (e.ctrlKey && e.shiftKey && (k === "i" || k === "j" || k === "c")) ||
      (e.ctrlKey && (k === "u" || k === "s"));

    if (forbidden) {
      stop(e);
      if (SHOW_ALERT) alert("🚫 Acción bloqueada");
    }
  }, true);

  // 3) Detección de DevTools (sin falsos positivos por tamaño de ventana)
  if (ENABLE_DEVTOOLS_DETECT) {
    let tripped = false;
    const threshold = 150; // ms: si el 'debugger' pausa, superará este tiempo

    const check = () => {
      const t0 = performance.now();
      debugger; // solo “pausa” si DevTools está abierto
      const delta = performance.now() - t0;

      if (delta > threshold && !tripped) {
        tripped = true;
        // Acción cuando se detecta el inspector:
        document.body.innerHTML =
          "<h1 style='color:red;text-align:center;margin-top:20%'>🚨 Inspector detectado 🚨</h1>";
        // Opcional: setTimeout(() => location.reload(), 1500);
      }
    };

    window.addEventListener("load", () => {
      setInterval(check, 1000);
    });
  }
})();
</script>
