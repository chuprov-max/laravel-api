## Laravel API для озвучки текста через Yandex SpeechKit API

### Было сделано:
- Создано API приложение на фреймворке Laravel (v7.13.0) со следующими точками входа:
   - `GET /article`: Получение списка статей
   - `GET /article/{ID}`: Получение статьи по `{ID}`
   - `POST /article`: Отправка статьи для преобразования текста в звук
   - `PUT /article/{ID}`: Обновление информации о статье по `{ID}`   
   - `DELETE /article/{ID}`: Удаление статьи с `{ID}` и звука к ней 
- В корне репозитория добавлен файл [4fresh Laravel API.postman_collection.json](4fresh Laravel API.postman_collection.json) для импорта в Postman и проверки точек входа API.
- Реализован механизм очередей при помощи базы данных https://laravel.com/docs/7.x/queues . После того как статья добавлена, она помещается в очередь на преобразование в звук.
- Синтезирование звуковых файлов производится с помощью Yandex SpeechKit API
- Полученные звуковые файлы хранятся в Yandex Object Storage: https://cloud.yandex.ru/docs/storage/quickstart
- API приложение хостится на Heroku: https://immense-refuge-94796.herokuapp.com/ Сюда же настроен автоматический деплой при коммите в `master` ветку данного репозитория.
- Написаны тесты (директория **tests**):
    - Unit тест, который тестирует, что статья успешно сохраняется в базу данных
    - функциональные тесты, которые тестируют точки входа `POST /article` и `GET /article`, `GET /article/{ID}`
- При получении информации о статье с успешно синтезированным звуком через API в атрибуте `speechUrl` передается ссылка, по которой можно прослушать звук (например, http://synthesizetext.storage.yandexcloud.net/synthesize/7.ogg)
- Логируются этапы обработки созданных статей от момента записи в базу данных до получение звука или ошибки. Для этой цели были созданы следющие [статусы](#article-statuses) (записываются в таблицу `articles`.`status`).

### Как это работает? Как проверить?
1. Импортировать файл `4fresh Laravel API.postman_collection.json` в Postman
2. Загрузить статью на обработку через Postman: точка входа "Добавить статью"
3. Если статья успешно загружена, то она добавляется в очередь на отправку для преобразования в звуковой файл.
4. Как только очередь дошла до данной статьи, то она отправляется в Yandex SpeechKit API и мы ждем ответа от сервера.
5. Если сервер возвращает файл, то мы его сохраняем в Yandex Object Storage. Статье ставится [статус](#article-statuses) = 4. Теперь API для данной статьи будет возвращать в том числе и ссылку на прослушивание данного звука.


### Специфические настройки и статусы (при самостоятельно развертывании)  
- [Переменные в .env файле](#env-variables).
- [.env.testing файл для тестирования](#env-testing).
- [Статусы обработки статьи](#article-statuses).

#### Env Variables
Помимо основных переменных, которые используются в Laravel, необходимо также добавить в `.env` следующие переменные:
 ```
YANDEX_OAUTH_TOKEN=
YANDEX_CLOUD_FOLDER_ID=

YANDEX_CLOUD_ACCESS_KEY_ID=
YANDEX_CLOUD_SECRET_ACCESS_KEY=
YANDEX_CLOUD_DEFAULT_REGION=us-east-1
YANDEX_CLOUD_BUCKET=

LOGGING_HEROKU_SINGLE=errorlog
```

Получить `YANDEX_OAUTH_TOKEN` можно по следующей ссылке: https://cloud.yandex.ru/docs/iam/operations/iam-token/create
Введите идентификатор каталога `YANDEX_CLOUD_FOLDER_ID`, в котором будут синтезироваться звуковые файлы на странице: https://console.cloud.yandex.ru/


Для хранения синтезированных звуковых файлов выбрано хранилище Yandex Object Storage: https://cloud.yandex.ru/docs/storage/quickstart
Необходимо создать бакет для хранения файлов и внести данные этого бакета в переменные:
```
YANDEX_CLOUD_ACCESS_KEY_ID=
YANDEX_CLOUD_SECRET_ACCESS_KEY=
YANDEX_CLOUD_DEFAULT_REGION=us-east-1
YANDEX_CLOUD_BUCKET=
```

Следующая переменная используется только на хостинге Heroku для просмотра лога через консоль:
```
LOGGING_HEROKU_SINGLE=errorlog
```

#### Env Testing
Для проведения теста используется друга база данных. Ее надо создать и прописать в файле `.env.testing` переменные для данной БД. Например:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_api_testing
DB_USERNAME=root
DB_PASSWORD=
```
Остальные значения переменных можно взять из `.env` файла


#### Article Statuses
- `STATUS_CREATED = 1;` => статья сохранена в БД
- `STATUS_QUEUED = 2;` => статья добавлена в очередь на отправку преобразования текста
- `STATUS_SYNTHESIZE_STARTED = 3;` => текст статьи отправлен на обработку преобразования текста
- `STATUS_SUCCESS_SYNTHESIZED = 4;` => текст успешно преобразован в звук и сохранен в Yandex Object Storage
- `STATUS_FAILED_SYNTHESIZED = 5;` => произолша ошибка при преобразовании текста в звук
