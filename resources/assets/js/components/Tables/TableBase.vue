<template>
  <div>
    <DataTable :value="items" :rows="perPage" :totalRecords="rows" :first="(currentPage - 1) * perPage" responsiveLayout="scroll" class="p-datatable-sm">
      <template #empty>
        Parece que aún no has realizado ningún movimiento.
      </template>
      <Column v-for="field in fields" :key="field.key" :field="field.key" :header="field.label" />
    </DataTable>
    <Paginator
      :rows="perPage"
      :totalRecords="rows"
      :first="(currentPage - 1) * perPage"
      @page="onPageChange"
      class="p-mt-2"
    />
  </div>
</template>

<script>
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';

export default {
  props: {
    items: {
      type: Array,
      required: true
    },
    perPage: {
      type: Number,
      required: true
    },
    rows: {
      type: Number,
      required: true
    },
    fields: {
      type: Array,
      required: true
    },
  },
  data() {
    return {
      currentPage: 1,
    };
  },
  components: {
    DataTable,
    Column,
    Paginator,
  },
  methods: {
    onPageChange(event) {
      this.currentPage = event.page + 1;
      this.$emit('change-pagination', this.currentPage);
    },
  },
};
</script>
