# Тестирование

## Запуск тестов

Перед запуском тестов выполните composer lib install для того что бы запустить инсталлеры.

Что бы запустить тесты вы обязаны поставить переменую окружения `APP_ENV` в `dev`. Либо задать ее значение в конфиге `config/env_config.php`.

Если переменная `APP_ENV` установлена в **dev**, тогда вы можете переопрделеить ее на prod в тестах, используя аргументы командкой строки.
Либо задать переменную окруения исключетельно для техучего процесаа `APP_ENV=dev composer test` или `APP_ENV=dev phpunit`

Пример тестов которые тестируют разное окружение -- [skeleton-test](https://github.com/rollun-com/rollun-skeleton/tree/master/tests/src/Api)

## Отладка

Если вам нужно отладить интеллеры вы можете [воспользоваться скриптом ](https://github.com/rollun-com/rollun-installer/blob/master/docs/InstallerSelfCall.md)
Он позволит запускать конкретный инталлер без запуска composer.
