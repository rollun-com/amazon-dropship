## Настройка окружения.

> Запускать все команды нужно в корневой дериктории проекта, если инного не указано в инструкции.

В данном туториале мы рассмотрим два примера:

1) Получение нотификации от крона([cron](https://en.wikipedia.org/wiki/Cron)) и ее обработка.

2) Обработка прищедшего interrupt запроса.

И так, для начала что бы мы могли запустить наши примеры вы должны в консоли выполнить

1) `composer update` - что бы установить/обновить все зависимости.

2) `composer lib uninstall` - что бы удалить ранее сгенерированые конфиги и предустановки если такие существуют.

> Так же важно указать в файле `config/env_config.php` параметр `HOST` с тем хостом по которому вы будете обращаться в приложение.

3) `composer lib install` - что бы установить и сконфигурировать наше приложение.

Когда установщик предоставит вам выбор компонентов, нужно выбрать три компонента  

* rollun\logger\Installer  
* rollun\callback\Middleware\MiddlewareInterruptorInstaller  
* rollun\callback\Callback\Interruptor\Script\ProcessInstaller
* rollun\callback\CronInstaller  
* rollun\skeleton\HelloWorldInstaller
* rollun\datastore\DataStoreTestInstaller

  

> На вопрос `Install cron multiplexer with Examples ? (Yes/No)` нужно ответиь Yes для создание тестовых примеров.

4) Удостовертесь что созданы следующее конфиг файлы.

* `rollun.actionrender.ActionRender.dist.local.php`
* `rollun.actionrender.BasicRender.dist.local.php`
* `rollun.actionrender.LazyLoadPipe.dist.local.php`
* `rollun.actionrender.MiddlewarePipe.dist.local.php`
* `rollun.callback.Cron.dist.local.php`
* `rollun.callback.MiddlewareInterruptor.dist.local.php`
* `rollun.logger.Logger.dist.local.php`
* `rollun.promise.Entity.dist.local.php`
* `rollun.promise.Promise.dist.local.php`

5)

### Запуск php built-in сервера 
Данный пункт можно пропустить если у вас уже запущен web сервер.
Теперь вам нужно запустить приложение на сервере, для этого вам достаточно запустить в консоли.
В дальнейшем по тексту будем считать что используемый сервер висит на 'localhost:8080'

`composer server` 

Или

`php -S localhost:8080 -t public/`

6) 

### Запуск тестов

Теперь нужно выполнить последний пункт - проверку что все установленно корректно.
Для этого запустите тесты, сделать это можно выполнив команду из консоли.

`APP_ENV=dev composer test`

Если тесты прошли успешно - приложение сконфигурировано правильно. В ином случае попробуйте провести всю процедуру заново.
