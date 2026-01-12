/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */
import Vue from "vue";

import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";

import Toasted from "vue-toasted";
import { BootstrapVue, IconsPlugin } from "bootstrap-vue";

import VueSweetalert2 from "vue-sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";
import './app.css';

import "bootstrap-icons/font/bootstrap-icons.css";
import "bootstrap/dist/css/bootstrap.css";
import "bootstrap-vue/dist/bootstrap-vue.css";

import "primeflex/primeflex.css";
import "primevue/resources/themes/lara-light-blue/theme.css";
import "primevue/resources/primevue.min.css";
import "primeicons/primeicons.css";

//Vue.use(PrimeVue);
Vue.use(PrimeVue, { ripple: true });
Vue.use(ToastService);
Vue.use(VueSweetalert2);
require("./bootstrap");

window.$ = window.jQuery = require("jquery");
window.Vue = require("vue");

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.component("square-item", require("./components/dashboard/squareItem.vue"));
Vue.component(
  "TopVendedores",
  require("./components/dashboard/TopVendedores.vue"),
);
Vue.component("TopClientes", require("./components/dashboard/TopClientes.vue"));
Vue.component("ventacredito", require("./components/VentaCredito.vue"));
Vue.component(
  "TopArticulos",
  require("./components/dashboard/TopArticulos.vue"),
);
Vue.component("newprecioarti", require("./components/dashboard/MedicamentosActu.vue"));


Vue.component("AbonarCuota", require("./components/creditos/AbonarCuota.vue"));
Vue.component(
  "ImportarExcel",
  require("./components/productos/ImportarExcel.vue"),
);
Vue.component(
  "ImportarExcelInventario",
  require("./components/productos/ImportarExcelInventario.vue"),
);
Vue.component(
  "ImportarExcelProvedores",
  require("./components/compras/ImportarExcelProvedores.vue"),
);

Vue.component("icon-button", require("./components/buttons/IconButton.vue"));

Vue.component("empresa", require("./components/Empresa.vue"));
Vue.component("sucursal", require("./components/Sucursal.vue"));
Vue.component("moneda", require("./components/Moneda.vue"));
Vue.component("caja", require("./components/Caja.vue"));
Vue.component("articulonewview", require("./components/ArticuloNewView.vue"));

Vue.component("ofertas", require("./components/Ofertas.vue"));
Vue.component("kits", require("./components/Kits.vue"));

Vue.component("bancos", require("./components/Bancos.vue"));
Vue.component("transferencias", require("./components/Transferencias.vue"));

Vue.component("ingreso", require("./components/Ingreso.vue"));
Vue.component("cliente", require("./components/Cliente.vue"));
Vue.component("proveedor", require("./components/Proveedor.vue"));
Vue.component("rol", require("./components/Rol.vue"));
Vue.component("user", require("./components/User.vue"));
Vue.component("venta", require("./components/Venta.vue"));
Vue.component("medidasnewview", require("./components/MedidasNewView.vue"));
Vue.component("ventavendedor", require("./components/VentaVendedor.vue"));

Vue.component(
  "registrarventa",
  require("./components/ventas/RegistrarVentas.vue"),
);
Vue.component("dashboard", require("./components/Dashboard.vue"));
Vue.component("consultaingreso", require("./components/ConsultaIngreso.vue"));
Vue.component("consultaventa", require("./components/ConsultaVenta.vue"));
Vue.component("notification", require("./components/Notification.vue"));

Vue.component("editarperfil", require("./components/EditarPerfil.vue"));
// compras
Vue.component(
  "registrarcompra",
  require("./components/compras/RegistrarCompra.vue"),
);
// productos
Vue.component(
  "modalagregarproductos",
  require("./components/productos/ModalAgregarProductos.vue"),
);
// Pedidos a proveedor
Vue.component(
  "detallepedidosproveedor",
  require("./components/PedidosProveedor/DetallePedidoProveedor.vue"),
);
Vue.component("marca", require("./components/Marca.vue"));
Vue.component("marcanewview", require("./components/MarcaNewView.vue"));
Vue.component("linea", require("./components/Linea.vue"));
Vue.component("lineanewview", require("./components/LineaNewView.vue"));
Vue.component("industria", require("./components/Industria.vue"));
Vue.component("industrianewview", require("./components/IndustriaNewView.vue"));
Vue.component("configuracion", require("./components/Configuracion.vue"));
Vue.component(
  "cotizacioncompras",
  require("./components/CotizacionCompras.vue"),
);
// Vue.component('cotizacionventas', require('./components/CotizacionVentas.vue'));
Vue.component(
  "cotizacionventas",
  require("./components/cotizacion/listacotizacion/CotizacionVentas.vue"),
);
//Vue.component('registrarcompra', require('./components/cotizacion/registrocotizacion/RegistrarCompra.vue'));
Vue.component(
  "detallecotizacionventa",
  require("./components/cotizacion/detallecotizacion/DetalleCotizacionVenta.vue"),
);

Vue.component("almacenes", require("./components/Almacens.vue"));
Vue.component("inventarios", require("./components/Inventario.vue"));
Vue.component("grupos", require("./components/Grupo.vue"));
Vue.component(
  "monitoreoproductos",
  require("./components/MonitoreoProductos.vue"),
);
Vue.component(
  "productosbajostock",
  require("./components/dashboard/ProductosBajoStock.vue"),
);
Vue.component(
  "productosvencidos",
  require("./components/ProductosVencidos.vue"),
);
Vue.component(
  "productosporvencerse",
  require("./components/dashboard/ProductosPorVencerse.vue"),
);
Vue.component("traspasoproducto", require("./components/Traspaso.vue"));
Vue.component("medidas", require("./components/Medidas.vue"));
Vue.component("factura", require("./components/Factura.vue"));
Vue.component(
  "sincronizacionactividades",
  require("./components/SincronizarActividades.vue"),
);
Vue.component(
  "sincronizarparametricatiposfactura",
  require("./components/SincronizarTiposFactura.vue"),
);
Vue.component(
  "sincronizarlistaleyendasfactura",
  require("./components/SincronizarLeyendasFactura.vue"),
);
Vue.component(
  "sincronizarproductosservicios",
  require("./components/SincronizarProductosServicios.vue"),
);
Vue.component(
  "sincronizarmotivoanulacion",
  require("./components/SincronizarMotivoAnulacion.vue"),
);
Vue.component(
  "sincronizareventossignificativos",
  require("./components/SincronizarEventosSignificativos.vue"),
);
Vue.component(
  "sincronizarunidadmedida",
  require("./components/SincronizarUnidadMedida.vue"),
);
Vue.component(
  "eventossignificativos",
  require("./components/EventosSignificativos.vue"),
);
Vue.component(
  "facturafueralinea",
  require("./components/FacturaFueraLinea.vue"),
);
Vue.component("puntoventa", require("./components/PuntoVenta.vue"));
Vue.component("rolventa", require("./components/RegistroVentas.vue"));
Vue.component("devoluciones", require("./components/Devoluciones.vue"));
Vue.component(
  "reporteventas",
  require("./components/ReporteVentasDiarias.vue"),
);

Vue.component(
  "reportekardexfisicovalorado",
  require("./components/ReporteKardexFisicoValorado.vue"),
);
Vue.component(
  "reportekardexfisico",
  require("./components/ReporteKardexFisico.vue"),
);

Vue.component(
  "resumenclientes",
  require("./components/ReporteResumenClientes.vue"),
);
Vue.component(
  "kardexclientesdetalladoglobal",
  require("./components/ReporteKardexClientesDetalleGlobal.vue"),
);
Vue.component(
  "kardexclientesresumenglobal",
  require("./components/ReporteKardexClientesResumenGlobal.vue"),
);
Vue.component(
  "reciboclientexdocumento",
  require("./components/ReporteReciboClienteDocumento.vue"),
);

Vue.component(
  "resumenventasycobranzas",
  require("./components/ReporteResumenVentasYCobranzas.vue"),
);

Vue.component(
  "resumenventaxdocumento",
  require("./components/ReporteResumenVentasXDocumento.vue"),
);

Vue.component(
  "resumenventaxproducto",
  require("./components/ReporteResumenVentasXProducto.vue"),
);

Vue.component(
  "resumenfisico",
  require("./components/ReporteResumenFisico.vue"),
);

Vue.component(
  "reportefisicomovimientos",
  require("./components/ReporteDetalladoFisicoMovimientos.vue"),
);

Vue.component(
  "ventasinstitucionales",
  require("./components/VentasInstitucionales.vue"),
);

Vue.component(
  "reporteinventario",
  require("./components/ReporteInventario.vue"),
);

Vue.component(
  "reporteventas",
  require("./components/ReporteVentasDiarias.vue"),
);

Vue.component(
  "reporteventasdetallado",
  require("./components/ReporteVentasDetallado.vue"),
);

Vue.component(
  "reporteinventariofisicovalorado",
  require("./components/ReporteInventarioFisicoValorado.vue"),
);

Vue.component(
  "reporteinventariofisico",
  require("./components/ReporteInventarioFisico.vue"),
);

Vue.component(
  "ventasinstitucionales",
  require("./components/VentasInstitucionales.vue"),
);

Vue.component(
  "TransaccionErgeso",
  require("./components/Tables/TransaccionEgreso.vue"),
);
Vue.component(
  "TransaccionIngreso",
  require("./components/Tables/TransaccionIngreso.vue"),
);
Vue.component(
  "TransaccionExtra",
  require("./components/Tables/TransaccionExtra.vue"),
);

Vue.component("mayoreo", require("./components/OfertasEspecialesMayoreo.vue"));

Vue.component("nuevacompra", require("./components/compras/NuevaCompra.vue"));
Vue.component(
  "comprascredito",
  require("./components/ComprasCredito/ComprasCredito.vue"),
);
Vue.component("ajusteinventarios", require("./components/AjusteInventario.vue"));
Vue.component("reportedeudores", require("./components/ReporteDeudores.vue"));
Vue.component("cobros", require("./components/Cobros.vue"));

Vue.component("reportedia", require("./components/ReporteDelDia.vue"));
Vue.component("itemscompuestos", require("./components/ItemCompuestos/ItemsCompuestos.vue"));


Vue.use(BootstrapVue);
Vue.use(Toasted);

const app = new Vue({
  el: "#app",
  data: {
    menu: 0,
    notifications: [],
  },
  mounted() {
    console.log("Vue app mounted");
  },
  created() {
    let me = this;
    axios
      .post("notification/get")
      .then(function (response) {
        //console.log(response.data);
        me.notifications = response.data;
      })
      .catch(function (error) {
        console.log(error);
      });

    var userId = $('meta[name="userId"]').attr("content");

    Echo.private("App.User." + userId).notification((notification) => {
      //console-log(notification);
      me.notifications.unshift(notification);
    });
  },
});
