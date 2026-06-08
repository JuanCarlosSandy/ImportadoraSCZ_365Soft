<template>
  <div class="main">
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>

    <Toast :breakpoints="{ '920px': { width: '100%', right: '0', left: '0' } }" style="padding-top: 10px;"
      appendTo="body" :baseZIndex="99999"></Toast>

    <Panel v-if="listado != 0" :toggleable="false" class="ingreso-panel">
      <template #header>
        <div class="panel-header">
          <i class="pi pi-shopping-cart panel-icon"></i>
          <h4 class="panel-title">COMPRAS</h4>
        </div>
      </template>

      <div v-if="listado == 1">
        <div class="toolbar-container" style="margin-top: 0; padding-top: 0;">

          <div class="search-bar">
            <span class="p-input-icon-left">
              <i class="pi pi-search" />
              <InputText v-model="buscar" @keyup="listarIngreso(1, buscar)" placeholder="Buscar en todos los campos..."
                class="p-inputtext-sm input-full" />
            </span>
          </div>

          <div class="toolbar">
            <Button icon="pi pi-refresh" class="p-button-help p-button-sm btn-sm-input" @click="resetBuscar"
              :title="'Limpiar búsqueda'" />
            <Button @click="mostrarDetalle()" :label="mostrarLabel ? 'Nuevo' : ''" icon="pi pi-plus"
              class="p-button-primary p-button-sm btn-sm-input" :title="'Nuevo Registro'" />
          </div>
        </div>

        <DataTable :value="arrayIngreso" :paginator="false" responsiveLayout="scroll"
          class="p-datatable-gridlines p-datatable-sm tabla-pro">
          <Column header="Acciones">
            <template #body="slotProps">

              <Button @click="verIngreso(slotProps.data.id)" icon="pi pi-eye" severity="success" size="small"
                :title="'Ver Detalle'" class="p-button-sm p-button-success btn-mini" v-tooltip.top="'Ver'" />

              <Button @click="imprimirIngreso(slotProps.data)" icon="pi pi-print" severity="warning" size="small"
                :title="'Imprimir PDF'" class="p-button-sm p-button-primary btn-mini" :disabled="isLoading" />

              <template v-if="puedeModificarIngreso(slotProps.data)">
                <Button icon="pi pi-pencil" class="p-button-warning p-button-sm btn-mini"
                  @click="editarIngreso(slotProps.data.id)" />

                <Button @click="anularCompra(slotProps.data.id)" icon="pi pi-trash"
                  class="p-button-sm p-button-danger btn-mini" />
              </template>

            </template>
          </Column>
          <Column field="usuario" header="Usuario" class="hidden md:table-cell" />
          <Column field="nombre_almacen" header="Almacén" class="hidden md:table-cell" />
          <Column field="tipo_comprobante" header="Tipo Comprobante" class="hidden md:table-cell" />
          <Column field="num_comprobante" header="Número Comprobante" class="hidden md:table-cell" />
          <Column field="fecha_hora" header="Fecha Hora" />
          <Column header="Total">
            <template #body="slotProps">
              {{
                (slotProps.data.total * parseFloat(monedaCompra[0])).toFixed(2)
              }}
              {{ monedaCompra[1] }}
            </template>
          </Column>
          <Column header="Estado">
            <template #body="slotProps">
              <span :class="{
                'estado-registrada': slotProps.data.estado == 1,
                'estado-anulada': slotProps.data.estado == 0
              }" class="estado-badge tag-mini">
                {{ slotProps.data.estado == 1 ? 'Registrada' : 'Anulada' }}
              </span>
            </template>
          </Column>
        </DataTable>

        <Paginator v-if="pagination.last_page > 1" :rows="pagination.per_page" :totalRecords="pagination.total"
          :first="(pagination.current_page - 1) * pagination.per_page" @page="onPageChange"
          template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" class="mt-3" />
      </div>

      <div v-else-if="listado == 2" class="detalle-overlay">
        <div class="comprobante-info-card">
          <div class="comprobante-header">
            <i class="pi pi-file-text comprobante-icon"></i>
            <h5 class="comprobante-title">Información del Comprobante</h5>
          </div>
          <div class="comprobante-fields">
            <div class="field-group">
              <label class="optional-field">
                <i class="pi pi-list optional-icon"></i>
                Nombre del Usuario
              </label>
              <InputText :value="usuario" readonly class="readonly-input input-full" />
            </div>
            <div class="field-group">
              <label class="optional-field">
                <i class="pi pi-list optional-icon"></i>
                Tipo de Comprobante
              </label>
              <InputText :value="tipo_comprobante" readonly class="readonly-input input-full" />
            </div>
            <div class="field-group">
              <label class="optional-field">
                <i class="pi pi-list optional-icon"></i>
                Número de Comprobante
              </label>
              <InputText :value="num_comprobante" readonly class="readonly-input input-full" />
            </div>
          </div>
        </div>

        <DataTable :value="arrayDetalle" responsiveLayout="scroll" class="p-datatable-gridlines p-datatable-sm tabla-pro">
          <Column header="Cant.">
            <template #body="slotProps">
              <span class="tipo-compra-badge tag-mini"
                :class="slotProps.data.tipo_compra === 'Unidad' ? 'tipo-unidad' : 'tipo-caja'">
                {{ slotProps.data.cantidad }} {{ slotProps.data.tipo_compra === 'Unidad' ? (slotProps.data.cantidad > 1
                  ? 'Unidades' : 'Unidad') : (slotProps.data.cantidad > 1 ? 'Cajas' : 'Caja') }}
              </span>
            </template>
          </Column>
          <Column field="codigo" header="Codigo" />
          <Column field="articulo" header="Producto" />
          <Column field="unidad_x_paquete" header="Cant x Caja" />
          <Column header="Precio Unitario">
            <template #body="slotProps">
              {{
                (slotProps.data.precio * parseFloat(monedaCompra[0])).toFixed(2)
              }}
              {{ monedaCompra[1] }}
            </template>
          </Column>
          <Column header="Subtotal">
            <template #body="slotProps">
              {{
                (
                  (slotProps.data.tipo_compra === 'Unidad'
                    ? slotProps.data.precio * slotProps.data.cantidad
                    : slotProps.data.precio * slotProps.data.cantidad * slotProps.data.unidad_x_paquete)
                  * parseFloat(monedaCompra[0])
                ).toFixed(2)
              }}
              {{ monedaCompra[1] }}
            </template>
          </Column>
          <template #footer>
            <div class="flex justify-content-end">
              <div class="font-bold">
                Total Neto:
                {{ (totalCalculado * parseFloat(monedaCompra[0])).toFixed(2) }}
                {{ monedaCompra[1] }}
              </div>
            </div>
          </template>
          <template #empty>
            <div class="text-center">No hay artículos agregados</div>
          </template>
        </DataTable>

        <div class="p-text-right">
          <Button @click="ocultarDetalle()" label="Cerrar" icon="pi pi-times" severity="danger"
            class="p-button-sm p-button-danger btn-sm" />
        </div>
      </div>
    </Panel>

    <Dialog v-model:visible="showModalArticulos" modal :style="{ width: '80vw' }"
      header="Seleccione los artículos que desee">
      <modalagregarproductos @cerrar="cerrarModal" @agregarArticulo="agregarArticuloSeleccionado"
        :idproveedor="idproveedor" :monedaPrincipal="monedaCompra" />
    </Dialog>

    <div v-if="listado == 0">
      <registrarcompra @cerrar="ocultarDetalle" @listarArticuloProveedor="listarArticuloProveedor"
        @abrirModalArticulos="abrirModal" @listarIngreso="listarIngresosTabla"
        :arrayArticuloSeleccionado="arrayArticuloSeleccionado" :monedaCompra="monedaCompra"
        :editarIngresoData="ingresoSeleccionado" :monedaPrincipal="monedaPrincipal" />
    </div>
  </div>
</template>

<script>
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Card from "primevue/card";
import Panel from "primevue/panel";
import Tooltip from 'primevue/tooltip';
import Dropdown from "primevue/dropdown";
import Paginator from "primevue/paginator";
import ProgressSpinner from "primevue/progressspinner";
import axios from "axios";
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';

export default {
  components: {
    DataTable,
    Column,
    Button,
    InputText,
    Dialog,
    Card,
    Dropdown,
    Paginator,
    ProgressSpinner,
    Panel,
    ToastService,
    Toast,
  }, directives: {
    'tooltip': Tooltip
  },
  data() {
    return {
      monedaPrincipal: 'BOB',
      ingresoSeleccionado: null, // <-- aquí guardamos el ingreso a editar
      isEditing: false, // se activa cuando cargamos datos para editar

      mostrarLabel: true,
      isLoading: false,
      monedaCompra: [],
      showModalArticulos: false,
      tipoUnidadSeleccionada: "Unidades",
      arrayArticuloSeleccionado: {},
      fechavencimiento: null,
      AlmacenSeleccionado: "",
      arrayAlmacenes: [],
      ingreso_id: 0,
      idproveedor: 0,
      proveedor: "",
      nombre: "",
      usuario: "",
      tipo_comprobante: "BOLETA",
      serie_comprobante: "",
      num_comprobante: "",
      impuesto: 0.18,
      total: 0.0,
      totalImpuesto: 0.0,
      totalParcial: 0.0,
      arrayIngreso: [],
      arrayProveedor: [],
      arrayDetalle: [],
      listado: 1,
      modal: 0,
      tituloModal: "",
      tipoAccion: 0,
      errorIngreso: 0,
      errorMostrarMsjIngreso: [],
      pagination: {
        total: 0,
        current_page: 0,
        per_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
      },
      offset: 3,
      buscar: "",
      criterioA: "nombre",
      buscarA: "",
      arrayArticulo: [],
      idarticulo: 0,
      codigo: "",
      articulo: "",
      precio: 0,
      cantidad: 1,
    };
  },
  computed: {
    isActived: function () {
      return this.pagination.current_page;
    },
    pagesNumber: function () {
      if (!this.pagination.to) {
        return [];
      }
      var from = this.pagination.current_page - this.offset;
      if (from < 1) {
        from = 1;
      }
      var to = from + this.offset * 2;
      if (to >= this.pagination.last_page) {
        to = this.pagination.last_page;
      }
      var pagesArray = [];
      while (from <= to) {
        pagesArray.push(from);
        from++;
      }
      return pagesArray;
    },
    totalCalculado: function () {
      if (!this.arrayDetalle || this.arrayDetalle.length === 0) {
        return 0;
      }
      return this.arrayDetalle.reduce((acc, item) => {
        if (item.tipo_compra === 'Unidad') {
          return acc + (parseFloat(item.precio) * parseInt(item.cantidad));
        } else {
          return acc + (parseFloat(item.precio) * parseInt(item.cantidad) * parseInt(item.unidad_x_paquete));
        }
      }, 0);
    },
  },
  methods: {
    puedeModificarIngreso(ingreso) {
      return ingreso.estado === 1;
    },
    toastSuccess(mensaje) {
      this.$toast.add({
        severity: "success",
        summary: "Éxito",
        detail: mensaje,
        life: 2000,
      });
    },
    toastError(mensaje) {
      this.$toast.add({
        severity: "error",
        summary: "Error",
        detail: mensaje,
        life: 3500,
      });
    },
    toastWarning(mensaje) {
      this.$toast.add({
        severity: "warn",
        summary: "Advertencia",
        detail: mensaje,
        life: 2000,
      });
    },
    async editarIngreso(id) {
      try {
        const resp = await axios.get(`/ingreso/obtener/${id}`);

        // Guardamos directamente los datos a pasar al hijo
        this.ingresoSeleccionado = resp.data;
        this.listado = 0; // abrimos el formulario en registrarcompra

      } catch (error) {
        console.error(error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo cargar la compra"
        });
      }
    },

    async imprimirIngreso(ingreso) {
      this.isLoading = true;
      try {
        const response = await axios.get(`/ingreso/imprimir/${ingreso.id}`, {
          responseType: 'blob',
          timeout: 600000
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;

        const fecha = new Date(ingreso.fecha_hora);
        const year = fecha.getFullYear();
        const month = String(fecha.getMonth() + 1).padStart(2, '0');
        const day = String(fecha.getDate()).padStart(2, '0');
        const fechaFormateada = `${year}${month}${day}`;
        const filename = `NotaCompra_${ingreso.num_comprobante}_${fechaFormateada}.pdf`;

        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();

        // Mensaje de éxito
        this.toastSuccess("Documento generado correctamente");

        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error('Error al imprimir:', error);
        this.toastError("No se pudo generar el documento");
      } finally {
        this.isLoading = false;
      }
    },

    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    resetBuscar() {
      this.buscar = "";
      this.listarIngreso(1, this.buscar);
      this.toastSuccess("Búsqueda limpiada");
    },
    onPageChange(event) {
      const page = Math.floor(event.first / event.rows) + 1;
      this.cambiarPagina(page, this.buscar);
    },

    datosConfiguracion() {
      let me = this;
      var url = "/configuracion";
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.monedaCompra = [
            respuesta.configuracionTrabajo.valor_moneda_compra,
            respuesta.configuracionTrabajo.simbolo_moneda_compra,
          ];
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    selectAlmacen() {
      let me = this;
      var url = "/almacen/selectAlmacen";
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayAlmacenes = respuesta.almacenes;
        })
        .catch(function (error) { });
    },
    atajoButton: function (event) {
      if (event.shiftKey && event.keyCode === 81) {
        event.preventDefault();
        this.$refs.impuestoRef.focus();
      }
      if (event.shiftKey && event.keyCode === 87) {
        event.preventDefault();
        this.$refs.serieComprobanteRef.focus();
      }
      if (event.shiftKey && event.keyCode === 69) {
        event.preventDefault();
        this.$refs.numeroComprobanteRef.focus();
      }
      if (event.shiftKey && event.keyCode === 82) {
        event.preventDefault();
        this.$refs.articuloRef.focus();
      }
      if (event.shiftKey && event.keyCode === 84) {
        event.preventDefault();
        this.$refs.precioRef.focus();
      }
      if (event.shiftKey && event.keyCode === 89) {
        event.preventDefault();
        this.$refs.cantidadRef.focus();
      }
    },
    listarIngresosTabla(dato) {
      const page = dato.page;
      const buscar = dato.buscar;
      this.listarIngreso(page, buscar);
    },
    async listarIngreso(page, buscar) {
      try {
        const url = `/ingreso?page=${page}&buscar=${buscar}`;
        const response = await axios.get(url);
        this.arrayIngreso = response.data.ingresos.data;
        this.pagination = response.data.pagination;
      } catch (error) { }
    },

    cambiarPagina(page, buscar) {
      let me = this;
      me.pagination.current_page = page;
      me.listarIngreso(page, buscar);
    },
    encuentra(id) {
      var sw = 0;
      for (var i = 0; i < this.arrayDetalle.length; i++) {
        if (this.arrayDetalle[i].idarticulo == id) {
          sw = true;
        }
      }
      return sw;
    },
    agregarArticuloSeleccionado(data = []) {
      let me = this;
      if (me.encuentra(data["id"])) {
        swal({
          type: "error",
          title: "Error...",
          text: "Este Artículo ya se encuentra agregado!",
        });
      } else {
        me.arrayArticuloSeleccionado = {
          codigo: data["codigo"],
          descripcion: data["descripcion"],
          fotografia: data["fotografia"],
          id: data["id"],
          nombre: data["nombre"],
          precio_costo_unid: data["precio_costo_unid"],
          unidad_envase: data["unidad_envase"],
          precio_costo_paq: data["precio_costo_paq"],
          vencimiento: data["vencimiento"],
        };
        me.codigo = me.arrayArticuloSeleccionado.codigo;
        this.showModalArticulos = false;
      }
    },
    listarArticulo(buscar, criterio) {
      let me = this;
      var url =
        "/articulo/listarArticulo?buscar=" +
        buscar +
        "&criterio=" +
        criterio +
        "&idProveedor=" +
        this.idproveedor;
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayArticulo = respuesta.articulos.data;
          console.log(me.arrayArticulo);
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    guardarInventarios() {
      axios
        .post("/inventarios/registrar", { inventarios: this.arrayDetalle })
        .then((response) => {
          console.log(response.data);
        })
        .catch((error) => {
          console.error(error);
        });
    },

    validarIngreso() {
      this.errorIngreso = 0;
      this.errorMostrarMsjIngreso = [];
      if (this.tipo_comprobante == 0)
        this.errorMostrarMsjIngreso.push("Sleccione el Comprobante");
      if (!this.impuesto)
        this.errorMostrarMsjIngreso.push("Ingrese el impuesto de compra");
      if (this.arrayDetalle.length <= 0)
        this.errorMostrarMsjIngreso.push("Ingrese detalles");
      if (this.errorMostrarMsjIngreso.length) this.errorIngreso = 1;
      return this.errorIngreso;
    },
    mostrarDetalle() {
      let me = this;
      me.selectAlmacen();
      me.listado = 0;
      me.ingresoSeleccionado = null; // ⚡ Prop nula, el hijo sabe que es nuevo
      me.idproveedor = 0;
      me.tipo_comprobante = "BOLETA";
      me.serie_comprobante = "";
      me.num_comprobante = "";
      me.impuesto = 0.18;
      me.total = 0.0;
      me.idarticulo = 0;
      me.articulo = "";
      me.cantidad = 1;
      me.precio = 0;
      me.arrayDetalle = [];
    },
    ocultarDetalle() {
      this.listado = 1;
      this.arrayDetalle = [];
      this.listarIngreso(1, this.buscar);
    },
    async verIngreso(id) {
      try {
        this.isLoading = true;
        this.listado = 2;
        const url = `/ingreso/obtenerCabecera?id=${id}`;
        const response = await axios.get(url);
        const arrayIngresoT = response.data.ingreso;
        this.proveedor = arrayIngresoT[0]["nombre"];
        this.tipo_comprobante = arrayIngresoT[0]["tipo_comprobante"];
        this.usuario = arrayIngresoT[0]["usuario"];
        this.serie_comprobante = arrayIngresoT[0]["serie_comprobante"];
        this.num_comprobante = arrayIngresoT[0]["num_comprobante"];
        this.impuesto = arrayIngresoT[0]["impuesto"];
        this.total = arrayIngresoT[0]["total"];
        const urlDetalles = `/ingreso/obtenerDetalles?id=${id}`;
        const responseDetalles = await axios.get(urlDetalles);
        this.arrayDetalle = responseDetalles.data.detalles;
      } catch (error) {
        console.error(error);
      } finally {
        setTimeout(() => {
          this.isLoading = false;
        }, 500);
      }
    },
    cerrarModal() {
      this.modal = 0;
      this.showModalArticulos = false;
      this.tituloModal = "";
    },
    abrirModal() {
      this.listarArticulo("", "");
      this.arrayArticulo = [];
      this.modal = 1;
      this.showModalArticulos = true;
      this.tituloModal = "Seleccione los articulos que desee";
    },
    listarArticuloProveedor(dato) {
      this.idproveedor = dato.idproveedor;
    },
    async anularCompra(id) {
      try {
        const result = await swal({
          title: "Esta seguro de desactivar esta compra?",
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Aceptar!",
          cancelButtonText: "Cancelar",
          confirmButtonClass: "btn btn-success",
          cancelButtonClass: "btn btn-danger",
          buttonsStyling: false,
          reverseButtons: true,
        });
        if (result.value) {
          this.isLoading = true;
          await axios.put("/ingreso/desactivar", { id: id });
          await this.listarIngreso(1, "");
          this.toastSuccess("La compra ha sido anulada con éxito.");
        }
      } catch (error) {
        console.error(error);
        this.toastError("No se pudo anular la compra");
      } finally {
        this.isLoading = false;
      }
    },
  },
  async mounted() {
    try {
      await Promise.all([
        this.datosConfiguracion(),
        this.listarIngreso(1, this.buscar),
      ]);
      this.handleResize();

      window.addEventListener("keydown", this.atajoButton);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
    }
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.handleResize);
  },
};
</script>

<style scoped>
/* Estilos para detalles fuera del TabView */
.detalle-overlay {
  animation: fadeIn 0.3s ease-in;
  padding: 1rem;
}
/* Estilo de tabla con scroll horizontal */
.tabla-pro {
  width: 100%;
  white-space: nowrap;
  /* evita salto de columnas */
  overflow-x: auto;
}

.tabla-pro .p-datatable-wrapper {
  overflow-x: auto;
}

.tabla-pro th,
.tabla-pro td {
  text-align: center;
  vertical-align: middle;
  font-size: 0.85rem;
  padding: 0.5rem;
}

/* DataTable Responsive */
>>>.p-datatable {
  font-size: 0.75rem;
}

>>>.p-datatable .p-datatable-tbody>tr>td {
  padding: 0.4rem;
  word-break: break-word;
  text-align: left;
}

>>>.p-datatable .p-datatable-thead>tr>th {
  padding: 0.35rem 0.4rem;
  font-size: 0.75rem;
}

/* 🔹 Estilo más pequeño para todos los Toasts */
.p-toast {
  width: 300px !important;
  /* más angosto */
  font-size: 0.75rem !important;
  /* texto más pequeño */
}

.p-toast-message {
  padding: 0.6rem 0.8rem !important;
  /* menos espacio interno */
  border-radius: 6px !important;
}

.p-toast-message-content {
  gap: 0.4rem !important;
  /* reduce separación entre ícono y texto */
}

.p-toast-message-text {
  line-height: 1.2;
}

.p-toast-summary {
  font-weight: 600;
  font-size: 0.85rem !important;
}

.p-toast-detail {
  font-size: 0.8rem !important;
  opacity: 0.9;
}

/* 🔹 Ícono más pequeño */
.p-toast-icon {
  font-size: 1rem !important;
}

/* 🔹 Márgenes y posición */
.p-toast-top-right {
  top: 1rem !important;
  right: 1rem !important;
}

/* 🔹 Botones pequeños */
.btn-sm {
  font-size: 0.8rem;
  padding: 0.3rem 0.7rem;
  border-radius: 6px;
  line-height: 1.1;
}

.btn-sm .pi {
  font-size: 0.75rem;
  margin-right: 4px;
}

/* 🔹 Botones pequeños inputs */
.btn-sm-input {
  font-size: 0.8rem;
  padding: 0.5rem 0.9rem;
  border-radius: 6px;
  line-height: 1.1;
}

.btn-sm-input .pi {
  font-size: 0.65rem;
  margin-right: 4px;
}

.textarea-full {
  width: 100% !important;
  font-size: 0.8rem !important;
  box-sizing: border-box;
}

/* Estilo base del Textarea de PrimeVue */
.textarea-full>>>.p-inputtextarea {
  width: 100% !important;
  font-size: 0.8rem !important;
  padding: 6px 8px !important;
  border: 1px solid #ccc !important;
  border-radius: 6px !important;
  min-height: 42px;
  /* misma altura mínima que Inputs */
  transition: border 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
  resize: vertical;
  /* permite redimensionar verticalmente */
}

/* 🔹 Focus igual que los otros campos */
.textarea-full>>>.p-inputtextarea:focus {
  border-color: #0ea5e9 !important;
  box-shadow: 0 0 0 0.15rem rgba(14, 165, 233, 0.25);
  outline: none !important;
}

/* 🔹 Hover opcional (igual que dropdown/inputtext) */
.textarea-full>>>.p-inputtextarea:hover {
  border-color: #a8a8a8;
}

/* Contenedor del AutoComplete */
.autocomplete-full {
  width: 100% !important;
  font-size: 0.8rem;
  border-radius: 6px;
  box-sizing: border-box;
}

/* Input interno */
.autocomplete-full>>>.p-inputtext {
  width: 100% !important;
  font-size: 0.8rem !important;
  padding: 6px 8px !important;
  border-radius: 6px;
  box-sizing: border-box;
}

/* Botón del dropdown (flecha) */
.autocomplete-full>>>.p-autocomplete-dropdown {
  width: 2rem !important;
  border-radius: 0 6px 6px 0;
}

/* Contenedor general del input + botón */
.autocomplete-full>>>.p-autocomplete {
  width: 100% !important;
  border: 1px solid #ccc !important;
  border-radius: 6px;
  transition: border 0.2s;
  display: flex;
  align-items: center;
}

/* Focus del input */
.autocomplete-full>>>.p-inputtext:focus,
.autocomplete-full>>>.p-autocomplete.p-focus {
  border-color: #0ea5e9 !important;
  box-shadow: 0 0 0 0.15rem rgba(14, 165, 233, 0.25);
}

/* Panel de sugerencias */
.autocomplete-full>>>.p-autocomplete-panel {
  font-size: 0.8rem !important;
}

/* Sugerencia individual */
.autocomplete-full>>>.p-autocomplete-items .p-autocomplete-item {
  padding: 6px 10px !important;
  font-size: 0.8rem !important;
  min-height: auto !important;
  cursor: pointer;
}

/* Estilo uniforme para Dropdown (igual que InputText) */
.dropdown-full {
  width: 100% !important;
  font-size: 0.8rem;
  border-radius: 6px;
  box-sizing: border-box;
}

/* Input dentro del dropdown */
.dropdown-full>>>.p-dropdown-label {
  padding: 6px 8px !important;
  font-size: 0.8rem;
}

/* Flecha del dropdown */
.dropdown-full>>>.p-dropdown-trigger {
  width: 2rem !important;
}

/* Borde al focus */
.dropdown-full>>>.p-dropdown {
  border: 1px solid #ccc;
  transition: border 0.2s;
}

.dropdown-full>>>.p-dropdown.p-focus {
  border-color: #0ea5e9;
  box-shadow: 0 0 0 0.15rem rgba(14, 165, 233, 0.25);
}

/* 🔹 Opciones del panel (lista desplegable) */
.dropdown-full>>>.p-dropdown-panel .p-dropdown-item {
  font-size: 0.8rem !important;
  padding: 6px 10px !important;
  min-height: auto !important;
  /* evita que queden muy grandes */
}

/* 🔹 Input principal (Buscar Producto) */
.input-full {
  width: 100%;
  font-size: 0.8rem;
  padding: 6px 8px;
  border-radius: 6px 0 0 6px;
  box-sizing: border-box;
}

/* Ajuste para InputText de PrimeVue */
.input-full>>>.p-inputtext {
  width: 100% !important;
  font-size: 0.8rem;
  padding: 6px 8px;
  border-radius: 6px 0 0 6px;
}

/* 🔹 Estilo especial para InputNumber */
.input-number-full {
  width: 100%;
}

.input-number-full>>>.p-inputtext {
  width: 100% !important;
  font-size: 0.8rem;
  padding: 6px 8px;
  box-sizing: border-box;
}

/* Arreglar icono de lupa - Centrado perfecto */
.search-bar .p-input-icon-left {
  position: relative;
  width: 100%;
}

.search-bar .p-input-icon-left i {
  position: absolute;
  left: 0.75rem;
  top: 0;
  bottom: 0;
  margin: auto 0;
  height: 1rem;
  z-index: 2;
  color: #6c757d;
  pointer-events: none;
  display: flex;
  align-items: center;
  line-height: 1;
}

.search-bar .p-input-icon-left .p-inputtext {
  padding-left: 2.5rem !important;
  width: 100%;
}

.estado-badge {
  padding: 0.15rem 0.6rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.70rem;
  color: #fff;
  display: inline-block;
  text-align: center;
}

/* VERDE */
.estado-registrada {
  background-color: #28a745;
}

/* ROJO */
.estado-anulada {
  background-color: #dc3545;
}

/* Search Bar Styles */
.search-bar {
  flex-grow: 1;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  min-width: 0;
  margin-right: 1rem;
}

.input-container {
  position: relative;
  padding-bottom: 20px;
  /* Aumentado de 8px a 12px para dar espacio al error */
  margin-bottom: 8px;
  /* Agregado margen inferior pequeño */
}

.input-container .p-inputtext {
  width: 100%;
  margin-bottom: 0;
  /* Eliminar margen inferior si existe */
}

.error-message {
  position: absolute;
  bottom: 2px;
  /* Ajustado para tener más espacio arriba del input */
  left: 0;
  font-size: 0.75rem;
  /* Tamaño de fuente más pequeño */
  margin-top: 0;
  /* Eliminado margen superior */
}

/* Panel Content Spacing */
>>>.p-panel .p-panel-content {
  padding: 1rem;
}

>>>.p-panel .p-panel-header {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}

>>>.p-panel .p-panel-header .p-panel-title {
  font-weight: 600;
}

/* Comprobante Info Card Styles */
.comprobante-info-card {
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.comprobante-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #d1d5db;
}

.comprobante-icon {
  color: #3b82f6;
  font-size: 1.1rem;
}

.comprobante-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
}

.comprobante-fields {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.field-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.25rem;
}

.readonly-input {
  background-color: #f9fafb !important;
  border-color: #d1d5db !important;
  color: #374151 !important;
  font-weight: 500;
  cursor: default;
}

.readonly-input:focus {
  box-shadow: none !important;
  border-color: #d1d5db !important;
}

/* Responsive para comprobante info */
@media (max-width: 768px) {
  .comprobante-fields {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .comprobante-info-card {
    padding: 0.75rem;
    margin-bottom: 1rem;
  }

  .comprobante-title {
    font-size: 0.9rem;
  }

  .field-label {
    font-size: 0.8rem;
  }
}

/* Responsive Dialog Styles */
.responsive-dialog>>>.p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog>>>.p-dialog-content {
  overflow-x: auto;
  padding: 0.75rem 1rem;
  /* Reducido padding vertical */
}

.responsive-dialog>>>.p-dialog-header {
  padding: 0.75rem 1.5rem;
  /* Reducido padding vertical */
  font-size: 1.1rem;
}

.responsive-dialog>>>.p-dialog-footer {
  padding: 0.5rem 1.5rem;
  /* Reducido padding vertical */
  gap: 0.5rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

/* Toolbar Responsive - Mantener en una línea */
.toolbar-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  gap: 0.75rem;
  flex-wrap: nowrap;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  flex-shrink: 0;
}

/* Formulario compacto - Reducir espacios entre campos */
.form-compact>>>.p-field {
  margin-bottom: 0.25rem !important;
  /* Reducido de 0.5rem a 0.25rem */
}

>>>.p-fluid .p-field {
  margin-bottom: 0.25rem;
  /* Reducido de 0.5rem a 0.25rem */
}

/* Reducir padding del contenedor del diálogo */
.responsive-dialog>>>.p-dialog-content {
  padding: 0.75rem 1rem !important;
  /* Reducido padding vertical */
}

/* Estilos para campos obligatorios */
.required-field {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 600;
  color: #2c3e50;
}

.required-icon {
  color: #e74c3c;
  font-size: 1rem;
  font-weight: bold;
  margin-right: 0.2rem;
}

/* Estilos para campos opcionales */
.optional-field {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 500;
  color: #6c757d;
}

.optional-icon {
  color: #17a2b8;
  font-size: 0.8rem;
}

.activo {
  color: green;
  font-weight: bold;
}

.status-badge {
  padding: 0.25em 0.5em;
  border-radius: 4px;
  color: white;
}

.status-badge.active {
  background-color: rgb(0, 225, 0);
}

.status-badge.inactive {
  background-color: red;
}

.p-dialog-mask {
  z-index: 9990 !important;
}

.p-dialog {
  z-index: 9990 !important;
}

/* SweetAlert z-index para que aparezca por encima de los diálogos */
>>>.swal2-container {
  z-index: 99999 !important;
}

>>>.swal2-popup {
  z-index: 99999 !important;
}

/* Tablet Styles */
@media (max-width: 1024px) {
  .responsive-dialog>>>.p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }

  >>>.p-datatable {
    font-size: 0.85rem;
  }
}

/* Mobile Styles */
@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.5rem 0.75rem;
    /* Más compacto en móviles */
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.5rem 1rem;
    /* Reducido padding vertical */
    font-size: 1rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.4rem 1rem;
    /* Reducido padding vertical */
    justify-content: flex-end;
  }

  .toolbar-container {
    gap: 0.5rem;
  }

  >>>.p-datatable {
    font-size: 0.8rem;
  }

  >>>.p-datatable .p-datatable-tbody>tr>td {
    padding: 0.4rem 0.3rem;
  }

  >>>.p-datatable .p-datatable-thead>tr>th {
    padding: 0.5rem 0.3rem;
    font-size: 0.75rem;
  }

  /* Ajustar botones en móviles */
  >>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  /* Ajustar botón "Nuevo" para que coincida con otros botones */
  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir altura del input buscador */
  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem !important;
    font-size: 0.85rem !important;
  }

  /* Ajustar el addon del icono de búsqueda en móviles */
  .search-bar .p-inputgroup-addon {
    padding: 0.35rem 0.5rem !important;
    font-size: 0.85rem !important;
  }

  /* Ajustar iconos en móviles */
  .required-icon {
    font-size: 0.8rem;
  }

  .optional-icon {
    font-size: 0.6rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input {
    font-size: 0.9rem;
    padding: 0.5rem;
  }

  /* Reducir espacios entre campos en móviles */
  .input-container {
    padding-bottom: 20px;
    /* Aumentado para dar espacio al error en móviles */
    margin-bottom: 6px;
  }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.4rem 0.5rem;
    /* Más compacto en móviles extra pequeños */
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.4rem 0.75rem;
    /* Reducido padding vertical */
    font-size: 0.95rem;
  }

  /* Footer mantiene botones alineados a la derecha, no ocupan todo el ancho */
  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.3rem 0.75rem;
    /* Reducido padding vertical */
    justify-content: flex-end;
  }

  .responsive-dialog>>>.p-dialog-footer .p-button {
    width: auto;
    margin-bottom: 0.25rem;
  }

  /* Toolbar mantiene elementos en una línea */
  .toolbar-container {
    gap: 0.4rem;
    flex-wrap: nowrap;
  }

  .toolbar {
    flex-shrink: 0;
    min-width: auto;
  }

  .search-bar {
    flex: 1;
    min-width: 0;
  }

  /* Ajustar botones para que coincidan */
  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir más la altura del input buscador en móviles pequeños */
  .search-bar .p-inputtext-sm {
    padding: 0.3rem 0.5rem !important;
    font-size: 0.8rem !important;
  }

  /* Ajustar el addon del icono de búsqueda en móviles extra pequeños */
  .search-bar .p-inputgroup-addon {
    padding: 0.3rem 0.5rem !important;
    font-size: 0.8rem !important;
  }

  >>>.p-datatable {
    font-size: 0.75rem;
  }

  >>>.p-datatable .p-datatable-tbody>tr>td {
    padding: 0.3rem 0.2rem;
  }

  >>>.p-datatable .p-datatable-thead>tr>th {
    padding: 0.4rem 0.2rem;
    font-size: 0.7rem;
  }

  /* Iconos más pequeños en móviles extra pequeños */
  .required-icon {
    font-size: 0.7rem;
  }

  .optional-icon {
    font-size: 0.55rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input {
    font-size: 0.85rem;
    padding: 0.4rem;
  }

  >>>.p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
  }

  /* Espacios aún más compactos en móviles extra pequeños */
  .input-container {
    padding-bottom: 20px;
    /* Aumentado para dar espacio al error en móviles pequeños */
    margin-bottom: 4px;
  }
}

/* Paginator Responsive */
@media (max-width: 768px) {
  >>>.p-paginator {
    flex-wrap: wrap !important;
    justify-content: center;
    font-size: 0.85rem;
    padding: 0.5rem;
  }

  >>>.p-paginator .p-paginator-page,
  >>>.p-paginator .p-paginator-next,
  >>>.p-paginator .p-paginator-prev,
  >>>.p-paginator .p-paginator-first,
  >>>.p-paginator .p-paginator-last {
    min-width: 32px !important;
    height: 32px !important;
    font-size: 0.85rem !important;
    padding: 0 6px !important;
    margin: 2px !important;
  }
}

@media (max-width: 480px) {
  >>>.p-paginator {
    font-size: 0.8rem;
    padding: 0.4rem;
  }

  >>>.p-paginator .p-paginator-page,
  >>>.p-paginator .p-paginator-next,
  >>>.p-paginator .p-paginator-prev,
  >>>.p-paginator .p-paginator-first,
  >>>.p-paginator .p-paginator-last {
    min-width: 28px !important;
    height: 28px !important;
    font-size: 0.8rem !important;
    padding: 0 4px !important;
    margin: 1px !important;
  }
}

/* Action Buttons in DataTable */
>>>.p-datatable .p-button {
  margin-right: 0.25rem;
}

@media (max-width: 768px) {
  >>>.p-datatable .p-button {
    margin-right: 0.15rem;
    margin-bottom: 0.15rem;
  }
}

>>>.p-fileupload .p-button.p-fileupload-choose {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>>.p-fileupload .p-button.p-fileupload-choose:enabled:hover {
  background-color: #16a34a !important;
  border-color: #16a34a !important;
}

/* Efecto focus */
>>>.p-fileupload .p-button.p-fileupload-choose:focus {
  box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>>.p-fileupload .p-button.p-fileupload-choose:enabled:active {
  background-color: #15803d !important;
  border-color: #15803d !important;
}

/* Estilo cuando está deshabilitado */
>>>.p-fileupload .p-button.p-fileupload-choose:disabled {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  opacity: 0.6;
}

>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose) {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

/* Efecto focus */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

/* Estilo cuando está deshabilitado */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}

>>>.p-fileupload .p-fileupload-files .p-button {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>>.p-fileupload .p-fileupload-files .p-button:enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

/* Efecto focus */
>>>.p-fileupload .p-fileupload-files .p-button:focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>>.p-fileupload .p-fileupload-files .p-button:enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

/* Estilo cuando está deshabilitado */
>>>.p-fileupload .p-fileupload-files .p-button:disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}

/* Asegurar que el icono dentro del botón también sea blanco */
>>>.p-fileupload .p-fileupload-files .p-button .p-button-icon {
  color: #ffffff !important;
}

>>>.p-fileupload-row>div:first-child {
  display: none !important;
}

>>>.p-dialog .p-dialog-content {
  padding: 0 1.5rem 1.5rem 1.5rem;
}


/* Estilos del loader */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(5px);
  padding: 30px;
  border-radius: 15px;
}

.spinner {
  width: 80px;
  height: 80px;
  border: 4px solid rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  border-top: 4px solid rgba(255, 255, 255, 0.9);
  animation: spin 1s linear infinite;
}

.loading-text {
  margin-top: 20px;
  color: rgba(255, 255, 255, 0.9);
  letter-spacing: 3px;
  font-size: 14px;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

/* Estilos para badges de tipo de compra */
.tipo-compra-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  white-space: nowrap;
}

.tipo-unidad {
  background-color: #2196F3;
  color: #ffffff;
}

.tipo-caja {
  background-color: #FF9800;
  color: #ffffff;
}
</style>

<!-- Estilos globales para SweetAlert -->
<style>
/* SweetAlert v1 (swal) */
.sweet-alert {
  z-index: 99999 !important;
}

.sweet-overlay {
  z-index: 99998 !important;
}

/* SweetAlert v2 (Swal) */
.swal2-container {
  z-index: 99999 !important;
}

.swal2-popup {
  z-index: 99999 !important;
}

.swal2-backdrop-show {
  z-index: 99998 !important;
}

/* Clase personalizada para z-index */
.swal-zindex {
  z-index: 99999 !important;
}

/* Asegurar que todos los elementos de SweetAlert estén por encima */
div[class*="swal"] {
  z-index: 99999 !important;
}
</style>
