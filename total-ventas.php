<?php
// Asegurarse de que este archivo no se acceda directamente
if (!defined('ABSPATH')) {
    exit;
}

// Obtener los datos para las ventas de la semana
$current_user_id = get_current_user_id();
$chart_data = [
    'labels' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
    'data' => [
        'sales_amounts' => [0, 0, 0, 0, 0, 0, 0],
        'sales_counts' => [0, 0, 0, 0, 0, 0, 0]
    ]
];

// Establecer el inicio y fin de la semana
$start_of_week = strtotime('monday this week');
$end_of_week = strtotime('sunday this week 23:59:59');

// Obtener los pedidos completados de WooCommerce para el autor actual
$args = array(
    'limit' => -1,
    'status' => 'completed',
);
$orders = wc_get_orders($args);

// Procesar los datos de ventas
if (!empty($orders)) {
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            if ($product) {
                $related_courses = get_post_meta($product->get_id(), '_related_course', true);
                $related_courses = maybe_unserialize($related_courses);
                if (is_array($related_courses) && !empty($related_courses)) {
                    foreach ($related_courses as $course_id) {
                        $course_author = get_post_field('post_author', $course_id);
                        if ($course_author == $current_user_id) {
                            $order_date = $order->get_date_created()->getTimestamp();
                            if ($order_date >= $start_of_week && $order_date <= $end_of_week) {
                                $day_of_week = date('N', $order_date) - 1; // 0 = Lunes, 6 = Domingo
                                $chart_data['data']['sales_amounts'][$day_of_week] += $order->get_total();
                                $chart_data['data']['sales_counts'][$day_of_week] += 1;
                            }
                        }
                    }
                }
            }
        }
    }
}

?>

<h3>Ventas esta semana</h3>
<p>Lunes <?php echo date('d', $start_of_week); ?> hasta el Domingo <?php echo date('d', $end_of_week); ?></p>
<div id="module-two-chart-container">
    <canvas id="module-two-chart"></canvas>
</div>

<script type="text/javascript">
    // Datos para el gráfico
    const ventasChartData = <?php echo json_encode($chart_data); ?>;

    // Configuración del gráfico de barras usando Chart.js
    const ctxModuleTwo = document.getElementById('module-two-chart').getContext('2d');
    const moduleTwoChart = new Chart(ctxModuleTwo, {
        type: 'bar',
        data: {
            labels: ventasChartData.labels, // Días de la semana
            datasets: [
                {
                    label: 'Total de Ventas (CLP)',
                    data: ventasChartData.data.sales_amounts, // Montos totales de ventas
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y', // Asignar al eje Y izquierdo
                },
                {
                    label: 'Número de Ventas',
                    data: ventasChartData.data.sales_counts, // Número de ventas
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    yAxisID: 'yRight', // Asignar al eje Y derecho
                },
            ],
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10, // Valor máximo para el eje Y izquierdo
                    position: 'left', // Mantener el eje en el lado izquierdo
                    title: {
                        display: true,
                        text: 'Total de Ventas (CLP)',
                    },
                },
                yRight: {
                    beginAtZero: true,
                    max: 10, // Valor máximo para el eje Y derecho
                    position: 'right', // Mover el eje al lado derecho
                    title: {
                        display: true,
                        text: 'Número de Ventas',
                    },
                    grid: {
                        drawOnChartArea: false, // Evitar que las líneas de la cuadrícula se dupliquen
                    },
                },
            },
            responsive: true,
            maintainAspectRatio: true,
        },
    });
</script>
