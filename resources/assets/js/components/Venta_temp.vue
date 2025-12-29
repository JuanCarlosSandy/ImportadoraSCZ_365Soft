/* Estilos para la columna de stock */
.stock-badge {
  background-color: #2563eb !important; /* Azul */
  color: white !important;
  padding: 0.3rem 0.6rem !important;
  border-radius: 0.375rem !important;
  font-weight: 600 !important;
  font-size: 0.875rem !important;
  display: inline-block !important;
  min-width: 40px !important;
  text-align: center !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
}

/* Responsive para el stock badge */
@media (max-width: 768px) {
  .stock-badge {
    padding: 0.25rem 0.5rem !important;
    font-size: 0.8rem !important;
    min-width: 35px !important;
  }
}

@media (max-width: 480px) {
  .stock-badge {
    padding: 0.2rem 0.4rem !important;
    font-size: 0.75rem !important;
    min-width: 30px !important;
  }
}