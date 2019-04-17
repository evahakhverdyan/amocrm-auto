<?php
/**
 * Возвращает авторизованный клиент API Google
 * @return Google_Client
 */
function getClient() {
    $client = new Google_Client();
    $client->setApplicationName('Интерфейс инструктора автошколы ВОА');
    $client->setScopes([Google_Service_Calendar::CALENDAR_EVENTS]);
    $client->setAuthConfig('../credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    $tokenPath = '../token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }

            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        else {
            throw new Exception("Ошибка токена: ".join(', ', $accessToken));
        }
    }
    return $client;
}
function getCalendarList() {
    return [
        "788903"  => "dbhe2bkg2e4c5p9tgqjk9qdlps@group.calendar.google.com", //Мельников Александр Геннадьевич
        "920525"  => "423eu1ams26911lcje1tv7c994@group.calendar.google.com", //Кокорин Дмитрий Владимирович
        "920527"  => "jc9dusa5n43hsqv6kn8shu8fjc@group.calendar.google.com", //Шматко Дмитрий Николаевич
        "920531"  => "vovpks0oodl64pscluhc613hek@group.calendar.google.com", //Кузнецов Иван Николаевич
        "920533"  => "juot3gdq89rber7q55kd7m34lo@group.calendar.google.com", //Кузнецов Геннадий Григорьевич
        "920537"  => "thph1brp7lh38881f16t8u16jk@group.calendar.google.com", //Коняхин Алексей Петрович
        "920539"  => "6bt34i3qg228jj3prro1iokres@group.calendar.google.com", //Прокопов Антон Викторович
        "920563"  => "idtrsqrnk20l233doidl8p83d8@group.calendar.google.com", //Юдин Александр Николаевич
        "1068181" => "59oc9t2attulefbm1tncf620e0@group.calendar.google.com", //Беликов Вячеслав Дмитриевич
        "1074817" => "kqofepg4leucaco7ma4514h574@group.calendar.google.com", //Монахов Алексей Валерьевич
        "1074819" => "cb915lf43oahv74m4iabi2k7qo@group.calendar.google.com", //Кисилев Сергей Сергеевич
        "1090033" => "d3ca8ghfr0avddfmrtjn0b2bmk@group.calendar.google.com", //Мельников Владимир Юрьевич
        "1098473" => "2qkr3ue25lu8vh1c35i8t1plg8@group.calendar.google.com", //Мельников Дмитрий Геннадьевич
    ];
}
function getInstructorCalendarId($instructorId) {
    $list = getCalendarList();
    return $list[$instructorId];
}
function getEvents($service, $calendarId, $timestamp) {
    $startOfDay = $timestamp;
    $dayLengthSeconds = 86400;
    $endOfDay = $startOfDay + $dayLengthSeconds;

    $optParams = [
        'maxResults'   => 10,
        'orderBy'      => 'startTime',
        'singleEvents' => true,
        'timeMin'      => date('c', $startOfDay),
        'timeMax'      => date('c', $endOfDay),
    ];
    $results = $service->events->listEvents($calendarId, $optParams);
    $events = $results->getItems();

    return empty($events)
        ? false
        : $events;
}
function getAllEvents($service, $calendarId, $timestamp) {
    $startOfDay = $timestamp;

    $optParams = [
        'maxResults'   => 10,
        'orderBy'      => 'startTime',
        'singleEvents' => true,
        'timeMin'      => date('c', $startOfDay),
    ];
    $results = $service->events->listEvents($calendarId, $optParams);
    $events = $results->getItems();

    return empty($events)
        ? false
        : $events;
}
function getTimeframes($service, $calendarId, $timestamp) {
    $timeframes = [
        "09:00" => [],
        "10:30" => [],
        "12:00" => [],
        "13:30" => [],
        "15:00" => [],
        "16:30" => [],
        "18:00" => [],
        "19:30" => [],
        "21:00" => [],
    ];
    $events = getEvents($service, $calendarId, $timestamp);

    if (!$events) {
        return $timeframes;
    }

    foreach ($events as $event) {
        $start = new DateTime($event->start->dateTime);
        $end = new DateTime($event->end->dateTime);
        $timeframe = $start->format('H:i');

        $timeframes[$timeframe][] = [
            'start' => $start->format('H:i'),
            'end' => $end->format('H:i'),
            'text' => $event->getSummary(),
        ];
    }

    return $timeframes;
}
function addEvent($service, $calendarId, $studentName, $date, $startTime) {
    $startTime = DateTime::createFromFormat('Y-m-d H:i:s', $date." ".$startTime, new DateTimeZone('Europe/Moscow'));
    $oneAndHalfHourSpec = 'PT1H30M0S';
    $endTime = clone $startTime;
    $endTime->add(new DateInterval($oneAndHalfHourSpec));

    $event = new Google_Service_Calendar_Event([
        'summary'     => $studentName,
        'description' => EVENT_DESCRIPTION,
        'start'       => [
            'dateTime' => $startTime->format(DateTime::ISO8601),
            'timeZone' => 'Europe/Moscow',
        ],
        'end'         => [
            'dateTime' => $endTime->format(DateTime::ISO8601),
            'timeZone' => 'Europe/Moscow',
        ]
    ]);

    $event = $service->events->insert($calendarId, $event);
    return ($event->htmlLink)
        ? $event->htmlLink
        : false;
}