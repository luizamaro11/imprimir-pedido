<?php

require 'vendor/autoload.php';
use Google\Client;
use Google\Service\Drive;

function initializeGoogleClient() {
    $client = new Client();
    $client->setAuthConfig(__DIR__ . "/credentials.json");
    $client->addScope(Drive::DRIVE_READONLY);
    $client->setAccessType("offline");
    // $client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/impressaoPedido/script.php');
    // $client->setApprovalPrompt("force");

    // Verificar se há um token de acesso salvo
    $tokenPath = __DIR__ . '/token.json';

    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // Se o token expirou, obtenha um novo token de atualização
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $newAccessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $client->setAccessToken($newAccessToken);
            // Salvar o novo token de acesso em um arquivo
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        } else {
            // Solicitar um novo token de autorização
            $authUrl = $client->createAuthUrl();
            // header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));

            printf("Abra o seguinte link no seu Navegador:\n%s\n", $authUrl);
            print 'Digite o código de verificação: ';
            $authCode = trim(fgets((fopen('php://stdin', 'r'))));
            // $authCode = $_GET['code'];

            // Trocar o código de verificação por um token de acesso
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Salvar o token de acesso em um arquivo
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
    }

    return $client;
}

function downloadFile($service, $fileId, $filePath) {
    $response = $service->files->get($fileId, array(
        'alt' => 'media'));
    file_put_contents($filePath, $response->getBody()->getContents());
}

function printFile($filePath) {
    $printerName = "PDF24"; // Substitua pelo nome da sua impressora compartilhada
    $command = "print /D:\\\\localhost\\" . escapeshellarg($printerName) . " " . escapeshellarg($filePath);
    exec($command);
}

// $count = 0;

// while (true) {
    
    $client = initializeGoogleClient();
    $service = new Drive($client);
    $folderId = '1TY9qXrodIoVBgHFXdN1puUCLjby2B1bK'; // ID da pasta do Google Drive
    $localPath = 'C:\\xampp\\htdocs\\impressaoPedido\\pedidos'; // Caminho para a pasta local
    
    try {
    
        $results = $service->files->listFiles(array(
            'q' => "'$folderId' in parents and mimeType='application/pdf'",
            'spaces' => 'drive',
            'fields' => 'files(id, name, createdTime)',
            'orderBy' => 'createdTime asc',
            // 'pageSize' => 1
        ));
    
        $arquivosDrive = [];
        $arquivosPastaPedidos = [];
    
        foreach ($results->files as $file) {
            $filePath = $localPath . '\\' . $file->name;
            $arquivosDrive[] = $file->name;
        }
    
        $path = __DIR__ . "/pedidos/";
        $diretorio = dir($path);
        while ($arquivo = $diretorio->read()) {
            if (!in_array($arquivo, array('.', '..'))) {
                $arquivosPastaPedidos[] = $arquivo;
            }
        }
        $diretorio->close();
    
        $novosPedidos = array_diff($arquivosDrive, $arquivosPastaPedidos);
    
        foreach ($novosPedidos as $pedido) {
            $filePath = $localPath . '\\' . $pedido;
    
            downloadFile($service, $file->id, $filePath);
            printFile($filePath);
        }
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }

    // echo "script executado" . $count++;
    // sleep(30);
// }
