
<!-- Botón para ver detalle de OC -->
<button type="button" class="btn btn-sm btn-info" onclick="verDetalleOC('<?= $id_oc ?>')">
    Ver Detalle
</button>

<!-- Modal Bootstrap -->
<div class="modal fade" id="modalDetalleOC" tabindex="-1" role="dialog" aria-labelledby="modalDetalleOCLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalDetalleOCLabel">Detalle de Orden de Compra</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="contenidoDetalleOC">
        <p class="text-muted">Cargando información...</p>
      </div>
    </div>
  </div>
</div>

<script>
function verDetalleOC(idOC) {
    // Mostrar el modal
    $('#modalDetalleOC').modal('show');

    // Limpia el contenido
    document.getElementById("contenidoDetalleOC").innerHTML = '<p class="text-muted">Cargando información...</p>';

    // Llama al API
    fetch('recepcion_oc_detalle_api.php?id_oc=' + idOC)
        .then(response => response.json())
        .then(data => {
            if (!data || Object.keys(data).length === 0) {
                document.getElementById("contenidoDetalleOC").innerHTML = "<p class='text-danger'>No se encontró información.</p>";
                return;
            }

            // Formatea y muestra los datos
            let html = "<table class='table table-bordered'>";
            html += "<thead><tr><th>Producto</th><th>Cantidad</th><th>Unidad</th><th>Precio</th><th>Total</th></tr></thead><tbody>";

            data.forEach(item => {
                html += `<tr>
                    <td>${item.Descripcion || "-"}</td>
                    <td>${item.Cantidad || "-"}</td>
                    <td>${item.Unidad || "-"}</td>
                    <td>${item.PrecioUnitario || "-"}</td>
                    <td>${item.Total || "-"}</td>
                </tr>`;
            });

            html += "</tbody></table>";
            document.getElementById("contenidoDetalleOC").innerHTML = html;
        })
        .catch(error => {
            document.getElementById("contenidoDetalleOC").innerHTML = "<p class='text-danger'>Error al obtener los datos.</p>";
        });
}
</script>
