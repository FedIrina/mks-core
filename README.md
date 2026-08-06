# MKS Core

Задуман, как плагин для выноса функционала из темы сайта интернет-магазина. Сейчас пока — read-only REST API остатков WooCommerce для внешних партнёров. Разработан согласно пожеланию клиента.

**Версия:** 0.1.0  
**Требования:** WordPress 5.9+, PHP 7.4+, активный [WooCommerce](https://woocommerce.com/)

## Возможности

- REST endpoint остатков (только `GET`)
- Роль **МКС API Partner** (`mks_api_partner`) и capability `read_mks_api` (также у администратора)
- Авторизация через ключи WooCommerce REST API (Read)
- Настройки: URL endpoint’а, пагинация по умолчанию, атрибут и термин фильтра

## Установка

1. Скопируйте плагин в `wp-content/plugins/mks-core`
2. Активируйте WooCommerce, затем MKS Core
3. **Настройки → MKS Core** — укажите атрибут и значение термина (без этого API вернёт 503)
4. Создайте пользователя с ролью партнёра (или используйте админа) и ключ WC REST API с правами Read

## Документация API

Подробности endpoint’а, полей ответа и авторизации: [docs/API-STOCK.md](docs/API-STOCK.md)

## Удаление

При удалении плагина снимаются роль партнёра, capability `read_mks_api` у администратора и option настроек. Ключи WooCommerce REST API в БД не трогаются.
