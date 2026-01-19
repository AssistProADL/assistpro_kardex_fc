(function () {
  const user = localStorage.getItem("mobile_user");
  const almacen = localStorage.getItem("mobile_almacen");

  if (!user || !almacen) {
    window.location.href = "index.html";
  }
})();
