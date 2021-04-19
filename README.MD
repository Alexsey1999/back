# Requirements

1. PHP 7.4
2. MongoDB
2. MongoDB php library
3. php7.4-ext-dom ```sudo apt-get install php-xml```
4. php7.4-gd  ```sudo apt-get install php5-gd```

# Развернуть проект @TODO - обновить
1. Установить зависимости ```composer install```
2. Клонировать репозитории с конфигурациями модулей в соответствующие директории
    - ```git clone <SenlerConfigTest_repo> ./config_env```
    - ```git clone <SenlerConfigMain_repo> ./config_main```
3. Запусить локальный сервер средствами php 
    - ```php -S localhost:8000 -t public```
    - Либо развернуть локально и направить в директорию ```public```

# Окружения
1. При развертывании локально или запуске тестов осуществялется подключение к ТЕСТОВОЙ базе данных
2. При развертывании на проде - подключение к общей MongoDB сервера
3. Конфигарация приложения при каждом запросе настраивается в `bootstrap/app.php`, в зависимости от параметров подписи запроса, а именно id вк приложения. Настраиваются следующие параметры:
    - Окружение
    - id вк приложения
    - Сервисный ключ доступа к АПИ
    - Секретный ключ для проверки подписи
4. Конфигарацию можно выбрать вручную, передав в конструктор AppConfig - id нужного вк приложения

# Архитектура приложения
1. Проект реализован с помощью микрофреймворка Lumen (Laravel)
2. Активно используется контейнер внедрения зависимостей. Основая идея заключается в том, чтобы к контролерах и класса ничего не дергать просто так из вне, а получаться все зависимости в конструкторе - для меньшей связанности и легкой подменяемости модулей
3. Так же внедрение зависимостей способствует сокращению использования статических классов и методов. Каждый сервис, каждая сущность даолжны быть использованы как полноценный объект, имеющий внутренне состояние. 
4. Зависимости настраиваются в файле `bootstrap/app.php` при инициализации приложения
5. Зависимости строятся строго на интерфейсах 

# Запуск тестов
1. В директории проекта выполнить команду `php vendor/bin/phpunit`
2. Чтобы запустить отдельный тест-кейс используем команду - `vendor/bin/phpunit --group [groupName]`, в которой [groupName] находится в блоке комментариев после @group в каждом тесткейсе

# Mongo DB
1. Установить и настроить Mongo DB
2. Создать файл-конфигурации 
    - */etc/mongod.conf*
    - Конфигурация 
    ```# mongodb.conf

        # Where to store the data.
        dbpath=/var/lib/mongodb

        #where to log
        logpath=/var/log/mongodb/mongodb.log

        logappend=true

        bind_ip = 127.0.0.1
        #port = 27017

        # Enable journaling, http://www.mongodb.org/display/DOCS/Journaling
        journal=true

        # Enables periodic logging of CPU utilization and I/O wait
        #cpu = true

        # Turn on/off security.  Off is currently the default
        #noauth = true
        #auth = true

        # Verbose logging output.
        #verbose = true

        # Inspect all client data for validity on receipt (useful for
        # developing drivers)
        #objcheck = true

        # Enable db quota management
        #quota = true

        # Set diagnostic logging level where n is
        #   0=off (default)
        #   1=W
        #   2=R
        #   3=both
        #   7=W+some reads
        #diaglog = 0

        # Diagnostic/debugging option
        #nocursors = true

        # Ignore query hints
        #nohints = true

        # Disable the HTTP interface (Defaults to localhost:27018).
        #nohttpinterface = true

        # Turns off server-side scripting.  This will result in greatly limited
        # functionality
        #noscripting = true

        # Turns off table scans.  Any query that would do a table scan fails.
        #notablescan = true

        # Disable data file preallocation.
        #noprealloc = true

        # Specify .ns file size for new databases.
        # nssize = <size>

        # Accout token for Mongo monitoring server.
        #mms-token = <token>

        # Server name for Mongo monitoring server.
        #mms-name = <server-name>

        # Ping interval for Mongo monitoring server.
        #mms-interval = <seconds>

        # Replication Options

        # in replicated mongo databases, specify here whether this is a slave or master
        #slave = true
        #source = master.example.com
        # Slave only: specify a single database to replicate
        #only = master.example.com
        # or
        #master = true
        #source = slave.example.com

        # Address of a server to pair with.
        #pairwith = <server:port>
        # Address of arbiter server.
        #arbiter = <server:port>
        # Automatically resync if slave data is stale
        #autoresync
        # Custom size for replication operation log.
        #oplogSize = <MB>
        # Size limit for in-memory storage of op ids.
        #opIdMem = <bytes>

        # SSL options
        # Enable SSL on normal ports
        #sslOnNormalPorts = true
        # SSL Key file and password
        #sslPEMKeyFile = /etc/ssl/mongodb.pem
        #sslPEMKeyPassword = pass
    ```

# Методы API

Некоторые методы устарели. Нужно обновить. Но на это как всегде нет времени.

### 1. Создание виджета
- Route 
```
POST /create
```

- Form data
```
type: <Тип виджета внтури VK (text|tiles|list|cover_list|compact_list)  и тд>
type_api: <Тип виджета внутри приложения (carousel|message|personal|banners|feedbacks) и тд>
name: <Название виджета>
group_id: <id сообщества VK>
```

- Успешный ответ
```
{
    "result": "success",
    "response":{
        "id": "5de4f63707df5746df529ac2"
    },
    "errors":[]
}
```

- Ответ с ошибкой
```
{
    "result": "error",
    "response": [],
    "errors": [errors list]
}
```

---

### 2. Редактирование виджета
- Route 
```
POST /update/<widget_id>
```
- Form data
```
code: <Объект тела виджета в формате JSON - тело отличается в зависимости от типа>,
audience: <Объект аудитории виджета в формате JSON>
```

- Успешный ответ
```
{
    "result":"success",
    "response": <Вся информация о виджете включая code и audience>,
    "errors":[]
}
```

- Ответ с ошибкой
```
{
    "result": "error",
    "response": [],
    "errors": [errors list]
}
```
---

### 3. Получение виджетов для сообщества
- Route 
```
GET /get-all/<group_id>
```

- Form data
```Нет```

- Успешный ответ
```
{
    "result": "success",
    "response": [widgets list],
    "errors": []
}
```

- Ответ с ошибкой
```
{
    "result": "error",
    "response": [],
    "errors": [errors list]
}
```
---

### 4. Удаление виджета
- Route 
```
POST /delete
```

- Form data
```
id: <widget_id>
```

- Успешный ответ
```
{
    "result": "success",
    "response": true,
    "errors": []
}
```

- Ответ с ошибкой
```
{
    "result": "error",
    "response": [],
    "errors": [errors list]
}
```
---

### 5. Переключение статуса виджета
- Route 
```
POST /change-status
```

- Form data
```
id: <String> widget_id
status: <Integer> (0|1)
```

- Успешный ответ
```
{
    "result": "success",
    "response": true,
    "errors": []
}
```

- Ответ с ошибкой
```
{
    "result": "error",
    "response": [],
    "errors": [errors list]
}
```

---

### 6. Сортировка виджета
- Route 
```
POST /sort
```

- Form data
```
Массив виджетов в формате JSON с полями 
id => <String>
sort => <Integer> 

widgets: [
    {"id":"5de10fbcc2191a315b0637f2","sort":1},
    {"id":"5de0fa582e5f8e0bf235c13c","sort":2},
    {"id":"5de111afc2191a315b0637f3","sort":3}
]
```

- Успешный ответ
```
{
    "result": "success",
    "response": true,
    "errors": []
}
```

- Ответ с ошибкой
```
{
    "result": "error",
    "response": [],
    "errors": [errors list]
}
```

---

### 7. Загрузка изображения

- Route 
```
POST /image
```

- Form data
```
image: <binary>,
image_type: <String> (160x160|160x240|50x50) - формат изображения
token: ключ доступа сообщества
```

- Успешный ответ
```
{
    "result":"success",
    "response":{
        "id": "174284183_400235",
        "type":"160x160",
        "images":[
            {"url":"https://sun9-55.userapi.com/c855236/v855236967/18b600/hcy_tEYnsew.jpg","width":160,"height":160},
            {"url":"https://sun9-62.userapi.com/c855236/v855236967/18b601/mGcTtPZKm80.jpg","width":320,"height":320},
            {"url":"https://sun9-21.userapi.com/c855236/v855236967/18b602/9_vqiF7oSNM.jpg","width":480,"height":480}
        ]
    },
    "errors":[]
}
```

- Ответ с ошибкой
```
{
    "result": "error",
    "response": [],
    "errors": [errors list]
}
```

---

### 8. Загрузка изображения из документа VK

- Route 
```
POST /document
```

- Form data
```
url: <String> https://vk.com/<document_url>,
ext: <String> (jpeg|png|gif),
image_type: <String> (160x160|160x240|50x50) - формат изображения
token: ключ доступа сообщества
```

- Успешный ответ
```
{
    "result":"success",
    "response":{
        "id": "174284183_400235",
        "type":"160x160",
        "images":[
            {"url":"https://sun9-55.userapi.com/c855236/v855236967/18b600/hcy_tEYnsew.jpg","width":160,"height":160},
            {"url":"https://sun9-62.userapi.com/c855236/v855236967/18b601/mGcTtPZKm80.jpg","width":320,"height":320},
            {"url":"https://sun9-21.userapi.com/c855236/v855236967/18b602/9_vqiF7oSNM.jpg","width":480,"height":480}
        ]
    },
    "errors":[]
}
```

- Ответ с ошибкой
```
{
    "result": "error",
    "response": [],
    "errors": [errors list]
}
```

### 9. Копировать виджет в другое сообщество
- Route 
```
POST /copy/<widget_id>
```

- Form data
```
group_id: id сообщества ВК
```

- Успешный ответ
```
{
    "result": "success",
    "response": {
        "id": "5dee4684555d9151041bbcd8"
    },
    "errors": []
}
```

- Ответ с ошибкой
```
{
    "result": "error",
    "response": [],
    "errors": [errors list]
}