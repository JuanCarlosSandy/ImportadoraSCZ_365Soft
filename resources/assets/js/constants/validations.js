import * as yup from "yup";

export const esquemaSucursal = yup.object().shape({
  nombre: yup
    .string()
    .required("El nombre de la sucursal es obligatorio")
    .max(50, "El nombre no puede tener más de 50 caracteres")
    .matches(
      /^[a-zA-Z0-9\s]+$/,
      "El nombre no puede contener caracteres especiales"
    ),

  direccion: yup.string().required("La dirección es obligatoria"),

  correo: yup
    .string()
    .email("Ingrese una dirección de correo electrónico válida")
    .required("El correo es obligatorio"),

  telefono: yup
    .string()
    .required("El teléfono es obligatorio")
    .matches(/^[0-9]{8}$/, "El teléfono debe contener exactamente 8 números"),

  departamento: yup.string().required("El departamento es obligatorio"),
});

export const esquemaMoneda = yup.object().shape({
  nombre: yup
    .string()
    .required("El nombre de la moneda es obligatorio")
    .max(50, "El nombre no puede tener más de 50 caracteres")
    .matches(
      /^[a-zA-Z0-9\s]+$/,
      "El nombre no puede contener caracteres especiales"
    ),

  pais: yup.string().required("El país es obligatorio"),
  simbolo: yup.string().required("El código de la moneda es obligatorio"),

  tipo_cambio: yup
    .number()
    .typeError("El tipo de cambio debe ser un número")
    .required("El tipo de cambio es obligatorio")
    .min(1, "El tipo de cambio no puede ser igual o menor a cero"),
});

export const esquemaKits = yup.object().shape({
  nombre: yup
    .string()
    .required("El nombre es obligatorio")
    .max(80, "El nombre no puede tener más de 80 caracteres"),
  codigo: yup
    .string()
    .required("El codigo es obligatorio")
    .max(100, "El codigo no puede tener más de 100 caracteres"),
  precio: yup
    .number()
    .required("El precio es obligatorio")
    .min(1, "El precio no puede ser menor o igual que 0")
    .typeError("Debe ser un número"),
  fecha_final: yup.string().required("La fecha final es obligatoria"),
});

export const esquemaOfertasEspeciales = yup.object().shape({
  nombre: yup
    .string()
    .required("El nombre es obligatorio")
    .max(80, "El nombre no puede tener más de 80 caracteres"),
  fecha_final: yup.string().required("La fecha final es obligatoria"),

  precio_r1: yup
    .number()
    .required("El precio del rango 1 es obligatorio")
    .min(1, "El precio no puede ser menor o igual que 0")
    .typeError("Debe ser un número"),
  rango_inicio_r1: yup
    .number()
    .required("El rango inicio 1 es obligatorio")
    .min(1, "El rango no puede ser menor o igual que 0")
    .typeError("Debe ser un número"),
  rango_final_r1: yup
    .number()
    .required("El precio es obligatorio")
    .min(
      yup.ref("rango_inicio_r1"),
      "El rango final debe ser mayor que el rango inicial"
    )
    .typeError("Debe ser un número"),

  precio_r2: yup
    .number()
    .required("El precio del rango 2 es obligatorio")
    .min(1, "El precio no puede ser menor o igual que 0")
    .typeError("Debe ser un número"),
  rango_inicio_r2: yup
    .number()
    .required("El precio es obligatorio")
    .min(
      yup.ref("rango_final_r1"),
      "El rango inicio 2 debe ser mayor al rango final 1"
    )
    .typeError("Debe ser un número"),
  rango_final_r2: yup
    .number()
    .required("El precio es obligatorio")
    .min(
      yup.ref("rango_inicio_r2"),
      "El rango final 2 debe ser mayor al rango inicio 2"
    )
    .typeError("Debe ser un número"),

  precio_r3: yup
    .number()
    .required("El precio es obligatorio")
    .min(1, "El precio no puede ser menor o igual que 0")
    .typeError("Debe ser un número"),
  rango_inicio_r3: yup
    .number()
    .required("El precio es obligatorio")
    .min(
      yup.ref("rango_final_r2"),
      "El rango inicio 3 debe ser mayor al rango final 2"
    )
    .typeError("Debe ser un número"),
  rango_final_r3: yup
    .number()
    .required("El precio es obligatorio")
    .min(
      yup.ref("rango_inicio_r3"),
      "El rango final 3 debe ser mayor al rango inicio 3"
    )
    .typeError("Debe ser un número"),
});

export const esquemaOfertas = yup.object().shape({
  nombre: yup
    .string()
    .required("El nombre es obligatorio")
    .max(80, "El nombre no puede tener más de 80 caracteres"),
  porcentaje: yup
    .number()
    .required("El porcentaje es obligatorio")
    .min(0, "El porcentaje no puede ser menor que 0")
    .max(100, "El porcentaje no puede ser mayor que 100")
    .typeError("Debe ser un número"),
  fecha_final: yup.string().required("La fecha final es obligatoria"),
});
export const esquemaArticulos = yup.object().shape({
  nombre: yup
    .string()
    .required("El Nombre del Producto es obligatorio")
    .max(80, "El nombre no puede tener más de 80 caracteres"),
  codigo: yup.string().required("El Código del Producto es obligatorio"),
  codigo_alfanumerico: yup.string().nullable(),
  descripcion_fabrica: yup.string().required("La Medida del Producto es obligatoria"),
  unidad_envase: yup
    .number()
    .required("Las Unidades x Caja son obligatorias")
    .typeError("Debe ingresar un número válido")
    .min(1, "Las Unidades x Caja deben ser mayor a 0"),
  precio_costo_unid: yup
    .number()
    .required("El Costo de Compra es obligatorio")
    .typeError("Debe ingresar un número válido")
    .min(0.01, "El Costo de Compra debe ser mayor a 0"),
  stock: yup
    .number()
    .required("El Stock Mínimo es obligatorio")
    .typeError("Debe ingresar un número válido")
    .min(1, "El Stock Mínimo debe ser mayor a 0"),
  idcategoria: yup
    .number()
    .required("Debe seleccionar una Categoría")
    .typeError("Debe seleccionar una Categoría"),
  idproveedor: yup
    .number()
    .required("Debe seleccionar un Proveedor")
    .typeError("Debe seleccionar un Proveedor"),
  precio_uno: yup
    .number()
    .required("El Precio por Unidad es obligatorio")
    .typeError("Debe ingresar un número válido")
    .min(0.01, "El Precio por Unidad debe ser mayor a 0"),
  precio_dos: yup
    .number()
    .required("El Precio por Docena es obligatorio")
    .typeError("Debe ingresar un número válido")
    .min(0.01, "El Precio por Docena debe ser mayor a 0"),
  precio_tres: yup
    .number()
    .required("El Precio por Paquete es obligatorio")
    .typeError("Debe ingresar un número válido")
    .min(0.01, "El Precio por Paquete debe ser mayor a 0"),
  precio_cuatro: yup.number().nullable().typeError("Debe ingresar un número válido"),
  
});
export const esquemaInventario = yup.object().shape({
  AlmacenSeleccionado: yup.number().required('El campo Almacen es obligatorio'),
  fechaVencimientoAlmacen : yup
    .string()
    .required("La fecha de vencimiento es obligatorio"),
  unidadStock: yup
  .number()
  .required("La cantidad de Stock es obligatoria")
  .typeError("Debe ingresar un número válido")
  .min(
    0.01,
    "Cantidad en stock no puede ser menor o igual a 0"
  ),
});
export const esquemaAlmacen = yup.object().shape({
  nombre_almacen: yup
    .string()
    .required("El nombre del almacén es obligatorio")
    .max(80, "El nombre del almacén no puede tener más de 80 caracteres"),
  encargado: yup.string().required("El nombre del encargado es obligatorio"),
  sucursal: yup.string().required("El nombre de la sucursal es obligatorio"),
  observaciones: yup
    .string()
    .max(255, "Las observaciones no pueden tener más de 255 caracteres"),
});

export const esquemaCliente = yup.object().shape({
  nombre: yup
    .string()
    .required("El nombre del cliente es obligatorio")
    .max(80, "El nombre del cliente no puede tener más de 80 caracteres"),

  tipo_documento: yup.string().required("El tipo de documento es obligatorio"),
  num_documento: yup.string().required("El número de documento es obligatorio"),
  direccion: yup.string().required("La ubicación es obligatoria"),

});

export const esquemaPuntoDeVenta = yup.object().shape({
  nombre: yup
    .string()
    .required("El nombre del punto de venta es obligatorio")
    .max(
      100,
      "El nombre del punto de venta no puede tener más de 100 caracteres"
    ),
  nit: yup.string().required("El NIT del punto de venta es obligatorio"),
  descripcion: yup
    .string()
    .required("La descripción del punto de venta es obligatoria"),
  idtipopuntoventa: yup
    .string()
    .required("El tipo de punto de venta es obligatorio"),
  idsucursal: yup.string().required("La sucursal es obligatoria"),
});

export const esquemaBanco = yup.object().shape({
  nombre_cuenta: yup.string().required("El nombre de la cuenta es obligatorio"),

  nombre_banco: yup.string().required("El nombre del banco es obligatorio"),
  numero_cuenta: yup
    .number()
    .typeError("El número de cuenta debe ser un número")
    .required("El número de cuenta es obligatorio")
    .min(1, "El número de cuenta no puede ser igual o menor a cero"),
  tipo_cuenta: yup.string().required("El tipo de cuenta es obligatorio"),
});

export const esquemaProveedor = yup.object().shape({
  nombre: yup.string().required("El nombre es obligatorio"),
  telefono: yup.string().required("El teléfono es obligatorio"),
  contacto: yup.string().required("El contacto es obligatorio"),
  telefono_contacto: yup
    .string()
    .required("El teléfono del contacto es obligatorio"),
});
