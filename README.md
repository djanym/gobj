# gobj

## Основные принципы

### Безопасность

1. Пользовательские папки, папки дополнительных плагинов, тем, загружаемых файлов должны быть отдельны от папки с неизменяемыми файлами.
2. Папки, пути которых можно просмотреть в коде страницы должны иметь кастомное название.
3. Core include-folder should be defined in settings.php file as `INC_FOLDER` constant

### Расположение файлов

1. Папка админ панель должна быть отдельна. Ее название должно быть указано в конфиг файле.
2. У админ части свой settings.php файл, который выставляет отличные от фронтенд настройки.
