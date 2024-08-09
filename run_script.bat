@echo off
:loop
php C:\xampp/htdocs/impressaoPedido/script.php
timeout /t 30 /nobreak
goto loop