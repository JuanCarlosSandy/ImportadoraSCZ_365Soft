<template>
  <main class="main">

    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">CARGANDO...</div>
      </div>
    </div>

    <Panel>
      
      <template #header>
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="pi pi-bars panel-icon"></i>
            <h4 class="panel-title" style="margin: 0;">DATOS DE EMPRESA</h4>
          </div>
          <Button v-if="estadoInputs" icon="pi pi-pencil" :label="mostrarLabel ? 'Editar' : ''"
            class="p-button-secondary p-button-sm" @click="estadoCampos"
            style="background-color: #f59e0b; border: none; color: white; padding: 0.75rem 1.5rem; font-weight: bold; font-size: 1rem;" />
        </div>
      </template>

      <!-- Sección de Información General -->
      <div class="info-section">
        <h5 class="section-title">
          <i class="pi pi-info-circle"></i> Información General
        </h5>
        <div class="p-fluid formgrid grid">
          <div class="field col-12 md:col-6">
            <label for="nombre"><strong>Nombre de Empresa</strong></label>
            <InputText id="nombre" v-model="nombre" placeholder="Ingrese el nombre de la empresa"
              :disabled="estadoInputs" />
          </div>

          <div class="field col-12 md:col-6">
            <label for="nit"><strong>NIT de Empresa</strong></label>
            <InputText id="nit" v-model="nit" placeholder="Ingrese el NIT" :disabled="estadoInputs" />
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
        </div>
      </div>

      <!-- Sección de Imágenes -->
      <div class="images-section">
        <h5 class="section-title">
          <i class="pi pi-image"></i> Imágenes del Sistema
        </h5>
        
        <div class="images-grid">
          <!-- Sección Logo Principal -->
          <div class="image-card">
            <div class="image-header">
              <h6>Logo Principal</h6>
              <span class="image-info">PNG (90% calidad)</span>
            </div>
            <div class="image-container">
              <img :src="imagenTemporal || '/img/logoPrincipal.png'" 
                alt="Logo Empresa" 
                class="imagen-preview"
                @error="handleImageError" />
            </div>
            <div v-if="!estadoInputs" class="image-actions">
              <input ref="inputLogo" type="file" accept="image/*" @change="seleccionarLogo" style="display: none;" />
              <Button label="Cambiar logo" icon="pi pi-image" 
                class="p-button-outlined p-button-info p-button-sm" 
                style="width: 100%;" @click="abrirSelectorLogo" />
            </div>
          </div>

          <!-- Sección Imagen de Fondo Login -->
          <div class="image-card">
            <div class="image-header">
              <h6>Fondo de Login</h6>
              <span class="image-info">JPG (90% calidad)</span>
            </div>
            <div class="image-container">
              <img :src="imagenFondoTemporal || '/img/BackLoginFarma.jpg'" 
                alt="Fondo Login" 
                class="imagen-preview"
                @error="handleImageError" />
            </div>
            <div v-if="!estadoInputs" class="image-actions">
              <input ref="inputFondo" type="file" accept="image/*" @change="seleccionarFondo" style="display: none;" />
              <Button label="Cambiar fondo" icon="pi pi-image" 
                class="p-button-outlined p-button-warning p-button-sm" 
                style="width: 100%;" @click="abrirSelectorFondo" />
            </div>
          </div>
        </div>
      </div>

      <!-- Botones de Acción -->
      <div class="action-buttons" v-if="!estadoInputs">
        <Button label="Cancelar" icon="pi pi-times" 
          class="p-button-danger p-button-sm" @click="cancelarEdicion"
          style="flex: 1;" />
        <Button label="Actualizar" icon="pi pi-save" 
          class="p-button-success p-button-sm" @click="actualizarEmpresa"
          style="flex: 1;" />
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
      isLoading: false,
      mostrarLabel: true,
      imagenTemporal: null,
      imagenFondoTemporal: null,
      logoFile: null,
      fondoFile: null,
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
    handleImageError(event) {
      // Manejo de errores cuando la imagen no se encuentra
      event.target.style.opacity = "0.5";
    },
    abrirSelectorLogo() {
      this.$refs.inputLogo.click();
    },
    abrirSelectorFondo() {
      this.$refs.inputFondo.click();
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
    seleccionarFondo(event) {
      const file = event.target.files[0];
      if (file && file.type.startsWith("image/")) {
        this.fondoFile = file;

        const reader = new FileReader();
        reader.onload = (e) => {
          this.imagenFondoTemporal = e.target.result;
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
      this.logoFile = null;
      this.fondoFile = null;
      this.imagenTemporal = null;
      this.imagenFondoTemporal = null;
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
    })
    .finally(() => {
      setTimeout(() => {
        this.isLoading = false;
      }, 500); 
    });
    },
    actualizarEmpresa() {
      if (this.validarEmpresa()) {
        return;
      }

      this.isLoading = true;

      const cambioImagenes = this.logoFile !== null || this.fondoFile !== null;

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

      if (this.fondoFile) {
        formData.append("backLoginFarma", this.fondoFile);
      }

      axios
        .post("/empresa/actualizar", formData, {
          headers: { "Content-Type": "multipart/form-data" },
        })
        .then((response) => {
          this.isLoading = false;

          if (cambioImagenes) {
            
            
            Swal.fire({
              title: "Cambios guardados",
              text: "Para visualizar el nuevo logo o fondo correctamente, es necesario actualizar la página.",
              icon: "info",
              showCancelButton: false,
              confirmButtonColor: "#3085d6",
              confirmButtonText: "Actualizar página",
              allowOutsideClick: false,
              allowEscapeKey: false
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.reload();
              }
            });

          } else {
            this.estadoInputs = true;
            this.logoFile = null;
            this.fondoFile = null;
            
           
            const timestamp = new Date().getTime();
            if (this.imagenTemporal && !this.imagenTemporal.startsWith('data:')) {
               this.imagenTemporal = this.imagenTemporal.split('?')[0] + '?t=' + timestamp;
            }
            if (this.imagenFondoTemporal && !this.imagenFondoTemporal.startsWith('data:')) {
               this.imagenFondoTemporal = this.imagenFondoTemporal.split('?')[0] + '?t=' + timestamp;
            }

            this.toastSuccess("Actualización Exitosa");
            this.datosEmpresa();
          }
        })
        .catch((error) => {
          console.log(error);
          this.isLoading = false;
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
  padding: 2rem;
}

>>>.p-panel .p-panel-header {
  padding: 0.75rem 1rem;
  border-bottom: none;
  color: white;
}

>>>.p-panel .p-panel-header .p-panel-title {
  font-weight: 600;
  color: white;
}

>>>.p-panel-title {
  color: white;
}

/* Secciones */
.info-section,
.images-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background-color: #f8fafc;
  border-radius: 8px;
  border-left: 4px solid #667eea;
}

.section-title {
  margin: 0 0 1.5rem 0;
  color: #1f2937;
  font-size: 1.1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-title i {
  color: #667eea;
}

/* Grid de imágenes */
.images-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  margin-bottom: 1rem;
}

.image-card {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.image-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.image-header {
  margin-bottom: 1rem;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 0.75rem;
}

.image-header h6 {
  margin: 0 0 0.25rem 0;
  color: #1f2937;
  font-size: 1rem;
  font-weight: 600;
}

.image-info {
  font-size: 0.75rem;
  color: #9ca3af;
  font-weight: 500;
}

.image-container {
  width: 100%;
  height: 250px;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
  overflow: hidden;
  border: 2px dashed #d1d5db;
}

.imagen-preview {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 0.5rem;
}

.image-actions {
  display: flex;
  gap: 0.5rem;
}

.image-actions button {
  flex: 1;
}

/* Botones de acción */
.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.action-buttons button {
  min-width: 120px;
}

/* Loading overlay */
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
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsivo */
@media screen and (max-width: 768px) {
  >>>.p-panel .p-panel-content {
    padding: 1.5rem;
  }

  .images-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .image-container {
    height: 200px;
  }

  .action-buttons {
    flex-direction: column;
  }

  .info-section,
  .images-section {
    padding: 1rem;
  }

  .section-title {
    font-size: 1rem;
  }
}

/* Estilos para inputs deshabilitados */
>>>.p-inputtext:disabled {
  background-color: #f3f4f6;
  color: #6b7280;
}

/* Estilos adicionales para mejor UX */
>>>.p-button {
  transition: all 0.3s ease;
}

>>>.p-button:hover {
  transform: translateY(-1px);
}

>>>.p-button-outlined.p-button-info:hover,
>>>.p-button-outlined.p-button-warning:hover {
  background-color: rgba(102, 126, 234, 0.1);
}
</style>