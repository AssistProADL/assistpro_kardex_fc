$(document).ready(function() {
    // Inicialización de DataTables
    $('#vendedoresTable').DataTable({
        "paging": true,
        "pageLength": 5
    });
    $('#supervisoresTable').DataTable({
        "paging": true,
        "pageLength": 5
    });
    $('#clientesTable').DataTable({
        "paging": true,
        "pageLength": 5
    });
});
