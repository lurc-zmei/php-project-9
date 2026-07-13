### Hexlet tests and linter status:
[![Actions Status](https://github.com/lurc-zmei/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/lurc-zmei/php-project-9/actions)


### URL CI tests
[![CI](https://github.com/lurc-zmei/php-project-9/actions/workflows/url.yml/badge.svg?branch=main)](https://github.com/lurc-zmei/php-project-9/actions/workflows/url.yml)


### SonarQube:
[![Quality gate](https://sonarcloud.io/api/project_badges/quality_gate?project=lurc-zmei_php-project-9)](https://sonarcloud.io/summary/new_code?id=lurc-zmei_php-project-9)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=lurc-zmei_php-project-9&metric=ncloc)](https://sonarcloud.io/summary/new_code?id=lurc-zmei_php-project-9)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=lurc-zmei_php-project-9&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=lurc-zmei_php-project-9)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=lurc-zmei_php-project-9&metric=sqale_index)](https://sonarcloud.io/summary/new_code?id=lurc-zmei_php-project-9)
[![Duplicated Lines (%)](https://sonarcloud.io/api/project_badges/measure?project=lurc-zmei_php-project-9&metric=duplicated_lines_density)](https://sonarcloud.io/summary/new_code?id=lurc-zmei_php-project-9)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=lurc-zmei_php-project-9&metric=code_smells)](https://sonarcloud.io/summary/new_code?id=lurc-zmei_php-project-9)

### Page Analyzer

Веб-приложение для анализа HTML-страниц
Оно позволяет извлекать и отображать базовую SEO-информацию.

[php-project-9-production-dd24.up.railway.app](https://php-project-9-production-dd24.up.railway.app/)

---

## Системные требования
Для запуска проекта вам потребуется:
* **Docker** и **Docker Compose**

---

## Установка и запуск

1. Клонируйте репозиторий:
   ```bash
   git clone https://github.com/lurc-zmei/php-project-9.git

2. Создайте файл `.env` на основе `.env.example`

3. Запустите приложение одним из способов:
- Через Makefile:
```bash
make docker-start
```
- Или напрямую через Docker Compose:
```bash
docker compose up
```

## Проверка кода и тестирование
В проекте настроены инструменты для статического анализа, линтинга и модульного тестирования. Все команды доступны через Makefile.
Все проверки: validate, lint, test:
```bash
make check	