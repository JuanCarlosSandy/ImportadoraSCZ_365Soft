<template>
  <div>
    <TableBase :items="items" :rows="rows" :per-page="perPage" :fields="fields" @change-pagination="changePaginationCurrent" />
  </div>
</template>

<script>
import TableBase from './TableBase.vue';
import axios from 'axios';
export default {
  props: {
    data: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      perPage: 0,
      currentPage: 1,
      fields: [
        { key: 'fecha', label: 'Fecha' },
        { key: 'transaccion', label: 'Transacción' },
        { key: 'importe', label: 'Importe' },
        { key: 'usuario', label: 'Usuario' }
      ],
      items: []
    };
  },
  created() {
    this.items = this.data.data;
    this.perPage = this.data.per_page;
  },
  components: {
    TableBase
  },
  computed: {
    rows() {
      return this.data.total;
    }
  },
  methods: {
    changePaginationCurrent(pageNum) {
      this.getDatawithPage(pageNum);
    },
    getDatawithPage(pageNumber) {
      const url = new URL(this.data.path, window.location.origin);
      const ruta = url.pathname.substring(1);
      axios.get(`${ruta}?page=${pageNumber}`)
        .then((response) => {
          this.items = response.data.transacciones.data;
          this.perPage = response.data.transacciones.per_page;
        })
        .catch((error) => {
          console.log(error);
        });
    }
  },
};
</script>
