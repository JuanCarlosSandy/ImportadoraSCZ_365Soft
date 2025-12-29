import useVuelidate from "@vuelidate/core";
import { required, minValue } from "@vuelidate/validators";
import Panel from "primevue/panel";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ColumnGroup from "primevue/columngroup";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";

export default {
  setup() {
    return {
      v$: useVuelidate(),
    };
  },

  data() {
    return {
      array_ingresos: [],
      array_cuotas: [],
      displayListaCuotas: false,
      displayPagarCuota: false,
      lista_tipo_pago_cuotas: [
        { nombre: "Efectivo" },
        { nombre: "Tarjeta" },
        { nombre: "QR" },
      ],
      idCuotaActual: null,
      form: {
        fecha_pago: null,
        tipo_pago_cuota: null,
        cuota_actual: null,
        pago_actual: null,
      },
    };
  },

  components: {
    Panel,
    DataTable,
    Column,
    ColumnGroup,
    InputText,
    Button,
    Tag,
    Dialog,
    InputNumber,
    Dropdown,
  },

  computed: {},

  watch: {},

  methods: {
    listarIngresosCuotas() {
      let me = this;
      var url = "/ingresoCuotas/listarCuotas";
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.array_ingresos = respuesta.ingresos;
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    async listarCuotasPorIngreso(ingresoId) {
      try {
        const cuotasResponse = await axios.get(
          "/ingresoCuotas/cuotasPorIngreso?id=" + ingresoId,
        );

        if (cuotasResponse.data.status === "success") {
          let cuotas = cuotasResponse.data.cuotas.slice(1);

          this.array_cuotas = cuotas.map((cuota, index) => ({
            ...cuota,
            fecha_pago: cuota.fecha_pago.split(" ")[0],
            enumeracion: index + 1,
          }));
        }
      } catch (error) {
        console.error(error);
      }
    },

    cortarString(cadena) {
      return cadena.substring(0, 9);
    },

    openModalListaCuotas(ingresoId) {
      this.displayListaCuotas = true;
      this.listarCuotasPorIngreso(ingresoId);
    },

    closeModalListaCuotas() {
      this.displayListaCuotas = false;
    },

    openModalPagoCuota(data) {
      this.displayListaCuotas = false;
      this.displayPagarCuota = true;
      this.form.cuota_actual = data.precio_cuota;
      this.idCuotaActual = data.id;

      const fechaActual = new Date();
      this.form.fecha_pago = fechaActual.toISOString().split("T")[0];
    },

    closeModalPagoCuota() {
      this.displayPagarCuota = false;
    },

    async pagarCuota() {
      try {
        const response = await axios.post("/ingresoCuotas/pagarCuota", {
          id: this.idCuotaActual,
          form: this.form,
        });
        if (response.data.status === "success") {
          this.$toast.add({
            severity: "success",
            summary: "Exito",
            detail: "Cuota pagada correctamente",
            life: 3000,
          });
          this.closeModalPagoCuota();
          this.listarIngresosCuotas();
        } else {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo procesar el pago",
            life: 3000,
          });
        }
      } catch (error) {
        console.log(error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Ocurrio un error al proccesar el pago",
          life: 3000,
        });
      }
    },
  },

  created() {},

  mounted() {
    this.listarIngresosCuotas();
  },
};
