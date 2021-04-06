# financybot
This bot can help with planning your budget.
You should just type your budget and then add some costs. All this data will be saved to database (PGSQL), so you can easily get all needed information about your costs, budget and remainder.
____
Here you should add your own token:
```php
const TOKEN = 'TOKEN';
```
and your own connection string:
```php
$link = pg_connect("CONNECT");
```
Then you have to use your own database with all information. 
