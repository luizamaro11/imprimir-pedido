<?php

require 'vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

function initializeGoogleClient() {
    $client = new Client();
    $client->setAuthConfig("credentials.json");
    $client->addScope(Drive::DRIVE_READONLY);
    $client->setAccessType("offline");
    // $client->setApprovalPrompt("force");

    // Verificar se há um token de acesso salvo
    $tokenPath = 'token.json';
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

            printf("Abra o seguinte link no seu Navegador:\n%s\n", $authUrl);
            print 'Digite o código de verificação: ';
            $authCode = trim(fgets((fopen('php://stdin', 'r'))));

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
    $command = "print /D:\\\\localhost\\" . $printerName . " " . escapeshellarg($filePath);
    exec($command);
}

$client = initializeGoogleClient();
$service = new Drive($client);
$folderId = '1TY9qXrodIoVBgHFXdN1puUCLjby2B1bK'; // ID da pasta do Google Drive
$localPath = 'C:\\xampp\\htdocs\\impressaoPedido\\pedidos'; // Caminho para a pasta local

$filesystem = new Filesystem();
$finder = new Finder();

try {

    $results = $service->files->listFiles(array(
        'q' => "'$folderId' in parents and mimeType='application/pdf'",
        'spaces' => 'drive',
        'fields' => 'files(id, name, createdTime)',
        // 'orderBy' => 'createdTime desc',
        // 'pageSize' => 1
    ));

    foreach ($results->files as $file) {
        $filePath = $localPath . '\\' . $file->name;

        $path = "pedidos/";
        $diretorio = dir($path);
        while ($arquivo = $diretorio->read()) {
            if (!in_array($arquivo, array('.', '..'))) {
                if ($arquivo != $file->name) {
                    $filePath = $localPath . '\\' . $file->name;
                    downloadFile($service, $file->id, $filePath);
                    printFile($filePath);
                }
            }
        }
        $diretorio->close();
        
        downloadFile($service, $file->id, $filePath);
        printFile($filePath);
        // unlink($filePath); // Remove o arquivo local após a impressão
    }

    // if (count($results->files) > 0) {
    //     $file = $results->files[0];

    //     // $path = "pedidos/";
    //     // $diretorio = dir($path);
    //     // echo "Lista de Arquivos do diretório '<strong>" . $path . "</strong>':<br />";
    //     // while ($arquivo = $diretorio->read()) {
    //     //     if (!in_array($arquivo, array('.', '..'))) {

    //     //         if ($arquivo != $file->name) {
    //     //             $filePath = $localPath . '\\' . $file->name;
    //     //             downloadFile($service, $file->id, $filePath);
    //     //             printFile($filePath);
    //     //         }

    //     //         echo "<a href='" . $path.$arquivo . "'>" . $arquivo . "</a><br />";
    //     //     }
    //     // }
    //     // $diretorio->close();

    //     $filePath = $localPath . '\\' . $file->name;
    //     downloadFile($service, $file->id, $filePath);
    //     printFile($filePath);
    //     // $filesystem->remove($filePath); // Remove o arquivo local após a impressão
    // } else {
    //     echo "Nenhum arquivo PDF encontrado na pasta especificada.";
    // }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
