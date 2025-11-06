<?php
// Configuración general del sistema
const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'tdcsa';

// Se establece la zona horaria solicitada para todos los cálculos de fechas
if (!date_default_timezone_set('America/Argentina/Cordoba')) {
    date_default_timezone_set('UTC');
}
