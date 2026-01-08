<template>
  <div class="main">
    <div v-if="isLoading" class="loading-overlay">
      <div class="loading-container">
        <ProgressSpinner style="width: 80px; height: 80px" strokeWidth="4" />
        <div class="loading-text">LOADING...</div>
      </div>
    </div>

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
            <div class="p-inputgroup">
              <span class="p-inputgroup-addon">
                <i class="pi pi-search"></i>
              </span>
              <InputText v-model="buscar" @keyup="listarIngreso(1, buscar)" placeholder="Buscar en todos los campos..."
                class="p-inputtext-sm" />
              <Button icon="pi pi-refresh" class="p-button-help p-button-sm" @click="resetBuscar"
                v-tooltip="'Limpiar búsqueda'" />
            </div>
          </div>
          <div class="toolbar">
            <Button @click="mostrarDetalle()" :label="mostrarLabel ? 'Nuevo' : ''" icon="pi pi-plus"
              class="p-button-primary p-button-sm" />
          </div>
        </div>

        <DataTable :value="arrayIngreso" :paginator="false" responsiveLayout="scroll"
          class="p-datatable-gridlines p-datatable-sm">
          <Column header="Acciones" :style="{ width: '150px' }">
            <template #body="slotProps">
              <div class="flex gap-1">
                <Button @click="verIngreso(slotProps.data.id)" icon="pi pi-eye" severity="success" size="small"
                  v-tooltip="'Ver'" class="p-button-sm p-button-success p-mr-1 btn-mini" v-tooltip.top="'Ver'"/>
                <Button icon="pi pi-pencil" class="p-button-warning p-button-sm btn-mini" v-tooltip="'Editar compra'"
                  @click="editarIngreso(slotProps.data.id)" v-tooltip.top="'Editar'"/>
                <Button @click="imprimirIngreso(slotProps.data.id)" icon="pi pi-print" severity="warning" size="small"
                  v-tooltip="'Imprimir PDF'" class="p-button-sm p-button-primary p-mr-1 btn-mini" v-tooltip.top="'Imprimir'"/>
                <Button v-if="slotProps.data.estado == '1'" @click="desactivarIngreso(slotProps.data.id)"
                  icon="pi pi-trash" severity="danger" size="small" v-tooltip="'Eliminar'"
                  class="p-button-sm p-button-danger p-mr-1 btn-mini" v-tooltip.top="'Desactivar'"/>
              </div>
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
              }" class="estado-badge">
                {{ slotProps.data.estado == 1 ? 'Registrada' : 'Anulada' }}
              </span>
            </template>
          </Column>
        </DataTable>

        <Paginator v-if="pagination.last_page > 1" :rows="pagination.per_page" :totalRecords="pagination.total"
          :first="(pagination.current_page - 1) * pagination.per_page" @page="onPageChange"
          template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" class="mt-3" />
      </div>

      <div v-else-if="listado == 2">
        <div class="comprobante-info-card">
          <div class="comprobante-header">
            <i class="pi pi-file-text comprobante-icon"></i>
            <h5 class="comprobante-title">Información del Comprobante</h5>
          </div>
          <div class="comprobante-fields">
            <div class="field-group">
              <label class="field-label">Tipo de Comprobante</label>
              <InputText :value="tipo_comprobante" readonly class="readonly-input" />
            </div>
            <div class="field-group">
              <label class="field-label">Número de Comprobante</label>
              <InputText :value="num_comprobante" readonly class="readonly-input" />
            </div>
          </div>
        </div>

        <DataTable :value="arrayDetalle" responsiveLayout="scroll" class="p-datatable-gridlines p-datatable-sm">
          <Column header="Cant.">
            <template #body="slotProps">
              <span 
                class="tipo-compra-badge"
                :class="slotProps.data.tipo_compra === 'Unidad' ? 'tipo-unidad' : 'tipo-caja'"
              >
                {{ slotProps.data.cantidad }} {{ slotProps.data.tipo_compra === 'Unidad' ? (slotProps.data.cantidad > 1 ? 'Unidades' : 'Unidad') : (slotProps.data.cantidad > 1 ? 'Cajas' : 'Caja') }}
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
            class="p-button-sm p-button-danger p-mr-1" />
        </div>
      </div>
    </Panel>

    <Dialog v-model:visible="showModalArticulos" modal :style="{ width: '80vw' }"
      header="Seleccione los artículos que desee">
      <modalagregarproductos @cerrar="cerrarModal" @agregarArticulo="agregarArticuloSeleccionado"
        :idproveedor="idproveedor" :monedaPrincipal="monedaCompra" />
    </Dialog>

   <div v-if="listado == 0">
  <registrarcompra
    @cerrar="ocultarDetalle"
    @listarArticuloProveedor="listarArticuloProveedor"
    @abrirModalArticulos="abrirModal"
    @listarIngreso="listarIngresosTabla"
    :arrayArticuloSeleccionado="arrayArticuloSeleccionado"
    :monedaCompra="monedaCompra"
    :editarIngresoData="ingresoSeleccionado"
  />
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
  },directives: {
    'tooltip': Tooltip
  },
  data() {
    return {
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

    imprimirIngreso(id) {
      const url = `/ingreso/imprimir/${id}`;
      window.open(url, '_blank');
    },

    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    resetBuscar() {
      this.buscar = "";
      this.listarIngreso(1, this.buscar);
    },
    onPageChange(event) {
      const page = Math.floor(event.first / event.rows) + 1;
      this.cambiarPagina(page, this.buscar);
    },
    async imprimirDocumento(id) {
      try {
        this.isLoading = true;

        const result = await swal({
          title: "Selecciona el tipo de documento",
          text: "¿Qué tipo de documento deseas generar?",
          type: "question",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Nota de Ingreso",
          cancelButtonText: "Boleta",
          reverseButtons: true,
        });
        if (result.value) {
          window.open("/ingreso/generar-nota-ingreso/" + id, "_blank");
        } else {
          window.open("/ingreso/generar-pdf-boleta/" + id, "_blank");
        }
      } catch (error) {
        console.error(error);
        swal("Error", "Error al generar el documento", "error");
      } finally {
        setTimeout(() => {
          this.isLoading = false;
        }, 500);
      }
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
    async buscarArticulo() {
      try {
        this.isLoading = true;
        let me = this;
        const url = "/articulo/buscarArticulo?filtro=" + me.codigo;
        const response = await axios.get(url);
        const respuesta = response.data;
        me.arrayArticulo = respuesta.articulos;
        if (me.arrayArticulo.length > 0) {
          me.arrayArticuloSeleccionado = me.arrayArticulo[0];
        } else {
          me.articulo = "No existe este articulo";
          me.idarticulo = 0;
        }
      } catch (error) {
        swal("Error", "Error al buscar el artículo", "error");
      } finally {
        setTimeout(() => {
          this.isLoading = false;
        }, 500);
      }
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
    async registrarIngreso() {
      if (this.validarIngreso()) {
        return;
      }
      try {
        this.isLoading = true;
        let me = this;
        for (let i = 0; i < me.arrayDetalle.length; i++) { }
        const response = await axios.post("/ingreso/registrar", {
          idproveedor: this.idproveedor,
          tipo_comprobante: this.tipo_comprobante,
          serie_comprobante: this.serie_comprobante,
          num_comprobante: this.num_comprobante,
          impuesto: this.impuesto,
          total: this.total,
          data: this.arrayDetalle,
        });
        if (response.data.id > 0) {
          await this.guardarInventarios();
          me.listado = 1;
          await me.listarIngreso(1, "");
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
          swal("Éxito", "Ingreso registrado correctamente", "success");
        } else {
          swal("Aviso", response.data.caja_validado, "warning");
        }
      } catch (error) {
        console.error(error);
        swal("Error", "No se pudo registrar el ingreso", "error");
      } finally {
        this.isLoading = false;
      }
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
    async desactivarIngreso(id) {
      try {
        const result = await swal({
          title: "Esta seguro de desactivar este ingreso?",
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
          swal("Anulado!", "El ingreso ha sido anulado con éxito.", "success");
        }
      } catch (error) {
        console.error(error);
        swal("Error", "No se pudo anular el ingreso", "error");
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

.search-bar .p-inputgroup {
  width: 100%;
}

.search-bar .p-inputgroup-addon {
  background: #f8fafc;
  border-color: #d1d5db;
  color: #6b7280;
}

.search-bar .p-inputtext {
  border-left: none;
}

.search-bar .p-inputtext:focus {
  box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.5);
  border-color: #3b82f6;
}

.search-bar .p-inputgroup-addon+.p-inputtext:focus {
  border-left-color: #3b82f6;
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

/* DataTable Responsive */
>>>.p-datatable {
  font-size: 0.9rem;
}

>>>.p-datatable .p-datatable-tbody>tr>td {
  padding: 0.5rem;
  word-break: break-word;
  text-align: left;
}

>>>.p-datatable .p-datatable-thead>tr>th {
  padding: 0.75rem 0.5rem;
  font-size: 0.85rem;
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
