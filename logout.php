<?php
require __DIR__ . '/includes/bootstrap.php';

logout_user();
session_start();
flash('info', 'Sesión cerrada. Tu progreso está guardado.');
redirect('index.php');
