<?php

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../services/EmailSyncService.php';
include_once __DIR__ . '/../models/EmailAccount.php';
include_once __DIR__ . '/../models/Email.php';

class EmailSyncController {
    private $emailSyncService;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();

        $this->emailSyncService = new EmailSyncService(($db));
    }

    public function syncEmails() {
        $data = json_decode(file_get_contents('php://input'), true);

        $user_id = $data['user_id'];
        $provider_id = $data['provider_id']; // Passando o provider_id

        $response = $this->emailSyncService->syncEmailsByUserIdAndProviderId($user_id, $provider_id);

        echo json_encode($response);
    }
}