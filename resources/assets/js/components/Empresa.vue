<template>
  <main class="main">
    <Panel>
      <template #header>
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="pi pi-bars panel-icon"></i>
            <h4 class="panel-title" style="margin: 0;">DATOS DE EMPRESA</h4>
          </div>
          <Button v-if="estadoInputs" icon="pi pi-pencil" :label="mostrarLabel ? 'Nuevo' : ''"
            class="p-button-secondary p-button-sm" @click="estadoCampos"
            style="background-color: #f59e0b; border: none; color: white; padding: 0.75rem 1.5rem; font-weight: bold; font-size: 1rem;" />
        </div>
      </template>


      <div class="empresa-grid">
        <div>
          <img :src="imagenTemporal || '/img/logoPrincipal.png'" alt="Logo Empresa" class="border-round shadow-2"
            style="width: 100%; object-fit: contain;" />

          <div v-if="!estadoInputs" class="mt-2">
            <input ref="inputLogo" type="file" accept="image/*" @change="seleccionarLogo" style="display: none;" />
            <Button label="Cambiar logo" icon="pi pi-image" class="p-button-outlined p-button-info p-button-sm mt-2"
              style="width: 100%;" @click="abrirSelectorLogo" />
          </div>
        </div>

        <div>
          <div class="p-fluid formgrid grid">
            <div class="field col-12 md:col-6">
              <label for="nombre"><strong>Nombre de Empresa</strong></label>
              <InputText id="nombre" v-model="nombre" placeholder="Ingrese el nombre de la empresa"
                :disabled="estadoInputs" />
            </div>

            <div class="field col-12 md:col-6">
              <label for="direccion"><strong>Dirección de Empresa</strong></label>
              <InputText id="direccion" v-model="direccion" placeholder="Ingrese la dirección"
                :disabled="estadoInputs" />
            </div>

            <div class="field col-12 md:col-6">
              <label for="telefono"><strong>Teléfono de Empresa</strong></label>
              <InputText id="telefono" v-model="telefono" placeholder="Ingrese el teléfono" :disabled="estadoInputs" />
            </div>

            <div class="field col-12 md:col-6">
              <label for="nit"><strong>NIT de Empresa</strong></label>
              <InputText id="nit" v-model="nit" placeholder="Ingrese el NIT" :disabled="estadoInputs" />
            </div>

            <div class="field col-12 md:col-6">
              <label for="email"><strong>Correo de Empresa</strong></label>
              <InputText id="email" v-model="email" placeholder="Ingrese el email" :disabled="estadoInputs"
                @blur="validateEmail" />
            </div>

            <div class="col-12 mt-3" v-if="!estadoInputs">
              <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <Button label="Cancelar" icon="pi pi-times" class="p-button-danger p-button-sm" @click="cancelarEdicion"
                  style="width: 120px;" />
                <Button label="Actualizar" icon="pi pi-save" class="p-button-success p-button-sm"
                  @click="actualizarEmpresa" style="width: 120px;" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </Panel>
  </main>
</template>

<script>
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import RadioButton from "primevue/radiobutton";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Panel from "primevue/panel";
import Swal from "sweetalert2";

export default {
  components: {
    Panel,
    InputText,
    InputNumber,
    RadioButton,
    Button,
    DataTable,
    Column,
  },
  data() {
    return {
      mostrarLabel: true,

      imagenTemporal: null,
      logoFile: null,
      empresa_id: 0,
      nombre: "",
      direccion: "",
      telefono: "",
      email: "",
      nit: 0,
      validEmail: null,
      monedaPrincipal: "",
      valorMaximoDescuento: "",
      tipoCambio1: "",
      tipoCambio2: "",
      tipoCambio3: "",
      licencia: "",
      errorEmpresa: 0,
      errorMostrarMsjEmpresa: [],
      estadoInputs: true,
      mostrarDivs: false,
    };
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
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    abrirSelectorLogo() {
      this.$refs.inputLogo.click();
    },
    seleccionarLogo(event) {
      const file = event.target.files[0];
      if (file && file.type.startsWith("image/")) {
        this.logoFile = file;

        const reader = new FileReader();
        reader.onload = (e) => {
          this.imagenTemporal = e.target.result;
        };
        reader.readAsDataURL(file);
      } else {
        Swal.fire({
          title: "Error",
          text: "Selecciona un archivo de imagen válido",
          icon: "error"
        });
      }
    },
    cancelarEdicion() {
      this.estadoInputs = true;
      this.datosEmpresa(); // Vuelve a cargar los datos originales desde la base de datos
    },
    validateEmail() {
      const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      this.validEmail = regex.test(this.email);
    },
    estadoCampos() {
      this.estadoInputs = !this.estadoInputs;
    },
    datosEmpresa() {
      let me = this;
      var url = "/empresa";

      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          console.log(respuesta);

          me.empresa_id = respuesta.empresa.id;
          me.nombre = respuesta.empresa.nombre;
          me.direccion = respuesta.empresa.direccion;
          me.telefono = respuesta.empresa.telefono;
          me.email = respuesta.empresa.email;
          me.nit = respuesta.empresa.nit;
          me.licencia = respuesta.empresa.licencia;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    actualizarEmpresa() {
      if (this.validarEmpresa()) {
        return;
      }

      const formData = new FormData();
      formData.append("nombre", this.nombre);
      formData.append("direccion", this.direccion);
      formData.append("telefono", this.telefono);
      formData.append("email", this.email);
      formData.append("nit", this.nit);
      formData.append("id", this.empresa_id);

      if (this.logoFile) {
        formData.append("logo", this.logoFile);
      }

      axios
        .post("/empresa/actualizar", formData, {
          headers: { "Content-Type": "multipart/form-data" },
        })
        .then((response) => {
          this.estadoInputs = true;
          this.logoFile = null;
          this.toastSuccess("Actualización Exitosa");
        })
        .catch((error) => {
          console.log(error);
          Swal.fire({
            title: "Error",
            text: "Hubo un problema al actualizar",
            icon: "error"
          });
        });
    },
    validarEmpresa() {
      this.errorEmpresa = 0;
      this.errorMostrarMsjEmpresa = [];

      if (!this.nombre) {
        this.errorMostrarMsjEmpresa.push(
          "El nombre de la empresa no puede estar vacío."
        );
        Swal.fire({
          title: "ALERTA",
          text: "El nombre no puede estar vacío",
          icon: "warning"
        });
      }
      if (!this.direccion) {
        this.errorMostrarMsjEmpresa.push(
          "La direccion de la empresa no puede estar vacío."
        );
        Swal.fire({
          title: "ALERTA",
          text: "La dirección no puede estar vacía",
          icon: "warning"
        });
      }
      if (!this.telefono) {
        this.errorMostrarMsjEmpresa.push(
          "El telefono de la empresa no puede estar vacío."
        );
        Swal.fire({
          title: "ALERTA",
          text: "El teléfono no puede estar vacío",
          icon: "warning"
        });
      }
      if (!this.email) {
        this.errorMostrarMsjEmpresa.push(
          "El email de la empresa no puede estar vacío."
        );
        Swal.fire({
          title: "ALERTA",
          text: "El gmail no puede estar vacío",
          icon: "warning"
        });
      }
      if (!this.nit) {
        this.errorMostrarMsjEmpresa.push(
          "El NIT de la empresa no puede estar vacío."
        );
        Swal.fire({
          title: "ALERTA",
          text: "El Nit no puede estar vacío",
          icon: "warning"
        });
      }
      if (this.errorMostrarMsjEmpresa.length) this.errorEmpresa = 1;
      return this.errorEmpresa;
    },
  },
  mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    this.datosEmpresa();
  },
};
</script>
<style scoped>
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

.empresa-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 2rem;
  align-items: start;
}

@media screen and (max-width: 768px) {
  .empresa-grid {
    grid-template-columns: 1fr;
  }
}
</style>
