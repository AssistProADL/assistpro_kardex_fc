      </div><!-- /.container-fluid -->
  </main>
</div><!-- /.ap-shell -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Restaurar estado del sidebar
  (function(){
    try{
      if(localStorage.getItem('ap_sidebar_collapsed')==='1'){
        document.body.classList.add('sidebar-collapsed');
      }
    }catch(e){}
  })();

  // Toggle hamburguesa + guardar preferencia
  document.getElementById('btn-toggle-sidebar')?.addEventListener('click', ()=>{
    document.body.classList.toggle('sidebar-collapsed');
    try{
      localStorage.setItem('ap_sidebar_collapsed',
        document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
    }catch(e){}
  });
</script>
</body>
</html>

