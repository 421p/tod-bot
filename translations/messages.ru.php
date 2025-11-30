<?php

return [
    'help.unrecognized_time' => "Не удалось распознать время. Примеры:\n"
        . ".tod icarus 14:30 Europe/Kyiv\n"
        . ".tod behemoth 1430 UTC+2\n"
        . ".tod catgang 2025-11-28 14:00 UTC\n"
        . ".tod shuriel now\n"
        . ".tod skylancer 30m ago",

    'common.last_tod' => 'Последний ТоД',
    'common.death_time' => 'Время смерти',
    'common.window_start' => 'Начало окна',
    'common.window_end' => 'Конец окна',
    'common.no_boss' => 'Нету ТоДа для **%boss%**.',
    'common.none_available' => 'Нет доступных боссов.',

    'list.header' => 'Текущие ТоД/окна:',
    'list.opens_in' => '%boss% — окно открывается:',
    'list.closes_in' => '%boss% — окно закрывается:',

    'tod.title' => '💀 %boss% был отпизжен.',
    'window.title' => '📅 Окно респа: %boss%',
    'del.title' => '❌ Удалили ТоД: %boss%',

    'reminder.start.title' => '⏰ Окно респа открылось: %boss%',
    'reminder.end.title' => '⚠️ Окно респа закрылось: %boss%',
    'reminder.start.field' => 'Начало окна:',
    'reminder.end.field' => 'Конец окна: ',
];
