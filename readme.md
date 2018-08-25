scan-mang (แสกนแม่ง)
====================

Requirements
------------

- PHP `>= 7.1.3`
- composer
- OpenSSL PHP Extension
- PDO PHP Extension
- Mbstring PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension
- Ctype PHP Extension
- JSON PHP Extension

Installation
------------

01. Clone repository

    ```markdown
    $ git clone https://github.com/rayriffy/hacktech-backend
    Cloning into 'hacktech-backend'...
    remote: Counting objects: 322, done.
    remote: Compressing objects: 100% (183/183), done.
    Receiving objects: remote: Total 322 (delta 143), reused 280 (delta 112), pack-reused 0
    Receiving objects: 100% (322/322), 225.04 KiB | 268.00 KiB/s, done.
    Resolving deltas: 100% (143/143), done.
    ```

02. Install composer package

    ```markdown
    $ composer install
    Loading composer repositories with package information
    Installing dependencies (including require-dev) from lock file
    Package operations: 70 installs, 0 updates, 0 removals
    ...
    ```

Routes
------

| Domain | Method   | URI                       | Name                      | Action  | Middleware |
|--------|----------|---------------------------|---------------------------|---------|------------|
|        | GET/HEAD | /                         |                           | Closure | web        |
|        | POST     | api/register              | register                  | Closure | api        |
|        | POST     | api/transaction/bank      | transaction/bank          | Closure | api        |
|        | POST     | api/transaction/promptpay | transaction/promptpay     | Closure | api        |
|        | GET/HEAD | api/transaction/{id}      | transaction               | Closure | api        |
|        | GET/HEAD | api/transactions/{id}     | transactions              | Closure | api        |
|        | GET/HEAD | api/user/{id}             | user                      | Closure | api        |
|        | GET/HEAD | {fallbackPlaceholder}     |                           | Closure | web        |

Documentation
-------------

[Postman](https://documenter.getpostman.com/view/4813279/RWTspF6Y)