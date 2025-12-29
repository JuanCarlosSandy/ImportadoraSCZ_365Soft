<template>
  <main class="main">
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>
    <Panel>
      <template #header>
        <div class="panel-header">
          <i class="pi pi-shopping-cart panel-icon"></i>
          <h4 class="panel-title">TRASPASOS</h4>
        </div>
      </template>

      <div class="toolbar-container">
        <div
          class="search-bar"
          style="display: flex; flex-wrap: wrap; gap: 0.5rem;"
        >
          <div style="flex: 1 1 45%; min-width: 130px;">
            <label for="fechaInicio" class="label-fecha">Fecha Desde</label>
            <Calendar
              id="fechaInicio"
              v-model="fechaInicio"
              dateFormat="yy-mm-dd"
              showIcon
              :appendTo="'body'"
              style="width: 100%; font-size: 12px;"
              inputStyle="padding: 0.4rem;"
            />
          </div>

          <div style="flex: 1 1 45%; min-width: 130px;">
            <label for="fechaFin" class="label-fecha">Fecha Hasta</label>
            <Calendar
              id="fechaFin"
              v-model="fechaFin"
              dateFormat="yy-mm-dd"
              showIcon
              :appendTo="'body'"
              style="width: 100%; font-size: 12px;"
              inputStyle="padding: 0.4rem;"
            />
          </div>
        </div>

        <div class="toolbar">
          <Button
            :label="mostrarLabel ? 'Filtrar' : ''"
            icon="pi pi-filter"
            class="p-button-help p-button-sm"
            @click="fetchTraspasos"
          />
          <Button
            :label="mostrarLabel ? 'Nuevo Traspaso' : ''"
            icon="pi pi-plus"
            @click="abrirModal('traspaso', 'registrar')"
            class="p-button-secondary p-button-sm"
          />
        </div>
      </div>

      <div class="p-fluid">
        <DataTable
          :value="traspasos"
          responsiveLayout="scroll"
          class="p-datatable-gridlines p-datatable-sm"
          paginator
          :rows="8"
          :rowsPerPageOptions="[8, 10, 20]"
          showCurrentPageReport
        >
          <Column header="Acciones" style="width: 100px">
            <template #body="slotProps">
              <Button
                icon="pi pi-eye"
                class="p-button-success p-button-sm"
                style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
                @click="verTraspaso(slotProps.data.id)"
              />
            </template>
          </Column>
                    <Column field="nombre_usuario" header="Encargado" />
          <Column field="nombre_almacen_origen" header="Almacen Origen" />
          <Column field="nombre_almacen_destino" header="Almacen Destino" />
          <Column header="Fecha">
            <template #body="slotProps">
              {{ formatDate(slotProps.data.fecha_traspaso) }}
            </template>
          </Column>
          <Column header="Hora">
            <template #body="slotProps">
              {{ formatTime(slotProps.data.created_at) }}
            </template>
          </Column>
        </DataTable>
      </div>
    </Panel>

    <Dialog
      :visible.sync="modal"
      :modal="true"
      :appendTo="null"
      @hide="handleModalClose"
      :containerStyle="dialogContainerStyle"
      class="responsive-dialog"
    >
      <template #header>
        <div class="flex justify-between items-center w-full">
          <h4 class="text-lg font-semibold">{{ tituloModal }}</h4>
        </div>
      </template>

      <div class="p-fluid p-formgrid p-grid gap-3 form-compact">
        <div class="p-grid">
          <div class="p-col-12 p-md-4">
            <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
              <div
                style="display: flex; align-items: center; min-width: 140px; flex-shrink: 0;"
              >
                <label for="almacenOrigen" style="margin-bottom: 0;">
                  Almacén Origen
                  <span class="p-tag p-tag-secondary obligatorio-rojo"
                    >OBLIG</span
                  >
                </label>
                <Button
                  v-if="bloquearAlmacenOrigen"
                  icon="pi pi-question"
                  class="p-button-rounded p-button-text p-button-sm"
                  style="margin-left: 4px; font-size: 0.8rem; padding: 0.05rem 0.1rem; min-width: 1.1rem; width: 1.1rem; height: 1.1rem;"
                  @click="mostrarDialogAlmacenOrigen = true"
                />
              </div>
            </div>
            <div style="flex: 1 1 0; min-width: 0; margin-top: 0.4rem;">
              <Dropdown
                id="almacenOrigen"
                v-model="AlmacenSeleccionado"
                :options="arrayAlmacenes"
                optionLabel="nombre_almacen"
                optionValue="id"
                placeholder="Seleccione"
                @change="getDatosAlmacen"
                :disabled="bloquearAlmacenOrigen"
                class="w-full"
              />
            </div>
          </div>

          <!-- ALMACÉN DESTINO -->
          <div class="p-col-12 p-md-4">
            <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
              <div
                style="display: flex; align-items: center; min-width: 140px; flex-shrink: 0;"
              >
                <label for="almacenDestino">
                  Almacén Destino
                  <span class="p-tag p-tag-secondary obligatorio-rojo"
                    >OBLIG</span
                  >
                </label>
              </div>
            </div>

            <Dropdown
              id="almacenDestino"
              v-model="AlmacenDestSeleccionado"
              :options="arrayAlmacenesDest"
              optionLabel="nombre_almacen"
              optionValue="id"
              placeholder="Seleccione"
              @change="getDatosAlmacenDest"
              class="w-full"
            />
          </div>

          <!-- TIPO TRASPASO -->
          <div class="p-col-12 p-md-4">
            <label for="tipoTraspaso">
              Tipo Traspaso
              <span class="p-tag p-tag-secondary obligatorio-rojo">OBLIG</span>
            </label>
            <Dropdown
              id="tipoTraspaso"
              v-model="tipotraspo"
              :options="arrayTraspaso"
              placeholder="Seleccione"
              class="w-full"
            />
          </div>
        </div>

        <div class="col-12 sm:col-4 flex items-center gap-2">
          <Button
            label="Buscar Producto"
            icon="pi pi-search"
            @click="abrirModal2()"
            class="p-button-outlined p-button-sm"
          />
        </div>
        <div class="col-12 sm:col-8 flex items-center">
          <strong>Artículo:</strong>
          <span class="italic text-gray-600 ml-2">
            {{ nombre_producto || "Seleccione un artículo" }}
          </span>
        </div>

        <div class="p-grid">
          <div class="p-col-12 p-sm-4">
            <label for="stockOrigen">Stock (Origen)</label>
            <InputNumber
              id="stockOrigen"
              v-model="saldo_stock"
              disabled
              class="w-full"
            />
          </div>

          <div class="p-col-12 p-sm-4">
            <label for="stockDestino">Stock (Destino)</label>
            <InputNumber
              id="stockDestino"
              v-model="saldoStockTotal"
              disabled
              class="w-full"
            />
          </div>

          <div class="p-col-12 p-sm-4">
            <label for="cantidadTrasp">Cantidad Trasp</label>
            <InputNumber
              id="cantidadTrasp"
              v-model="cantidad_traspaso"
              class="w-full"
            />
          </div>
        </div>

        <div class="col-12 sm:col-4 flex align-items-center">
          <Button
            label="Agregar"
            icon="pi pi-plus"
            class="p-button-success p-button-sm"
            @click="agregarDetalle()"
          />
        </div>

        <div class="col-12" style="max-height: 300px; overflow-y: auto;">
          <DataTable
            :value="arrayDetalle"
            responsiveLayout="scroll"
            emptyMessage="No hay artículos agregados"
            class="p-datatable-sm"
          >
            <Column header="Eliminar" style="width: 80px">
              <template #body="slotProps">
                <Button
                  icon="pi pi-trash"
                  class="p-button-danger p-button-sm"
                  @click="eliminarDetalle(slotProps.index)"
                />
              </template>
            </Column>
            <Column field="codigo" header="Código" />
            <Column field="nombre_producto" header="Producto" />
            <Column header="Saldo Stock Origen">
              <template #body="slotProps">
                {{ slotProps.data.saldo_stock }} de stock
              </template>
            </Column>
            <Column header="Cant. Traspaso Destino">
              <template #body="slotProps">
                {{ Math.floor(slotProps.data.cantidad_traspaso) }} de stock
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <!-- FOOTER CON BOTONES -->
      <template #footer>
        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-danger p-button-sm"
          @click="cerrarModal()"
          type="button"
        />
        <Button
          v-if="tipoAccion === 1"
          label="Guardar"
          icon="pi pi-check"
          class="p-button-success p-button-sm"
          @click="registrarTraspaso()"
          type="button"
        />
      </template>
    </Dialog>
    <Dialog
      :visible.sync="dialogTraspasoVisible"
      header="Detalle De Traspaso"
      :modal="true"
      class="w-[70vw] md:w-[60vw]"
    >
      <DataTable
        :value="arrayInventarioTrasp"
        responsiveLayout="scroll"
        class="p-datatable-gridlines p-datatable-sm"
        stripedRows
      >
        <Column field="nombre_producto" header="Producto" />
        <Column field="contacto" header="Proveedor" />
        <Column field="cantidad_traspaso" header="Total Traspasado" />
      </DataTable>
    </Dialog>

    <Dialog
      header="Almacén de origen bloqueado"
      :visible.sync="mostrarDialogAlmacenOrigen"
      :modal="true"
      :closable="false"
      :containerStyle="{ width: '600px' }"
    >
      <p>
        Ya agregó items al carrito del traspaso, no puede cambiar el Almacén de
        origen. Si se equivocó, debe cancelar el traspaso y volver a iniciarlo
        desde cero.
      </p>
      <div class="p-d-flex p-jc-end">
        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-text"
          @click="mostrarDialogAlmacenOrigen = false"
        />
      </div>
    </Dialog>

    <Dialog
      :visible.sync="modal2"
      :modal="true"
      header="Seleccione el Producto"
      class="w-[70vw] md:w-[60vw]"
      :closable="true"
      @hide="cerrarModal2"
    >
      <template #header>
        <div class="flex justify-between items-center w-full">
          <h4 class="text-lg font-semibold">{{ tituloModal2 }}</h4>
        </div>
      </template>
      <div class="p-fluid">
        <div class="p-field p-grid">
          <div class="p-col">
            <InputText
              id="buscarProducto"
              v-model="buscar"
              @keyup="actualizarBusqueda"
              placeholder="Texto a buscar"
              class="w-full"
            />
          </div>
        </div>
        <DataTable
          :value="arrayInventario"
          :paginator="true"
          :rows="pagination.per_page || 8"
          :first="(pagination.current_page - 1) * pagination.per_page"
          :totalRecords="pagination.total"
          :lazy="true"
          @page="onInventarioPage"
          responsiveLayout="scroll"
          dataKey="id"
          emptyMessage="No hay productos disponibles"
          class="p-datatable-sm mt-3"
        >
          <Column header="Opciones" style="width: 80px">
            <template #body="slotProps">
              <Button
                icon="pi pi-check"
                class="p-button-success p-button-sm"
                style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
                @click="agregarDetalleModal(slotProps.data)"
              />
            </template>
          </Column>
          <Column field="nombre_producto" header="Medicamento" />
          <Column field="saldo_stock" header="Cantidad" />
          <Column field="nombre_proveedor" header="Proveedor" />
        </DataTable>
      </div>
      <template #footer>
        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-danger p-button-sm"
          @click="cerrarModal2()"
          type="button"
        />
      </template>
    </Dialog>
  </main>
</template>

<script>
import Calendar from "primevue/calendar";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Panel from "primevue/panel";

export default {
  components: {
    Calendar,
    DataTable,
    Column,
    Button,
    Dialog,
    Dropdown,
    InputNumber,
    Panel,
    InputText,
  },
  data() {
    return {
      mostrarLabel: true,
      bloquearAlmacenOrigen: false, // Cuando sea true, deshabilita el Dropdown de origen
      mostrarDialogAlmacenOrigen: false,
      dialogTraspasoVisible: false,

      modal: false,
      screenWidth: window.innerWidth,
      isLoading: false,
      modal: 0,
      tituloModal: "",
      tipoAccion: 0, //para mostrar lo botones de guardar/cancelar
      //---modal2---
      modal2: 0,
      tituloModal2: "",
      //----almacen--
      idalmacen: 0,
      AlmacenSeleccionado: 1,
      arrayAlmacenes: [],
      //AlmacenDestSeleccionado : '',
      idalmacendes: 0,
      AlmacenDestSeleccionado: 1,
      arrayAlmacenesDest: [],
      //--fechas---
      fecha_traspaso: "",
      //---Tipo traspaso--
      tipotraspo: "Salida",
      arrayTraspaso: ["Salida", "Entrada"],
      tipounipaq: "Unidad",
      arrayUndPaq: ["Unidad", "Paquete"],
      //---Producto de inventario mostrar al seleccionar--
      arrayInventario: [],
      codigo: "",
      saldo_stock: "",
      nombre_producto: "",
      saldoStockTotal: "",
      fecha_vencimiento: "",
      arrayArticuloSeleccionado: {}, //talbes de por []
      fotografia: "",
      //---insertar cantidad--
      cantidad_traspaso: "",
      //---array de mostrar lo selecciondo para recorrer el--
      arrayDetalle: [],
      //----datos adicional para registrar---
      idinventario: 0,
      idarticulo: 0,
      //----para mostrar lo que se registro por id
      idtraspaso: 0,
      //-----lisTado por filtro de fecha--
      traspasos: [],
      fechaInicio: "",
      fechaFin: "",
      arrayInventarioTrasp: [],
      //----listarel saldo_stock
      arrayInventarioStock: [],
      saldoStockTotal: "",
      precio_costo_unid: "",
      precio_costo_paq: "",
      //--buscador--
      pagination: {
        total: 0,
        current_page: 0,
        per_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
      },
      offset: 2,
      criterio: "",
      buscar: "",
      anio_motor: null, // Valor actual del filtro por categoría (C, A, T)
      nombre: "",
    };
  },
  watch: {
    codigo(newValue) {
      if (newValue) {
        this.listarInventarioSalStock();
      }
    },
  },

  computed: {
    dialogContainerStyle() {
      if (window.innerWidth <= 480) {
        return { width: "95vw", maxWidth: "95vw", margin: "0 auto" };
      } else if (window.innerWidth <= 768) {
        return { width: "90vw", maxWidth: "90vw", margin: "0 auto" };
      } else if (window.innerWidth <= 1024) {
        return { width: "85vw", maxWidth: "900px", margin: "0 auto" };
      } else {
        return { width: "800px", maxWidth: "90vw", margin: "0 auto" };
      }
    },
    isActived: function() {
      return this.pagination.current_page;
    },
    //Calcula los elementos de la paginación
    pagesNumber: function() {
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
  },
  methods: {
    toastSuccess(mensaje) {
      this.$toasted.show(
        `
    <div style="height: 50px;font-size:16px;">
        <br>
        ` +
          mensaje +
          `.<br>
    </div>`,
        {
          type: "success",
          position: "bottom-right",
          duration: 2000,
        }
      );
    },
    onInventarioPage(event) {
      const page = Math.floor(event.first / event.rows) + 1;
      this.pagination.per_page = event.rows;
      this.listarInventarios(page);
    },
    handleModalClose() {
      document.body.style.overflow = "auto"; // Restaurar scroll
    },
    handleResize() {
      this.screenWidth = window.innerWidth;
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    cambiarTipoUnidad() {
      if (this.tipounipaq == "Unidad") {
      } else {
        this.tipounipaq = "Paquete";
      }
    },
    //--------calcular cantidad_traspaso - saldo_stock--
    calcularPrecioP(saldo_stock, cantidad_traspaso) {
      const precioP = parseFloat(saldo_stock) - parseFloat(cantidad_traspaso);
      return Math.floor(precioP); // Convierte el resultado a entero
    },
    //---------listar por Trasapso por FILTRO DE FECHA---------
    //---listado por filtro de fecha--
    async fetchTraspasos() {
      let me = this;
      try {
        let url = "/list/traspasos";

        if (this.fechaInicio && this.fechaFin) {
          const inicio = this.formatDate(this.fechaInicio);
          const fin = this.formatDate(this.fechaFin);
          url += `?fechaInicio=${inicio}&fechaFin=${fin}`;
        }

        const response = await axios.get(url);
        const respuesta = response.data;
        me.traspasos = respuesta.traspasos.data;
        me.pagination = respuesta.pagination;
      } catch (error) {
        console.error("Error al obtener traspasos:", error);
        swal("Error", "No se pudieron cargar los traspasos", "error");
      }
    },

    formatDate(date) {
      if (!date) return null;
      const d = new Date(date);
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, "0");
      const day = String(d.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    },
    formatTime(date) {
      return new Date(date).toLocaleTimeString("es-ES");
    },
    //--inicializasa fecha acTual
    inicializarFechas() {
      const fechaActual = new Date();
      this.fechaInicio = new Date(fechaActual); // objeto Date
      this.fechaFin = new Date(fechaActual); // objeto Date
    },

    //-----------------hasta aqui--------------
    //-----------alamcen-----
    //--garrar el id para que liste el inventario con ese almacen
    getDatosAlmacen() {
      let me = this;
      if (me.AlmacenSeleccionado !== "") {
        // Comprobar si hay un almacén seleccionado
        me.loading = true;
        me.idalmacen = Number(me.AlmacenSeleccionado); // Convertir a número antes de asignarlo
        console.log("IDalmacen: " + me.idalmacen);
      }
    },
    getDatosAlmacenDest() {
      let me = this;
      if (me.AlmacenDestSeleccionado !== "") {
        // Validar que no se seleccione el mismo almacén de destino que el de origen si ya hay items
        if (
          me.arrayDetalle.length > 0 &&
          me.AlmacenDestSeleccionado === me.AlmacenSeleccionado
        ) {
          swal({
            icon: "warning",
            title: "No puedes seleccionar el mismo almacén",
            text: "El almacén de destino debe ser diferente al de origen.",
          });
          // Revertir selección
          me.AlmacenDestSeleccionado = me.idalmacendes;
          return;
        }
        me.loading = true;
        me.idalmacendes = Number(me.AlmacenDestSeleccionado);
        console.log("IDalmacenSaldo: " + me.idalmacendes);
        me.listarInventarioSalStock(me.idalmacendes);

        // Actualizo aquí todos los items existentes:
        me.arrayDetalle.forEach((det) => {
          det.idalmacendes = me.idalmacendes;
        });
      }
    },
    selectAlmacen() {
      let me = this;
      var url = "/almacen/selectAlmacen";
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayAlmacenes = respuesta.almacenes;
          console.log("ORIGEN", me.arrayAlmacenes);

          // Si hay al menos un almacén, seleccionarlo automáticamente
          if (me.arrayAlmacenes.length > 0) {
            me.AlmacenSeleccionado = me.arrayAlmacenes[0].id;
            me.getDatosAlmacen(); // Llamar manualmente, porque @change no se dispara automáticamente
          }
        })
        .catch(function(error) {
          console.log(error);
        });
    },
    selectAlmacenDestino() {
      let me = this;
      var url = "/almacen/selectAlmacenDest";
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayAlmacenesDest = respuesta.almacenes;
          console.log("DesTino_Almacen:", me.arrayAlmacenesDest);
        })
        .catch(function(error) {
          console.log(error);
        });
    },
    //--------hasta aqui-----
    //------listado producto de inventario----
    async listarInventarios(page) {
      let me = this;
      try {
        this.isLoading = true; // Activar loading
        let url = `/inventariosTraspaso?page=${page}&buscar=${me.buscar}&idAlmacen=${me.idalmacen}`;

        const response = await axios.get(url);
        let respuesta = response.data;
        me.arrayInventario = respuesta.inventarios.data;
        me.pagination = respuesta.pagination;
      } catch (error) {
        console.error("Error al listar inventarios:", error);
        swal("Error", "No se pudo cargar el inventario", "error");
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },
    filtrarPorAnioMotor(valor) {
      this.anio_motor = valor; // Actualiza el filtro
      this.buscar = ""; // Limpia el texto de búsqueda
      this.listarInventarios(1); // Refresca la lista
    },

    actualizarBusqueda() {
      this.listarInventarios(1); // Llama a listarInventarios con la página 1
    },

    cambiarPagina(page, buscar, criterio) {
      let me = this;
      me.pagination.current_page = page;
      me.listarInventarios(page, buscar, criterio);
    },
    //----------listar invenTario de producto para mostra el saldoctock---
    listarInventarioSalStock() {
      let me = this;
      var url =
        "/inventarios/saldostock" +
        "?idAlmacen=" +
        me.idalmacendes +
        "&idArticulo=" +
        me.idarticulo;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          console.log("list SALDO_STOCK:", respuesta);
          if (respuesta.invenstock.length > 0) {
            me.arrayInventarioStock = respuesta.invenstock.data;
            me.saldoStockTotal = respuesta.invenstock[0].saldo_stock_totaldos;
          } else {
            // No hay datos, por lo que saldoStockTotal se establece en 0 o en lo que quieras
            me.arrayInventarioStock = [];
            me.saldoStockTotal = 0; // O cualquier valor predeterminado
          }
        })
        .catch(function(error) {
          console.log(error);
        });
    },
    //------agregar detalle al modal al dar click para que se muestre los datos como imagen, nombre producto
    encuentra(id) {
      var sw = 0;
      for (var i = 0; i < this.arrayDetalle.length; i++) {
        if (this.arrayDetalle[i].idarticulo == id) {
          sw = true;
        }
      }
      return sw;
    },
    agregarDetalleModal(data = []) {
      let me = this;
      if (me.encuentra(data["id"])) {
        swal({
          type: "error",
          title: "Error...",
          text: "Este Artículo ya se encuentra agregado!",
        });
      } else {
        console.log("==========Agregando inventario-==============");
        console.log(data);
        console.log("=============hasta aqui=================");
        me.arrayArticuloSeleccionado = [
          {
            codigo: data["codigo"],
            fotografia: data["fotografia"],
            id: data["id"],
            //nombre: data['nombre'],
            saldo_stock: data["saldo_stock"],
            nombre_producto: data["nombre_producto"],
            fecha_vencimiento: data["fecha_vencimiento"],
            idarticulo: data["idarticulo"],
            precio_costo_unid: data["precio_costo_unid"],
            unidad_envase: data["unidad_envase"],
          },
        ];
        me.codigo = me.arrayArticuloSeleccionado[0].codigo;
        me.nombre_producto = data["nombre_producto"];
        me.saldo_stock = me.arrayArticuloSeleccionado[0].saldo_stock;
        me.precio_costo_unid =
          me.arrayArticuloSeleccionado[0].precio_costo_unid;
        me.idarticulo = me.arrayArticuloSeleccionado[0].idarticulo;
        me.precio_costo_paq = data["precio_costo_paq"];
        me.fecha_vencimiento =
          me.arrayArticuloSeleccionado[0].fecha_vencimiento;
        me.unidad_envase = me.arrayArticuloSeleccionado[0].unidad_envase;
        me.listarInventarioSalStock();
      }
      this.cerrarModal2();
    },
    //--------hasta aqui------------
    //----agregar los datos para mostrar en la Tabla de abajo---
    agregarDetalle() {
      let me = this;
      const cantidad = parseFloat(me.cantidad_traspaso);
      console.log("----------Almacen seleccionado---------");
      console.log(me.AlmacenSeleccionado);
      console.log("TODO", me.arrayDetalle);
      console.log("----------Almacen seleccionado----------");
      if (me.arrayArticuloSeleccionado.length == "") {
        swal({
          type: "error",
          title: "Error...",
          text: "Seleccione un Producto!",
        });
      } else {
        if (
          cantidad == 0 ||
          cantidad == "" ||
          cantidad < 1 ||
          !Number.isInteger(cantidad)
        ) {
          swal({
            type: "error",
            title: "Error...",
            text: "La cantidad no puede ser 0, negativo o con decimales!",
          });
        } else {
          if (cantidad > me.saldo_stock) {
            swal({
              type: "error",
              title: "Error...",
              text: "No puedes Traspasar una cantidad mayor a la que tienes!",
            });
          } else {
            if (me.encuentra(me.arrayArticuloSeleccionado[0].id)) {
              swal({
                type: "error",
                title: "Error...",
                text: "Este Artículo ya se encuentra agregado!",
              });
            } else {
              // Verificar si AlmacenDestSeleccionado es igual a AlmacenSeleccionado
              if (me.AlmacenDestSeleccionado === me.AlmacenSeleccionado) {
                this.advertenciaSwalAlmDes();
                console.log(
                  "El almacén de destino debe ser diferente al almacén seleccionado."
                );
                return; // No agregar el detalle y salir del método
              }
              if (me.tipounipaq == "Paquetes") {
                me.arrayDetalle.push({
                  idinventario: me.arrayArticuloSeleccionado.id,
                  idarticulo: me.arrayArticuloSeleccionado.idarticulo,
                  //idalmacen: me.AlmacenDestSeleccionado,
                  idalmacen: me.AlmacenSeleccionado,
                  idalmacendes: me.AlmacenDestSeleccionado,
                  codigo: me.arrayArticuloSeleccionado.codigo,
                  nombre_producto: me.arrayArticuloSeleccionado.nombre_producto,
                  fecha_vencimiento:
                    me.arrayArticuloSeleccionado.fecha_vencimiento,
                  saldo_stock: me.arrayArticuloSeleccionado.saldo_stock,
                  unidad_envase: me.arrayArticuloSeleccionado[0].unidad_envase,
                  precio_costo_unid:
                    me.arrayArticuloSeleccionado[0].precio_costo_unid,
                  cantidad_traspaso: me.cantidad_traspaso,
                });
              } else {
                me.arrayDetalle.push({
                  idinventario: me.arrayArticuloSeleccionado[0].id,
                  idarticulo: me.arrayArticuloSeleccionado[0].idarticulo,
                  idalmacen: me.AlmacenSeleccionado,
                  idalmacendes: me.AlmacenDestSeleccionado,
                  codigo: me.arrayArticuloSeleccionado[0].codigo,
                  nombre_producto:
                    me.arrayArticuloSeleccionado[0].nombre_producto,
                  fecha_vencimiento:
                    me.arrayArticuloSeleccionado[0].fecha_vencimiento,
                  saldo_stock: me.arrayArticuloSeleccionado[0].saldo_stock,
                  unidad_envase: me.arrayArticuloSeleccionado[0].unidad_envase,
                  precio_costo_unid:
                    me.arrayArticuloSeleccionado[0].precio_costo_unid,
                  cantidad_traspaso: me.cantidad_traspaso,
                  //son para que semuestre al agragar e la vista y enviar datos para registrar
                });
              }
              me.bloquearAlmacenOrigen = true;

              me.arrayArticuloSeleccionado = [];
              me.codigo = "";
              me.idinventario = 0;
              me.nombre_producto = "";
              me.cantidad_traspaso = "";
              me.saldo_stock = "";
              me.saldoStockTotal = 0;
            }
          }
        }
      }
    },
    //------------hasta aqui-----------
    //---------eliminar lo agrgagado----
    eliminarDetalle(index) {
      let me = this;
      me.arrayDetalle.splice(index, 1);
    },
    //------Registrar ------
    async registrarTraspaso() {
      let me = this;
      try {
        if (me.arrayDetalle.length === 0) {
          this.advertenciaSwal();
          return;
        }

        this.isLoading = true; // Activar loading

        let almacenOrigen = me.arrayAlmacenes.find(
          (almacen) => almacen.id === me.AlmacenSeleccionado
        );
        let almacenDestino = me.arrayAlmacenes.find(
          (almacen) => almacen.id === me.AlmacenDestSeleccionado
        );

        const response = await axios.post("/traspaso/registrar", {
          tipo_traspaso: this.tipotraspo,
          almacen_origen: almacenOrigen ? almacenOrigen.id : null,
          almacen_destino: almacenDestino ? almacenDestino.id : null,
          fecha_traspaso: this.fecha_traspaso,
          data: this.arrayDetalle,
        });

        this.toastSuccess("El traspaso fue registrado con éxito");

        me.cerrarModal_1();
        me.eliminarDetalle();
        await me.fetchTraspasos(); // Actualizar lista de traspasos
      } catch (error) {
        console.error(error);
        swal("Error", "No se pudo registrar el traspaso", "error");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    //-----ver traspaso lo que se registro en un listado----
    async verTraspaso(id) {
      try {
        this.dialogTraspasoVisible = true;

        let idtraspaso = id;
        let me = this;

        const url = "/traspaso/obtenerTraspaso?idtraspaso=" + idtraspaso;
        const response = await axios.get(url);
        const respuesta = response.data;

        me.arrayInventarioTrasp = respuesta.detalletrasp;
      } catch (error) {
        console.error("Error al obtener detalles del traspaso:", error);
        swal(
          "Error",
          "No se pudieron cargar los detalles del traspaso",
          "error"
        );
      }
    },
    //---abrir modal de registro de traspaso--
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "traspaso": {
          switch (accion) {
            case "registrar": {
              this.modal = 1;
              document.body.style.overflow = "hidden"; // Evita scroll en fondo
              this.tituloModal = "Registrar Traspaso";
              this.tipo_traspaso = "";
              this.almacen_origen = "";
              this.alamcen_destino = "";
              const fechaActual = new Date().toISOString().substr(0, 10);
              this.fecha_traspaso = fechaActual;
              this.getDatosAlmacen();
              this.getDatosAlmacenDest();
              this.tipoAccion = 1;
              break;
            }
          }
        }
      }
    },
    //-----abrir modal de listado de producTo de inventario--
    abrirModal2() {
      this.arrayInventario = [];
      this.modal2 = 1;
      this.tituloModal2 = "Seleccione el Producto";
      this.listarInventarios(1);
    },
    //------cerrar modal y modal2--
    cerrarModal() {
      swal({
        title: "¿Desea Cerrar la ventana y cancelar el Traspaso?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, cancelar",
        cancelButtonText: "No, mantener",
        confirmButtonClass: "btn btn-success",
        cancelButtonClass: "btn btn-danger",
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
      }).then((result) => {
        if (result.value) {
          this.modal = false;
          document.body.style.overflow = "auto";

          this.tituloModal = "";
          this.saldo_stock = "";
          this.saldoStockTotal = 0;
          this.idarticulo = 0;
          this.precio_costo_unid = "";
          this.nombre_producto = "";
          this.codigo = "";
          this.AlmacenSeleccionado = 1;
          this.cantidad_traspaso = "";
          this.fecha_vencimiento = 1;
          this.eliminarDetalle();
          this.AlmacenDestSeleccionado = 1;
          this.bloquearAlmacenOrigen = false;
          if (this.arrayArticuloSeleccionado[0]) {
            this.arrayArticuloSeleccionado[0].fotografia = "";
          }
        }
      });
    },
    cerrarModal_1() {
      // Cierra el modal
      this.modal = 0;
      this.tituloModal = "";
      this.saldo_stock = "";
      this.saldoStockTotal = 0;
      this.idarticulo = 0;
      this.precio_costo_unid = "";
      this.nombre_producto = "";
      this.codigo = "";
      this.AlmacenSeleccionado = 1;
      this.cantidad_traspaso = "";
      this.fecha_vencimiento = 1;
      this.eliminarDetalle();
      this.AlmacenDestSeleccionado = 1;
      this.bloquearAlmacenOrigen = false;
      if (this.arrayArticuloSeleccionado[0]) {
        this.arrayArticuloSeleccionado[0].fotografia = "";
      }
    },

    advertenciaSwal() {
      swal({
        title: "Advertencia",
        text: "Debe agregar al menos un producto antes de guardar.",
        type: "error",
      });
    },
    advertenciaSwalAlmDes() {
      swal({
        title: "Advertencia",
        text: "Almacen destino tiene que ser distinto.",
        type: "error",
      });
    },
    cerrarModal2() {
      this.modal2 = 0;
      this.tituloModal2 = "";
      this.buscar = "";
    },
  },
  async mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);

    try {
      await Promise.all([
        this.selectAlmacen(),
        this.selectAlmacenDestino(),
        this.inicializarFechas(),
        this.fetchTraspasos(),
      ]);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      swal("Error", "Error al cargar los datos iniciales", "error");
    }
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
  },
};
</script>
<style scoped>
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

.panel-header {
  display: flex;
  align-items: center;
}
.panel-title {
  margin: 0;
  padding-left: 5px;
}

/* Responsive Dialog Styles */
.responsive-dialog >>> .p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog >>> .p-dialog-content {
  overflow-x: auto;
  padding: 0.75rem 1rem;
}

.responsive-dialog >>> .p-dialog-header {
  padding: 0.75rem 1.5rem;
  font-size: 1.1rem;
}

.responsive-dialog >>> .p-dialog-footer {
  padding: 0.5rem 1.5rem;
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

.search-bar {
  flex-grow: 1;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  min-width: 0;
  margin-right: 1rem;
}

/* Formulario compacto - Reducir espacios entre campos */
.form-compact >>> .p-field {
  margin-bottom: 0.25rem !important;
}

>>> .p-fluid .p-field {
  margin-bottom: 0.25rem;
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

/* DataTable Responsive */
>>> .p-datatable {
  font-size: 0.9rem;
}

>>> .p-datatable .p-datatable-tbody > tr > td {
  padding: 0.5rem;
  word-break: break-word;
  text-align: left;
}

>>> .p-datatable .p-datatable-thead > tr > th {
  padding: 0.75rem 0.5rem;
  font-size: 0.85rem;
}

/* Form Grid Responsive */
>>> .p-formgrid.p-grid {
  margin: 0;
}

>>> .p-formgrid .p-field {
  padding: 0.5rem;
}

/* Tablet Styles */
@media (max-width: 1024px) {
  .responsive-dialog >>> .p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }

  >>> .p-datatable {
    font-size: 0.85rem;
  }

  >>> .p-formgrid .p-field.p-col-12.p-md-6 {
    width: 100% !important;
    flex: 0 0 100% !important;
  }
}

/* Mobile Styles */
@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  /* Calendar input and icon smaller for all views, force both calendars in row */
  .search-bar {
    flex-direction: row;
    gap: 0.5rem;
    justify-content: flex-start;
    align-items: flex-end;
  }
  .search-bar > .p-col-12 {
    flex: 1 1 0;
    max-width: 48%;
    min-width: 0;
    padding: 0 !important;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }
  .search-bar >>> .p-calendar {
    max-width: 140px !important;
    width: 100%;
  }
  .search-bar >>> .p-inputtext {
    font-size: 0.9rem !important;
    padding: 0.28rem 1.5rem 0.28rem 0.5rem !important;
    height: 1.7rem !important;
  }
  .search-bar >>> .p-datepicker-trigger {
    width: 1.2rem !important;
    height: 1.2rem !important;
    font-size: 0.9rem !important;
    padding: 0.1rem !important;
  }
  .search-bar >>> .pi-calendar {
    font-size: 0.9rem !important;
  }

  .responsive-dialog >>> .p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }

  .responsive-dialog >>> .p-dialog-content {
    padding: 0.5rem 0.75rem;
  }

  .responsive-dialog >>> .p-dialog-header {
    padding: 0.5rem 1rem;
    font-size: 1rem;
  }

  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.4rem 1rem;
    justify-content: flex-end;
  }

  .toolbar-container {
    gap: 0.5rem;
  }

  >>> .p-datatable {
    font-size: 0.8rem;
  }

  >>> .p-datatable .p-datatable-tbody > tr > td {
    padding: 0.4rem 0.3rem;
  }

  >>> .p-datatable .p-datatable-thead > tr > th {
    padding: 0.5rem 0.3rem;
    font-size: 0.75rem;
  }

  >>> .p-formgrid .p-field {
    padding: 0.25rem;
    margin-bottom: 0.4rem !important;
  }

  >>> .p-formgrid .p-field label {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
  }

  /* Ajustar iconos en móviles */
  .required-icon {
    font-size: 0.8rem;
  }

  .optional-icon {
    font-size: 0.6rem;
  }

  >>> .p-inputtext,
  >>> .p-dropdown,
  >>> .p-inputnumber-input,
  >>> .p-multiselect,
  >>> .p-inputtextarea {
    font-size: 0.9rem;
    padding: 0.5rem;
  }

  >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
  }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  /* Calendar input and icon even smaller for all views, force both calendars in row */
  .search-bar {
    flex-direction: row;
    gap: 0.3rem;
    justify-content: flex-start;
    align-items: flex-end;
  }
  .search-bar > .p-col-12 {
    flex: 1 1 0;
    max-width: 48%;
    min-width: 0;
    padding: 0 !important;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }
  .search-bar >>> .p-calendar {
    max-width: 110px !important;
    width: 100%;
  }
  .search-bar >>> .p-inputtext {
    font-size: 0.8rem !important;
    padding: 0.18rem 1.1rem 0.18rem 0.4rem !important;
    height: 1.2rem !important;
  }
  .search-bar >>> .p-datepicker-trigger {
    width: 1rem !important;
    height: 1rem !important;
    font-size: 0.7rem !important;
    padding: 0.05rem !important;
  }
  .search-bar >>> .pi-calendar {
    font-size: 0.7rem !important;
  }

  .responsive-dialog >>> .p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }

  .responsive-dialog >>> .p-dialog-content {
    padding: 0.4rem 0.5rem;
  }

  .responsive-dialog >>> .p-dialog-header {
    padding: 0.4rem 0.75rem;
    font-size: 0.95rem;
  }

  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.3rem 0.75rem;
    justify-content: flex-end;
  }

  .responsive-dialog >>> .p-dialog-footer .p-button {
    width: auto;
    margin-bottom: 0.25rem;
  }

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

  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  .search-bar .p-inputtext-sm {
    padding: 0.3rem 0.5rem 0.3rem 2.5rem !important;
    font-size: 0.8rem !important;
  }

  >>> .p-datatable {
    font-size: 0.75rem;
  }

  >>> .p-datatable .p-datatable-tbody > tr > td {
    padding: 0.3rem 0.2rem;
  }

  >>> .p-datatable .p-datatable-thead > tr > th {
    padding: 0.4rem 0.2rem;
    font-size: 0.7rem;
  }

  >>> .p-formgrid .p-field {
    padding: 0.2rem;
    margin-bottom: 0.3rem !important;
  }

  >>> .p-formgrid .p-field label {
    font-size: 0.85rem;
  }

  .required-icon {
    font-size: 0.7rem;
  }

  .optional-icon {
    font-size: 0.55rem;
  }

  >>> .p-inputtext,
  >>> .p-dropdown,
  >>> .p-inputnumber-input,
  >>> .p-multiselect,
  >>> .p-inputtextarea {
    font-size: 0.85rem;
    padding: 0.4rem;
  }

  >>> .p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
  }
}

/* Paginator Responsive */
@media (max-width: 768px) {
  >>> .p-paginator {
    flex-wrap: wrap !important;
    justify-content: center;
    font-size: 0.85rem;
    padding: 0.5rem;
  }

  >>> .p-paginator .p-paginator-page,
  >>> .p-paginator .p-paginator-next,
  >>> .p-paginator .p-paginator-prev,
  >>> .p-paginator .p-paginator-first,
  >>> .p-paginator .p-paginator-last {
    min-width: 32px !important;
    height: 32px !important;
    font-size: 0.85rem !important;
    padding: 0 6px !important;
    margin: 2px !important;
  }
}

@media (max-width: 480px) {
  >>> .p-paginator {
    font-size: 0.8rem;
    padding: 0.4rem;
  }

  >>> .p-paginator .p-paginator-page,
  >>> .p-paginator .p-paginator-next,
  >>> .p-paginator .p-paginator-prev,
  >>> .p-paginator .p-paginator-first,
  >>> .p-paginator .p-paginator-last {
    min-width: 28px !important;
    height: 28px !important;
    font-size: 0.8rem !important;
    padding: 0 4px !important;
    margin: 1px !important;
  }
}

/* Action Buttons in DataTable */
>>> .p-datatable .p-button {
  margin-right: 0.25rem;
}

@media (max-width: 768px) {
  >>> .p-datatable .p-button {
    margin-right: 0.15rem;
    margin-bottom: 0.15rem;
  }
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

.p-dialog-mask {
  z-index: 9990 !important;
}
.p-dialog {
  z-index: 9990 !important;
}
/*Panel*/
.ingreso-panel {
  margin-bottom: 1rem;
}
.panel-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
}
.panel-icon {
  color: #000000;
  font-size: 1.2rem;
}
.panel-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
}

/* Panel Content Spacing */
>>> .p-panel .p-panel-content {
  padding: 1rem;
}
>>> .p-panel .p-panel-header {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}
>>> .p-panel .p-panel-header .p-panel-title {
  font-weight: 600;
}
.label-fecha {
  background-color: #007bff;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.85rem;
  display: inline-block;
  margin-bottom: 2px;
  width: 100px;
  text-align: center;
}
.obligatorio-rojo {
  background: #e53935 !important;
  color: #fff !important;
  border: none !important;
}
.search-bar >>> .p-datepicker {
  min-height: 280px !important;
  height: auto !important;
}
/* Forzar que el popup del calendario siempre esté visible y por encima */
:deep(.p-datepicker) {
  z-index: 99999 !important;
  position: absolute !important;
}
</style>
