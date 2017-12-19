# Тонкости Megaplan API или Перевод документации по Megaplan API на понятный язык.

## Общие моменты
Собственное, речь пойдет не обо всем API, а только о той части, которая касается сделок.

Оригинал документации по сделкам [тут](https://dev.megaplan.ru/api/API_deals.html).

Все ответы API возвращает в виде JSON строки. Но иногда может вернуть и в виде объекта stdClass )). Причин не знаю, системы
не обнаружил. Просто вот в какой-то прекрасный момент вместо JSON приходит объект. Возможно, это происходит,
когда они там у себя что-то меняют/делают.
Именно поэтому в коде нашей библиотеки стоит проверка, что именно пришло. И если это таки JSON,
то он разворачивается в ассоциативный массив.

Обычно ответ выглядит так:
```
[
    [status] => [
            [code] => ok
            [message] =>
        ]

    [data] => [
            [deals] => [
                    [0] => [...],
                    [1] => [...],
                    // ...
                    [n] => [...],
                ],
        ]
]
```

Если в запросе есть ошибка, или в момент его выполнения что-то пошло не так, то код статуса будет "error",
а в поле "message" будет содержаться сообщение об ошибке:

```
[
    [status] => [
            [code] => error
            [message] => SQLSTATE[22P02]: Invalid text representation: 7 ERROR:  invalid input syntax for integer: "fdfsahjjs"
СТРОКА 1: ...tive) AND (logic_program_instance__3.program_id = 'fdfsahjjs...
                                                               ^
        ]

]
```

К громадному сожалению, в подавляющем большинстве случаев ошибки, которые возвращает сервер, НЕинформативны.
Часто приходится догадываться, а еще чаще - писать в поддержку и выяснять, что именно не понравилось серверу.

При работе с API нужно также учитывать лимиты.

Во-первых, это лимит по частоте/количеству запросов. На момент написания
этой документации наш тарифный план позволял **делать 3000 запросов в час, НО не чаще 3 запросов в секунду**.
К сожалению, следить за этим из кода слегка проблематично, потому что в наших задачах часто используются параллельные
процессы. В одном процессе подсчитать лимиты не сложно, а вот когда работают несколько процессов сразу, то можно легко
выйти за пределы ограничения. По всей видимости, в будущем все эти запросы будут отправляться в очередь, которая и будет
следить за лимитами.

Второе ограничение - это количество сделок за одну выборку. Мы можем **за один запрос получить не более 100 записей**.
Т.е. если, скажем, Вы пытаетесь выбрать все сделки, а их там, например, 2000, то Вам придется сделать 20 последовательных
запросов. Каждый запрос разворачивать из JSON (при этом проверять, а вдруг пришел уже развернутый ответ?),
этот ответ собирать в результирующий массив. Перед каждым новым запросом устанавливать смещение выборки и так далее.
Все это реализовано в коде. Поэтому выборка производится простым вызовом метода **get()** у соответствующего класса
сущности в нашей библиотеке.
У меня нет ни одной мысли, почему они ввели этот лимит. Но он есть, и с ним надо считаться.


## Сериализатор ответов
Выходит, что для обработки одного ответа нужно каждый раз выполнять сразу несколько действий:
- проверить, что пришло - stdClass или JSON; если это объект, то надо завернуть его в JSON;
- развернуть в ассоциативный массив;
- проверить статус ответа; если это ошибка, то выборосить исключение с соответствующим сообщением;
- если все в порядке, то выделить из ответа только данные и вернуть их; сами данные запакованы во вложенный массив с
ключом, который соответствует названию сущности: deals, deal, fields и тд.

Поэтому, чтобы каждый раз не плодить одно и то же, мы прибегли к услугам ZendSerializer. Нас интересует непосредственно
\Zend\Serializer\Adapter\Json, ибо работаем с JSON.

```
public function unserialize($serialized)
{
    // Data may come already in stdClass view
    if (!(is_string($serialized) && (is_object(json_decode($serialized)) || is_array(json_decode($serialized))))) {
        // So encode them again
        $serialized = parent::serialize($serialized);
    }
    // Now decode data with $assoc = true
    $unserializedData = parent::unserialize($serialized);

    /**
     * API returns not number of error. Instead "error" or "ok"
     */
    if ('error' == $unserializedData['status']['code']) {
        throw new RuntimeException($unserializedData['status']["message"]);
    }

    $rawUnserializedData = $unserializedData["data"][$this->options->getEntity()];
    return $rawUnserializedData;
}
```

Объект сериализатора передается внутрь любой библиотечной сущности, как критическая зависимость.

```
abstract class EntityAbstract
{
    // ...

    /**
     * Gets information from Megaplan.
     *
     * This is the main method for receive data from Megaplan.
     *
     * @return array
     */
    public function get()
    {
        $requestParams = $this->getRequestParams();
        $response = $this->megaplanClient->get(static::URI_ENTITY_GET, $requestParams);
        // Fetch data from response
        $data = $this->serializer->unserialize($response);
        return $data;
    }

    // ...
}
```


## Схемы сделок
Схема для сделок - это чуть ли не краеугольный камень для работы с этой сущностью вообще.
В документации это никак не акцентируется. Но стоит где-то упустить этот параметр и все - результаты будут очень
неожиданными или их не будет вообще.

```
// rollun.api.Api.Megaplan.Megaplan.dist.local.php
return
[
    // ...
    'megaplan_entities' => [
        'deals' => [
            'dealListFields' => 'dealListFields',
            'filterField' => [
                'Program' => 13,
            ],
            'requestedFields' => [],
            'extraFields' => [],
        ],
    ],
    // ...
]
```

В конфигурации, которую содержит библиотека и которую формирует инсталлер (локальная часть) есть обязательный
параметр **Program**. Вот это и есть id схемы, по которой эта компонента будет работать.

Почитать о том, как получить список всех имеющихся схем можно [тут](https://dev.megaplan.ru/api/API_deals.html#id12).
Одно из самых бесполезных действий. Дело в том, что сам по себе список сделок какой-либо новой информации не несет.
Обычно при работе со сделками уже заранее известно, по какой схеме требуется это делать. Разве что для каких-нибудь
визуальных компонентов типа выпадающего списка и тд.

Изменить схему посредством API нельзя. Только через web-интерфейс.


## Пользовательские параметры для сделок
Вот тут начинается веселуха. В каждой отдельно взятой схеме может быть неограниченное число пользовательских параметров.
**Но даже если они называются одинаково во всех схемах, по существу - это разные параметры!!!**

Получить список параметров схемы можно используя [соответствующий API](https://dev.megaplan.ru/api/API_deals.html#api-deal-available-fields-list).
Как видно из документации здесь параметр схемы уже называется по-другому, чем в остальных API-запросах - ProgramId.
И он тут - строго обязателен!

При выборке сделок (списком) или карточки сделки эти поля нужно добавлять в список расширенных (extra) параметров.
Но дело в том, что мы заранее не знаем, какие именно пользовательские поля существуют. Поэтому нужно запросить их список
и добавить поле ExtraFields запроса на выборку. Каждое кастомное поле будет обязательно содержать в названии **CustomField**.
('Category1000060CustomFieldOrderId', 'Category1000060CustomFieldTrekNomer')

Итак, получаем все поля схемы, выбираем среди них только кастомные, собираем их в массив ExtraFields, который потом отдаем в запрос на выборку:
```
class Deals extends ListEntityAbstract
{
    // ...

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    protected function getRequestParams()
    {
        $this->requestParams = array_merge($this->requestParams, [
            // ...
            'ExtraFields' => $this->getExtraFields(),
        ]);
        return $this->requestParams;
    }

    /**
     * Gets extra fields for the entity.
     *
     * Extra fields are custom fields. They contain 'CustomField' chunk in their names.
     * This method gets all the deal fields and then fetch the custom fields only.
     *
     * @return array
     */
    protected function getExtraFields()
    {
        if (!count($this->extraFields)) {
            $fields = $this->dealListFields->get();
            foreach ($fields as $field) {
                if (preg_match("/CustomField/", $field['Name'])) {
                    $this->extraFields[] = $field['Name'];
                }
            }
        }
        return $this->extraFields;
    }

    // ...
}
```

Выборка этих параметров будет нужна в получении списка сделок. **Если не указать кастомные (extra) поля, то API их не вернет.**

В конечном итоге полностью сформированный массив параметров для запроса может выглядеть примерно так (на примере схемы №13):
```
'FilterFields' => [
    'Program' => 13,
]
'RequestedFields' => [
]
'ExtraFields' => [
    'Category1000051CustomFieldDataZakupki',
    'Category1000051CustomFieldPostavshchik',
    'Category1000051CustomFieldShipmentId',
    'Category1000051CustomFieldStoimostUslugi',
    'Category1000060CustomFieldOrderId',
    'Category1000060CustomFieldTrekNomer',
    'Status',
],
'Limit' => 100,
'Offset' => 1000,
```


## Список сделок
Документация по соответствующему API [здесь](https://dev.megaplan.ru/api/API_deals.html#id8).

Важным тут является раздел FilterFields. Он позволяет произвести фильтрацию выборки прямо на сервере.

Несколько важных замечаний:
1. **Фильтровать список сделок можно по всем полям, КРОМЕ ID.** Почему? Спросите у саппорта Megaplan'а. Т.е. в списке
НЕЛЬЗЯ получить конкретную сделку. Для этого нужно использовать API карточки сделки (см. ниже). Более того, если
Вы по ошибке или специально передадите в фильтр поле Id, то запрос вернет исключение.
2. Если в фильтрации указать кастомное поле, то если оно есть в схеме сделки, то будет автоматически включено в результаты,
даже если его не указать в ExtraFields.
3. Соответственно, если кастомное поле включено в фильтры, а его нет в схеме, то ошибки не будет. Но API попытается
провести выборку по этому полю. А поскольку его нет, то и результата не будет (по всей видимости там используется InnerJoin
с таблицей кастомных полей)
4. Если выборка производится по нескольким схемам, то кастомные поля и их значения будут подставляться при наличии
этих полей в схеме. Поэтому если Вы хотите выбрать все схемы с их кастомными полями, то нужно собрать их все в ExtraFields.
5. Фильтр можно производить только лишь по верхнему уровню списка результатов.
6. Ограничение по выборке только по верхнему списку, например, не позволит выбрать все сделки, в которых был продан
какой-то конкретный товар. Возможно, разработчики в будущем что-то изменят, но, как мне ответили в поддержке, пока
этот вопрос даже не обсуждается.

Правила фильтрации будут описаны  ниже.


## Карточка сделки
Документация по соответствующему API [здесь](https://dev.megaplan.ru/api/API_deals.html#api-deals-card).

Позволяет получить (и только) данные конкретной сделки.

Вот тут можно и нужно фильтровать по полю Id. Два других параметра - это наборы полей, которые нужно показать.
Так же, как и в списке сделок, если не указать экстра поля, то значений по ним не будет.


## Создание/редактирование сделки
Документация по соответствующему API [здесь](https://dev.megaplan.ru/api/API_deals.html#api-deals-save).

Самый сложный для понимания раздел. Я, собственно, даже не до конца в нем разобрался. Поточму что документация
сухая и скудная. А реальных примеров не было столько, чтобы исследовать все возможности.

Первое, что нужно знать и понимать - это то, что все, что не относится непосредственно к сделке, должно обертываться
в ключ массива Model. Технические же параметры самой сделки передаются на одном уровне с ключом Model.

Статус обычно не передается. Эта возможность задокументирована, но я ее не использовал. Мне было достаточно того, что
схема сама переключала статус по триггерам при передаче набора данных.

ProgramId - обязательный параметр.

С позициями мы не работали (пока). Поэтому реальных примеров нет.

Сама передача параметров для создания/редактирования сделки инкапсулирована в аспект-класс \rollun\amazonDropship\Megaplan\Aspect\Deal.
Просто передаете набор параметров (полей со значениями) в методы Create/Update, и класс сам преобразует их в нужную форму.

1.
```
// Создаем сделку в базовом статусе
$itemData = [
    'Category1000060CustomFieldOrderId' => '112-4729639-1898621',
    'Category1000060CustomFieldDataZakaza' => '2017-12-01',
];

$megaplanAspect = $container->get(megaplanDataStoreAspect);
$result = $megaplanAspect->create($itemData);
```

2.
```
// Редактируем сделку и переводим ее в статус "Ожидает отправку"
$itemData = [
    'Category1000060CustomFieldOrderId' => '112-4729639-1898621',
    'Category1000060CustomFieldNomerZakazaUPostavshchika' => '11559601',
];

$megaplanAspect = $container->get(megaplanDataStoreAspect);
$result = $megaplanAspect->update($itemData);
```

3.
```
// Редактируем сделку и переводим ее в статус "Отправлено"
$itemData = [
    'Category1000060CustomFieldOrderId' => '112-4729639-1898621',
    'Category1000060CustomFieldTrekNomer' => '414676071273',
];

$megaplanAspect = $container->get(megaplanDataStoreAspect);
$result = $megaplanAspect->update($itemData);
```

Или:
```
// Создаем сделку и переводим ее сразу по всем статусам до статуса "Отправлено"
$itemData = [
    'Category1000060CustomFieldOrderId' => '112-4729639-1898621',
    'Category1000060CustomFieldDataZakaza' => '2017-12-01',
    'Category1000060CustomFieldNomerZakazaUPostavshchika' => '11559601',
    'Category1000060CustomFieldTrekNomer' => '414676071273',
];

$megaplanAspect = $container->get(megaplanDataStoreAspect);
$result = $megaplanAspect->create($itemData);
```

Метод preCreate этого класса проверяет, передан ли Id в наборе данных. Если передан, он ищет сделку с этим Id.
Далее, в зависимости от вспомагательных флагов $rewriteIfExist / $createIfAbsent производится решение, что делать,
если сделка (не)найдена.

И если таки принято решение записывать данные, они преобразуются в необходимую, понятную Megaplan'у, структуру.

Посмотреть код можно [тут](https://github.com/rollun-com/amazon-dropship/blob/master/src/AmazonOrder/src/Megaplan/Aspect/Deal.php#L43)


## Правила фильтрации

Ниже показаны правила фильтрации самого Megaplan API и примеры использования.

### Фильтрация с указанием конкретного значения
Например, есть такая сделка:
```
[
    [Id] => 39
    [GUID] =>
    [Name] => №1
    [Description] =>
    [Contractor] =>
    [TimeCreated] => 2017-03-14 19:08:39
    [TimeUpdated] => 2017-04-27 15:12:16
    [Owner] => [
            [Id] => 1000002
            [Name] => Marina D
        ]

    [IsDraft] =>
    [Positions] => [
            [0] => [/* Тут какие-то поля проданных товаров*/]
            [1] => [/* Тут какие-то поля проданных товаров*/]
            // ...
            [N] => [/* Тут какие-то поля проданных товаров*/]
        ],
    [IsPaid] =>
    [FinalPrice] => [
            [Value] => 0
            [Currency] => $
            [CurrencyId] => 2
            [CurrencyAbbreviation] => USD
            [Rate] => 1
        ]

    [Program] => [
            [Id] => 6
            [Name] => Закупка
        ]

    [Category1000051CustomFieldDataZakupki] => 2017-02-15
    [Category1000051CustomFieldPostavshchik] => Rocky Mountain
    [Category1000051CustomFieldShipmentId] => FBA4G24B5Q
    [Category1000051CustomFieldStoimostUslugi] => [
            [Value] => 42.4
            [Currency] => $
            [CurrencyId] => 2
            [CurrencyAbbreviation] => USD
            [Rate] => 1
        ]

    [Auditors] => []

    [Manager] => [
            [Id] => 1000002
            [Name] => Marina D
        ]

    [Status] => [
            [Id] => 36
            [Name] => Sold out
        ]

]
```
Так вот фильтрацию можно производить только по верхнему уровню, т.е. по полям:
- GUID,
- Name,
и тд.

и нельзя по:
- Id;
- Positions (формально нельзя; см. ниже).

Поля, которые имеют в своем составе поле Id, например: Manager, Status, Program можно фильтровать, просто указав значение
этого Id:
```
// Выберет все сделки менеджера с Id 1000002
'FilterFields' => [
    // ...
    'Manager' => 1000002,
]
```
или:
```
// Выберет все сделки в статусе 36
'FilterFields' => [
    // ...
    'Status' => 36,
]
```

В принципе, по полю Positions тоже можно производить выборку, потому что оно содержит поле Id. Но это НЕ Id товара.
А Id строки базы данных, под которым оно записано. Т.е. одна и та же товарная позиция в разных сделках будет
иметь разный Id. Поэтому выборка по этому полю бессмысленна.

Единственная группа полей, которое выпадает из приведенного выше описания - это група "ценовых" полей, таких как:
FinalPrice, Category1000051CustomFieldStoimostUslugi. Их там может быть несколько видов. Всех их отличает одна особенность.
По ним можно фильтровать, просто указав значение цены:
```
// Выберет все сделки, у которых финальная цена равна 100 единицам базовой валюты; при этом саму валюту уточнить нельзя
'FilterFields' => [
    // ...
    'FinalPrice' => 100,
]
```

### Фильтрация с указанием диапазона значений
Диапазон значений имеется в виду **less, lessOrEqual, greaterOrEqual, greater**. Здесь же, в этой же группе, есть
и проверка на равенство значения: **equal**.

К счастью, API дает такую возможность. Тут все просто:
```
// Выберет все сделки, у которых финальная цена больше 100 единицам базовой валюты; при этом саму валюту уточнить нельзя
'FilterFields' => [
    // ...
    'FinalPrice' => [
        'greater' => 100,
    ],
]
```

или:
```
// Выберет все сделки, которые были обновлены после начала суток 1-го сентября
'FilterFields' => [
    // ...
    'TimeUpdated' => [
        'greaterOrEqual' => '2017-09-01 00:00:00',
    ],
]
```

По такому же принципу можно использовать остальные операторы.


### Логическая фильтрация
Имеется в виду объединения фильтров по логике **And, Or, Not**.

Примеры:
```
// Выберет все сделки, которые были отредактированы, начиная с 1-го сентября и в схеме №13
'FilterFields' => [
    // ...
    ['and' => [
            ['TimeUpdated' => [
                'greaterOrEqual' => '2017-09-01 00:00:00',
            ],],
            ['Program' => 13,],
        ],
    ],
]
```

```
// Выберет все сделки, где поставщик НЕ 'Rocky Mountain'
'FilterFields' => [
    // ...
    ['not' => [
            ['Category1000051CustomFieldPostavshchik' => 'Rocky Mountain',],
        ],
    ],
]
```

```
// Выберет все сделки по схемам 6 и 13
'FilterFields' => [
    // ...
    ['or' => [
            ['Program' => 6,],
            ['Program' => 13,],
        ],
    ],
]
```

### Ну и, наконец, комбинируем

```
// Выберет все созданные сделки по схеме №6 за сентябрь месяц, где поставщик НЕ равен 'Rocky Mountain'
'FilterFields' => [
    ['and' => [
            ['or' => [
                    ['TimeCreated' => [
                        'greaterOrEqual' => '2017-09-01 00:00:00',
                    ],],
                    ['TimeCreated' => [
                        'less' => '2017-10-01 00:00:00',
                    ],],
                ],
            ],
            ['Program' => 6,],
            ['not' => [
                    ['Category1000051CustomFieldPostavshchik' => 'Rocky Mountain',],
                ],
            ],
        ],
    ],
]
```

### Как отфильтровать это все в контексте Rql
Страшно? Мне тоже. Поэтому, чтобы избавить Вас от таких монстров был разработан специальный класс ConditionBuilder,
объект которого принимает Rql-запрос и сам строит параметры в нужном порядке и виде.

Собственно, Вам ничего не нужно делать. Этот построитель инкапсулирован в специальный MegaplanDataStore.
Все, что нужно, - это создать объект этого класса из контейнера и использовать метод \rollun\api\Api\Megaplan\DataStore\MegaplanDataStore::query

```
use Xiag\Rql\Parser\Node\Query\LogicOperator;
use Xiag\Rql\Parser\Node\Query\ScalarOperator;

$query = new \Xiag\Rql\Parser\Query();
$andNode = new LogicOperator\AndNode([
    new LogicOperator\OrNode([
        new ScalarOperator\GeNode('TimeCreated', '2017-09-01 00:00:00'),
        new ScalarOperator\LtNode('TimeCreated', '2017-10-01 00:00:00'),
    ]),
    new ScalarOperator\EqNode('Program', 6),
    new LogicOperator\NotNode([
        new ScalarOperator\EqNode('Category1000051CustomFieldPostavshchik', 'Rocky Mountain'),
    ]),
]);
$query->setQuery($andNode);

$megaplanDataStore = $container->get('megaplanDataStore');
$result = $megaplanDataStore->query($query);
var_dump($result);
```

Вместо набора запроса путем создания объектов можно распарсить его из Rql-выражения
```
use \Xiag\Rql\Parser\Parser;

$rqlExpression = "and(or(ge('TimeCreated','2017-09-01 00:00:00'),lt('TimeCreated','2017-10-01 00:00:00')),eq('Program',6),not(eq('Category1000051CustomFieldPostavshchik','Rocky Mountain')))";
$parser = new Parser();
$query = $parser->parse($rqlExpression);

$megaplanDataStore = $container->get('megaplanDataStore');
$result = $megaplanDataStore->query($query);
var_dump($result);
```