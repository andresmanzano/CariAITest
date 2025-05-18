<?php

function classifyAttendances($concepts, $attendanceIn, $attendanceOut) {
    // Inicializamos un array para almacenar el total de horas por cada concepto.
    // Aseguramos que HO, HED y HEN estén presentes con valor inicial 0.
    $result = [
        "HO" => 0,
        "HED" => 0,
        "HEN" => 0,
    ];

    // Convertimos las horas de entrada y salida de la asistencia a objetos DateTime
    // para facilitar la comparación y el cálculo de diferencias.
    $attendanceStartTime = new DateTime($attendanceIn);
    $attendanceEndTime = new DateTime($attendanceOut);

    // Manejo del caso donde la hora de salida es anterior a la hora de entrada (cruza la medianoche).
    $attendanceEndDate = clone $attendanceStartTime;
    if ($attendanceEndTime < $attendanceStartTime) {
        $attendanceEndDate->modify('+1 day');
        $attendanceEndTime->setDate($attendanceEndDate->format('Y'), $attendanceEndDate->format('m'), $attendanceEndDate->format('d'));
    } else {
        $attendanceEndTime->setDate($attendanceStartTime->format('Y'), $attendanceStartTime->format('m'), $attendanceStartTime->format('d'));
    }

    // Iteramos sobre cada uno de los conceptos (HO, HED, HEN) proporcionados.
    foreach ($concepts as $concept) {
        // Crea objetos DateTime para las horas de inicio y fin del concepto.
        $conceptStartTime = new DateTime($concept['start']);
        $conceptEndTime = new DateTime($concept['end']);

        // Manejo del caso donde la hora de fin del concepto es anterior a la hora de inicio (cruza la medianoche).
        $conceptEndDate = clone $conceptStartTime;
        if ($conceptEndTime < $conceptStartTime) {
            $conceptEndDate->modify('+1 day');
            $conceptEndTime->setDate($conceptEndDate->format('Y'), $conceptEndDate->format('m'), $conceptEndDate->format('d'));
        } else {
            $conceptEndTime->setDate($conceptStartTime->format('Y'), $conceptStartTime->format('m'), $conceptStartTime->format('d'));
        }

        // Manejo especial para el concepto de Horas Extras Nocturnas (HEN) que puede cruzar la medianoche.
        if ($concept['id'] === 'HEN' && (new DateTime($concept['start'])) > (new DateTime($concept['end']))) {
            // Divide el intervalo HEN en dos partes: hasta el final del día y desde el inicio del día siguiente.
            $midnight = clone $conceptStartTime;
            $midnight->setTime(24, 0, 0);

            // Calcula la superposición de la asistencia con la primera parte del HEN.
            $overlapStart1 = max($attendanceStartTime, $conceptStartTime);
            $overlapEnd1 = min($attendanceEndTime, $midnight);

            if ($overlapStart1 < $overlapEnd1) {
                // Si hay superposición, calculamos la duración de esa superposición.
                $interval1 = $overlapStart1->diff($overlapEnd1);
                // Obtenemos la diferencia en segundos.
                $totalSeconds1 = ($interval1->d * 24 * 3600) + ($interval1->h * 3600) + ($interval1->i * 60) + $interval1->s;
                // Convertimos los segundos a horas y sumamos las horas de la superposición al total del concepto actual.
                $result['HEN'] += $totalSeconds1 / 3600;
            }

            // Calcula la superposición de la asistencia con la segunda parte del HEN (al día siguiente).
            $midnightStartNextDay = clone $conceptStartTime;
            $midnightStartNextDay->setTime(0, 0, 0);
            $conceptEndTimeNextDay = clone $conceptEndTime;
            $conceptEndTimeNextDay->setDate($conceptEndDate->format('Y'), $conceptEndDate->format('m'), $conceptEndDate->format('d'));

            $overlapStart2 = max($attendanceStartTime, $midnightStartNextDay);
            $overlapEnd2 = min($attendanceEndTime, $conceptEndTimeNextDay);

            // Asegurar que las fechas sean coherentes para la comparación
            $overlapStart2->setDate($conceptEndDate->format('Y'), $conceptEndDate->format('m'), $conceptEndDate->format('d'));

            if ($overlapStart2 < $overlapEnd2) {
                // Si hay superposición, calculamos la duración de esa superposición.
                $interval2 = $overlapStart2->diff($overlapEnd2);
                // Obtenemos la diferencia en segundos.
                $totalSeconds2 = ($interval2->d * 24 * 3600) + ($interval2->h * 3600) + ($interval2->i * 60) + $interval2->s;
                // Convertimos los segundos a horas y sumamos las horas de la superposición al total del concepto actual.
                $result['HEN'] += $totalSeconds2 / 3600;
            }
        } 
        else // Para los conceptos que no cruzan la medianoche.
        {            
            $overlapStart = max($attendanceStartTime, $conceptStartTime);
            $overlapEnd = min($attendanceEndTime, $conceptEndTime);

            if ($overlapStart < $overlapEnd) {
                // Si hay superposición, calculamos la duración de esa superposición.
                $interval = $overlapStart->diff($overlapEnd);
                // Obtenemos la diferencia en segundos.
                $totalSeconds = ($interval->d * 24 * 3600) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
                // Convertimos los segundos a horas y sumamos las horas de la superposición al total del concepto actual.
                $result[$concept['id']] += $totalSeconds / 3600;
            }
        }
    }

    // Preparamos el array de salida, incluyendo solo los conceptos con horas laboradas.
    $output = [];
    if ($result["HO"] > 0) {
        $output["HO"] = round($result["HO"], 1); // Redondeamos a 1 decimal
    }
    if ($result["HED"] > 0) {
        $output["HED"] = round($result["HED"], 1); // Redondeamos a 1 decimal
    }
    if ($result["HEN"] > 0) {
        $output["HEN"] = round($result["HEN"], 1); // Redondeamos a 1 decimal
    }

    // Convertimos el array de resultados a formato JSON y lo devolvemos.
    return json_encode($output);
}

// Ejemplos de uso de la función con los datos proporcionados.
$concepts1 = [
    [
        "id" => "HO",
        "name" => "HO",
        "start" => "08:00",
        "end" => "17:59"
    ],
    [
        "id" => "HED",
        "name" => "HED",
        "start" => "18:00",
        "end" => "20:59"
    ],
    [
        "id" => "HEN",
        "name" => "HEN",
        "start" => "21:00",
        "end" => "05:59"
    ]
];
$attendanceIn1 = "08:00";
$attendanceOut1 = "11:30";
echo "Ejemplo 1: " . classifyAttendances($concepts1, $attendanceIn1, $attendanceOut1) . "\n";

$concepts2 = [
    [
        "name"=> "HO",
        "id"=> "HO",
        "start"=> "08:00",
        "end"=> "17:59"
    ],
    [
        "id"=> "HED",
        "name"=> "HED",
        "start"=> "18:00",
        "end"=> "20:59"
    ],
    [
        "id"=> "HEN",
        "name"=> "HEN",
        "start"=> "21:00",
        "end"=> "05:59"
    ]
];
$attendanceIn2 = "14:00";
$attendanceOut2 = "21:30";
echo "Ejemplo 2: " . classifyAttendances($concepts2, $attendanceIn2, $attendanceOut2) . "\n";

?>