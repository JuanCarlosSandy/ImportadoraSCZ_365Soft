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
    <Panel>
      <template #header>
        <div class="panel-header">
          <i class="pi pi-bars panel-icon"></i>
          <h4 class="panel-title">USUARIOS</h4>
        </div>
      </template>
      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText type="text" placeholder="Texto a buscar" v-model="buscar" class="p-inputtext-sm"
              @input="onBuscarInput" />
          </span>
        </div>
        <div class="toolbar">
          <Button :label="mostrarLabel ? 'Reset' : ''" icon="pi pi-refresh" @click="resetBusqueda"
            class="p-button-help p-button-sm" />
          <Button :label="mostrarLabel ? 'Nuevo' : ''" icon="pi pi-plus" @click="abrirModal('persona', 'registrar')"
            class="p-button-secondary p-button-sm" />
          <Button :label="mostrarLabel ? 'Exportar' : ''" icon="pi pi-cloud-download"
            @click="cargarReporteUsuariosExcel()" class="p-button-success p-button-sm" />
        </div>
      </div>

      <div>
        <DataTable :value="arrayPersona" :paginator="true" :rows="15" dataKey="id"
          class="p-datatable-gridlines p-datatable-sm tabla-pro" responsiveLayout="scroll">
          <Column header="Acciones">
            <template #body="slotProps">
              <div style="display: flex; gap: 0.25rem; align-items: center;">
                <Button icon="pi pi-pencil" class="p-button p-button-warning btn-mini"
                  @click="abrirModal('persona', 'actualizar', slotProps.data)" v-tooltip.top="'Editar'"/>
                <Button v-if="slotProps.data.condicion" icon="pi pi-trash" class="p-button p-button-danger btn-mini"
                  @click="desactivarUsuario(slotProps.data.id)" v-tooltip.top="'Desactivar'" />
                <Button v-else icon="pi pi-check" class="p-button p-button-info"
                  @click="activarUsuario(slotProps.data.id)" v-tooltip.top="'Activar'" />
                <Button icon="pi pi-image" class="p-button p-button-secondary p-button-outlined btn-mini"
                  @click="verFoto(slotProps.data.fotografia)" v-tooltip.top="'Ver Foto'" />
              </div>
            </template>
          </Column>
          <Column field="nombre" header="Nombre"></Column>
          <Column field="rol" header="Rol"></Column>
          <Column field="usuario" header="Usuario"></Column>
          <Column header="Documento Identidad">
            <template #body="slotProps">
              <span v-if="esNoRegistrado(formatearDocumento(
                slotProps.data.tipo_documento,
                slotProps.data.num_documento
              ))" class="dato-no-registrado">
                <i class="pi pi-info-circle mr-1"></i>
                Documento no registrado
              </span>

              <span v-else>
                {{ formatearDocumento(slotProps.data.tipo_documento, slotProps.data.num_documento) }}
              </span>
            </template>
          </Column>

          <Column header="Teléfono">
            <template #body="slotProps">
              <span v-if="esNoRegistrado(mostrarDato(slotProps.data.telefono))" class="dato-no-registrado">
                <i class="pi pi-info-circle mr-1"></i>
                No registrado
              </span>

              <span v-else>
                {{ mostrarDato(slotProps.data.telefono) }}
              </span>
            </template>
          </Column>
          <Column field="sucursal" header="Sucursal"></Column>
          <Column field="puntoventa" header="Punto Venta"></Column>
        </DataTable>
      </div>
    </Panel>

    <Dialog :visible.sync="modal" :containerStyle="{ width: '550px' }" :modal="true" :closable="false"
      :closeOnEscape="true" style="padding-top: 35px !important;">

      <template #header>
        <h4>{{ tituloModal }}</h4>
      </template>

      <div class="p-fluid p-formgrid p-grid">
        <div class="p-field p-col-12 p-md-6">
          <label for="nombre" class="required-field">
            <span class="required-icon">*</span>
            Nombre
          </label>
          <InputText class="p-inputtext-sm" id="nombre" v-model="nombre"
            :class="{ 'input-error': errorPersona && !nombre }" />
        </div>
        <div class="p-field p-col-12 p-md-6">
          <label for="nombre" class="required-field">
            <span class="required-icon">*</span>
            Rol
          </label>
          <Dropdown class="p-inputtext-sm" id="idrol" v-model="idrol" :options="arrayRol" optionLabel="nombre"
            optionValue="id" placeholder="Seleccione" :class="{ 'input-error': errorPersona && !idrol }" />
        </div>

        <div class="p-field p-col-12 p-md-6">
          <label for="descripcion" class="optional-field">
            <i class="pi pi-info-circle optional-icon"></i>
            Tipo Documento
            <span class="p-tag p-tag-secondary">Opcional</span>
          </label>
          <Dropdown class="p-inputtext-sm" id="tipo_documento" v-model="tipo_documento" :options="tipoDocumentoOptions"
            optionLabel="label" optionValue="value" placeholder="Selecciona un tipo de documento" />
        </div>
        <div class="p-field p-col-12 p-md-6">
          <label for="descripcion" class="optional-field">
            <i class="pi pi-info-circle optional-icon"></i>
            Num Documento
            <span class="p-tag p-tag-secondary">Opcional</span>
          </label>
          <InputText class="p-inputtext-sm" id="num_documento" v-model="num_documento" />
        </div>
        <div class="p-field p-col-12 p-md-6">
          <label for="nombre" class="required-field">
            <span class="required-icon">*</span>
            Sucursal
          </label>
          <Dropdown class="p-inputtext-sm" id="idsucursal" v-model="idsucursal" :options="arraySucursal"
            optionLabel="nombre" optionValue="id" placeholder="Seleccione"
            :class="{ 'input-error': errorPersona && !idsucursal }" />
        </div>
        <div class="p-field p-col-12 p-md-6">
          <label for="nombre" class="required-field">
            <span class="required-icon">*</span>
            Punto de Venta
          </label>
          <Dropdown class="p-inputtext-sm" id="idpuntoventa" v-model="idpuntoventa" :options="arrayPuntoVenta"
            optionLabel="nombre" optionValue="id" placeholder="Seleccione"
            :class="{ 'input-error': errorPersona && !idpuntoventa }" />
        </div>

        <div class="p-field p-col-12 p-md-6">
          <label for="nombre" class="required-field">
            <span class="required-icon">*</span>
            Usuario
          </label>
          <InputText class="p-inputtext-sm" id="usuario" v-model="usuario"
            :class="{ 'input-error': errorPersona && !usuario }" />
        </div>
        <div class="p-field p-col-12 p-md-6">
          <label for="password" class="required-field">
            <span class="required-icon">*</span>
            Contraseña
          </label>

          <Password class="p-inputtext-sm" id="password" v-model="password" :toggleMask="true"
            :class="{ 'input-error': errorPersona && !password }" />
        </div>
        <div class="p-field p-col-12 p-md-6">
          <label for="descripcion" class="optional-field">
            <i class="pi pi-info-circle optional-icon"></i>
            Num Celular
            <span class="p-tag p-tag-secondary">Opcional</span>
          </label>
          <InputText class="p-inputtext-sm" id="telefono" v-model="telefono" />
        </div>
        <div class="p-field p-col-12 p-md-6">
          <label for="foto" class="optional-field">
            <i class="pi pi-info-circle optional-icon"></i>
            Foto
            <span class="p-tag p-tag-secondary">Opcional</span>
          </label> <input ref="fotografiaInput" type="file" accept="image/*" style="display: none;"
            @change="onFileChange" />
          <Button label="Seleccionar foto" icon="pi pi-image" class="p-button-outlined p-button-info p-button-sm mt-2"
            @click="$refs.fotografiaInput.click()" />
          <div v-if="fotoMuestra" class="mt-2">
            <img :src="fotoMuestra" alt="Foto previa"
              style="max-width: 100px; max-height: 100px; border-radius: 8px;" />
          </div>
        </div>
      </div>

      <template #footer>
        <div class="d-flex gap-2 justify-content-end modal-footer-buttons">

          <Button label="Cerrar" icon="pi pi-times" @click="cerrarModal" class="p-button-danger p-button-sm" />
          <Button v-if="tipoAccion == 1" label="Guardar" icon="pi pi-check" @click="registrarPersona"
            class="p-button-success p-button-sm" />
          <Button v-if="tipoAccion == 2" label="Actualizar" icon="pi pi-check" @click="actualizarPersona"
            class="p-button-warning p-button-sm" />
        </div>

      </template>
    </Dialog>

    <Dialog :visible.sync="modalFoto" header="Fotografía" :modal="true" :closable="true" :closeOnEscape="true"
      :containerStyle="{ width: '400px' }">
      <div class="text-center">
        <img :src="'img/usuarios/' + fotoSeleccionada" style="max-width: 100%; border-radius: 8px;">
      </div>
    </Dialog>
  </div>
</template>

<script>
import Swal from "sweetalert2";
import Panel from "primevue/panel";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import Password from "primevue/password";
import FileUpload from "primevue/fileupload";
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import Tooltip from 'primevue/tooltip';
import { BIconTelephoneMinus } from "bootstrap-vue";

export default {
  components: {
    Panel,
    Button,
    Dropdown,
    InputText,
    DataTable,
    Column,
    Dialog,
    Password,
    FileUpload,
    ToastService,
    Toast
  },directives: {
    'tooltip': Tooltip
  },
  data() {
    return {
      arrayPuntoVenta: [],
      idpuntoventa: '',
      modalFoto: false,
      fotoSeleccionada: null,

      searchTimeout: null,
      mostrarLabel: true,
      abreviaturasDocumento: {
        "1": "CI",
        "2": "CEX",
        "5": "NIT",
        "3": "PAS",
        "4": "OD",
      },
      isLoading: false,
      tipoDocumentoOptions: [
        { label: "CI - CEDULA DE IDENTIDAD", value: "1" },
        { label: "CEX - CEDULA DE IDENTIDAD DE EXTRANJERO", value: "2" },
        { label: "NIT - NÚMERO DE IDENTIFICACIÓN TRIBUTARIA", value: "5" },
        { label: "PAS - PASAPORTE", value: "3" },
        { label: "OD - OTRO DOCUMENTO DE IDENTIDAD", value: "4" },
      ],
      criterioOptions: [
        { label: "Nombre", value: "nombre" },
        { label: "Documento", value: "num_documento" },
        { label: "Teléfono", value: "telefono" },
      ],
      loading: false,
      persona_id: 0,
      nombre: "",
      tipo_documento: "",
      num_documento: "",
      direccion: "",
      telefono: "",
      email: "",
      usuario: "",
      password: "",
      fotografia: "",
      fotoMuestra: "",
      idrol: "",
      idsucursal: "",
      arrayPersona: [],
      arrayRol: [],
      arraySucursal: [],
      modal: false,
      tituloModal: "",
      tipoAccion: 0,
      errorPersona: 0,
      errorMostrarMsjPersona: [],
      pagination: {
        total: 0,
        current_page: 0,
        per_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
      },
      offset: 3,
      criterio: "nombre",
      buscar: "",
    };
  },
  watch: {
    idsucursal() {
      this.obtenerPuntosDeVenta();
    }
  },
  computed: {
    filteredPuntosVenta() {
      if (this.idsucursal === 0) {
        return [];
      } else {
        return this.arrayPuntoVenta.filter(puntoventa => puntoventa.idsucursal === this.idsucursal);
      }
    },
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
    imagen() {
      console.log(this.fotoMuestra);
      return this.fotoMuestra;
    },
  },
  methods: {
    obtenerPuntosDeVenta() {
      if (this.idsucursal === '0') {
        return;
      }

      axios.get(`/api/puntosDeVenta/${this.idsucursal}`)
        .then(response => {
          this.arrayPuntoVenta = response.data;
        })
        .catch(error => {
          console.error('Error al obtener los puntos de venta:', error);
        });
    },
    verFoto(foto) {
      this.fotoSeleccionada = foto || "defecto.jpg";
      this.modalFoto = true;
    },
    onBuscarInput() {
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      this.searchTimeout = setTimeout(() => {
        this.listarPersona(this.buscar);
      }, 300);
    },
    resetBusqueda() {
      this.buscar = "";
      this.listarPersona(this.buscar);
    },
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
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    mostrarDato(valor) {
      return valor && valor !== "null" ? valor : "No registrado";
    },
    formatearDocumento(tipo, numero) {
      if (tipo && numero) {
        const abreviado = this.abreviaturasDocumento[tipo];
        return abreviado ? `${abreviado} ${numero}` : "Documento no registrado";
      }
      return "Documento no registrado";
    },

    esNoRegistrado(texto) {
      return texto === "No registrado" || texto === "Documento no registrado";
    },
    onFileChange(event) {
      const file = event.target.files[0];
      if (file && file.type.startsWith('image/')) {
        this.fotografia = file;
        const reader = new FileReader();
        reader.onload = (e) => {
          this.fotoMuestra = e.target.result;
        };
        reader.readAsDataURL(file);
      } else {
        this.fotografia = null;
        this.fotoMuestra = '';
      }
    },
    onPage(event) {
      this.listarPersona(this.buscar);
    },
    async listarPersona(buscar) {
      let me = this;
      try {
        me.isLoading = true;

        const url = "/user?buscar=" + buscar;
        const response = await axios.get(url);
        me.arrayPersona = response.data.personas;
      } catch (error) {
        console.error("Error al listar usuarios:", error);
        Swal.fire("Error", "No se pudieron cargar los usuarios", "error");
      } finally {
        me.isLoading = false;
      }
    },
    selectRol() {
      let me = this;
      var url = "/rol/selectRol";
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayRol = respuesta.roles;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    selectSucursal() {
      let me = this;
      var url = "/sucursal/selectSucursal";
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arraySucursal = respuesta.sucursales;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    cambiarPagina(page, buscar, criterio) {
      let me = this;
      me.pagination.current_page = page;
      me.listarPersona(buscar);
    },
    obtenerFotografia(event) {
      let file = event.target.files[0];
      let fileType = file.type;
      if (fileType !== "image/png" && fileType !== "image/jpeg") {
        alert("Por favor, seleccione una imagen en formato PNG o JPG.");
        return;
      }
      this.fotografia = file;
      this.mostrarFoto(file);
      // Ya no es necesario, ahora se maneja en onFileChange
    },
    mostrarFoto(file) {
      let reader = new FileReader();
      reader.onload = (file) => {
        this.fotoMuestra = file.target.result;
      };
      reader.readAsDataURL(file);
    },
    async registrarPersona() {
      if (this.validarPersona()) {
        return;
      }

      try {
        this.isLoading = true; // Activar loading
        let me = this;
        let formData = new FormData();
        formData.append("nombre", this.nombre);
        formData.append("tipo_documento", this.tipo_documento);
        formData.append("num_documento", this.num_documento);
        formData.append("direccion", this.direccion);
        formData.append("telefono", this.telefono);
        formData.append("email", this.email);
        formData.append("idrol", this.idrol);
        formData.append("idsucursal", this.idsucursal);
        formData.append("usuario", this.usuario);
        formData.append("password", this.password);
        formData.append("fotografia", this.fotografia);
        formData.append('idpuntoventa', this.idpuntoventa);

        await axios.post("/user/registrar", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });

        me.cerrarModal();
        await me.listarPersona(this.buscar);

        this.toastSuccess("Usuario registrado correctamente");
      } catch (error) {
        console.error("Error al registrar:", error);
        Swal.fire("Error", "No se pudo registrar el usuario", "error");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    async actualizarPersona() {
      if (!this.password) {
        Swal.fire("¡Advertencia!", "La clave es obligatoria.", "warning");
        return;
      }
      if (this.validarPersona()) {
        return;
      }

      try {
        this.isLoading = true; // Activar loading
        let me = this;
        let formData = new FormData();
        formData.append("nombre", this.nombre);
        formData.append("tipo_documento", this.tipo_documento);
        formData.append("num_documento", this.num_documento);
        formData.append("direccion", this.direccion);
        formData.append("telefono", this.telefono);
        formData.append("email", this.email);
        formData.append("idrol", this.idrol);
        formData.append("idsucursal", this.idsucursal);
        formData.append("usuario", this.usuario);
        formData.append("password", this.password);
        formData.append("fotografia", this.fotografia);
        formData.append("id", this.persona_id);
        formData.append('idpuntoventa', this.idpuntoventa);

        await axios.post("/user/actualizar", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });
        this.isLoading = false; // Desactivar loading justo antes del swal
        this.toastSuccess("Datos actualizados correctamente");

        me.cerrarModal();
        await me.listarPersona(this.buscar);
      } catch (error) {
        console.error("Error al actualizar:", error);
        this.isLoading = false; // Desactivar loading justo antes del swal de error
        Swal.fire("Error", "No se pudo actualizar el usuario", "error");
      }
    },
    validarPersona() {
      this.errorPersona = 0;
      this.errorMostrarMsjPersona = [];

      if (!this.nombre)
        this.errorMostrarMsjPersona.push(
          "El nombre de la persona no puede estar vacío."
        );

      if (!this.usuario)
        this.errorMostrarMsjPersona.push(
          "El nombre de usuario no puede estar vacío."
        );

      if (!this.password)
        this.errorMostrarMsjPersona.push(
          "La password del usuario no puede estar vacía."
        );

      if (!this.idsucursal)
        this.errorMostrarMsjPersona.push(
          "La sucursal del usuario no puede estar vacía."
        );

      if (!this.idpuntoventa)
        this.errorMostrarMsjPersona.push(
          "El punto de venta del usuario no puede estar vacío."
        );

      if (!this.password)
        this.errorMostrarMsjPersona.push(
          "La password del usuario no puede estar vacía."
        );

      if (this.idrol == 0)
        this.errorMostrarMsjPersona.push("Seleccione un Rol.");

      if (this.errorMostrarMsjPersona.length) {
        this.errorPersona = 1;

        // 🔥 Aquí se muestra el toast de error
        this.$toast.add({
          severity: "error",
          summary: "Datos faltantes",
          detail: "Complete todos los campos obligatorios.",
          life: 3000,
        });
      }

      return this.errorPersona;
    },

    cerrarModal() {
      let fileInput = this.$refs.fotografiaInput;
      if (fileInput) {
        fileInput.value = "";
      }

      this.modal = false;
      this.tituloModal = "";
      this.nombre = "";
      this.tipo_documento = "DNI";
      this.num_documento = "";
      this.direccion = "";
      this.telefono = "";
      this.email = "";
      this.usuario = "";
      this.password = "";
      this.fotografia = fileInput ? fileInput : "";
      this.fotoMuestra = "img/usuarios/defecto.jpg";
      this.idrol = 0;
      this.idsucursal = 0;
      this.errorPersona = 0;
      this.idpuntoventa = '';
    },
    abrirModal(modelo, accion, data = []) {
      this.selectRol();
      this.selectSucursal();
      switch (modelo) {
        case "persona": {
          switch (accion) {
            case "registrar": {
              this.modal = true;
              this.tituloModal = "REGISTRAR USUARIO";
              this.nombre = "";
              this.tipo_documento = "DNI";
              this.num_documento = "";
              this.direccion = "";
              this.telefono = "";
              this.email = "";
              this.usuario = "";
              this.password = "";
              this.fotografia = "";
              this.idrol = 0;
              this.idsucursal = 0;
              this.tipoAccion = 1;
              this.idpuntoventa = '';
              break;
            }
            case "actualizar": {
              this.modal = true;
              this.tituloModal = "ACTUALIZAR USUARIO";
              this.tipoAccion = 2;
              this.persona_id = data["id"];
              this.nombre = data["nombre"];
              this.tipo_documento = data["tipo_documento"];
              this.num_documento = data["num_documento"];
              this.direccion = data["direccion"];
              this.telefono = data["telefono"];
              this.email = data["email"];
              this.usuario = data["usuario"];
              this.password = data["password"];
              this.fotografia = data["fotografia"];
              this.fotoMuestra = data["fotografia"]
                ? "img/usuarios/" + data["fotografia"]
                : "img/usuarios/defecto.jpg";
              this.idrol = data["idrol"];
              this.idsucursal = data["idsucursal"];
              this.idpuntoventa = data['idpuntoventa'];
              break;
            }
          }
        }
      }
    },
    async desactivarUsuario(id) {
      try {
        const result = await Swal.fire({
          title: "Esta seguro de desactivar este usuario?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#22c55e",
          cancelButtonColor: "#ef4444",
          confirmButtonText: "Aceptar!",
          cancelButtonText: "Cancelar",
          reverseButtons: true,
          customClass: {
            confirmButton: "swal2-confirm-lineanew",
            cancelButton: "swal2-cancel-lineanew",
          },
        });

        if (result.value) {
          this.isLoading = true; // Activar loading
          let me = this;
          await axios.put("/user/desactivar", { id: id });
          await me.listarPersona(this.buscar);
          this.toastSuccess("Usuario desactivado correctamente");
        }
      } catch (error) {
        console.error("Error al desactivar:", error);
        Swal.fire({
          title: "Error",
          text: "No se pudo desactivar el usuario",
          icon: "error",
        });
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    async activarUsuario(id) {
      try {
        const result = await Swal.fire({
          title: "¿Está seguro de activar este usuario?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#22c55e",
          cancelButtonColor: "#ef4444",
          confirmButtonText: "Aceptar!",
          cancelButtonText: "Cancelar",
          reverseButtons: true,
          customClass: {
            confirmButton: "swal2-confirm-lineanew",
            cancelButton: "swal2-cancel-lineanew",
          },
        });

        if (result.isConfirmed) {
          this.isLoading = true; // Activar loading

          await axios.put("/user/activar", { id: id });
          await this.listarPersona(this.buscar);

          this.toastSuccess("Usuario activado correctamente.");
        }
      } catch (error) {
        console.error("Error al activar:", error);
        await Swal.fire({
          title: "Error",
          text: "No se pudo activar el usuario",
          icon: "error",
        });
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },

    cargarReporteUsuariosExcel() {
      window.open("/user/listarReporteUsuariosExcel", "_blank");
    },
  },
  async mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    try {
      this.isLoading = true; // Activar loading
      await Promise.all([
        this.listarPersona(this.buscar),
        this.selectRol(),
        this.selectSucursal(),
      ]);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      Swal.fire("Error", "Error al cargar los datos iniciales", "error"); // Usando sweetalert2 correctamente
    } finally {
      this.isLoading = false; // Desactivar loading justo cuando todo termine
    }
  },
};
</script>
<style scoped>
/*datos no registrados */
.dato-no-registrado {
  color: #b38a00;
  /* amarillo oscuro */
  font-weight: 600;
  display: flex;
  align-items: center;
}

.dato-no-registrado i {
  font-size: 1rem;
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

.search-bar {
  flex-grow: 1;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  min-width: 0;
  margin-right: 1rem;
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
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 4px;
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
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 4px;
  align-items: center;
  gap: 0.4rem;
  font-weight: 500;
  color: #6c757d;
}

.optional-icon {
  color: #17a2b8;
  font-size: 0.5rem;
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
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
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
    padding: 0.3rem 0.5rem 0.3rem 2.5rem !important;
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

.obligatorio-rojo {
  background-color: #ff0000;
}

.modal-footer-buttons {
  padding-top: 1rem;
}
</style>
