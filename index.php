<?php
    include_once("script.php");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impressão Pedidos</title>
</head>
<body>
    <div>
        <h1>Impressão dos pedidos</h1>

        <?php
            if ($client->getAccessToken()) {
                
            } else {
                $authUrl = $client->createAuthUrl();

                echo '<a href="' . $authUrl . '">Conectar Drive</a>';
            }
        ?>
    </div>
</body>
</html>